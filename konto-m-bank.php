<?php

/**
 * Get all relevant files
 */

require_once 'src/loader.php';

/**
 * Get all statements from Account
 */

$account_statements = csv_to_array('files/Konto-7442-oktober.csv');

/**
 * Get all bank statements
 */

$bank_statements = csv_to_array('files/bank-dkkort-okt18.csv');

// Get bank statement ID's
$bank_statements_id = array();
foreach ($bank_statements as $bank_statement) {
    $bank_statements_id[] .= (string) $bank_statement['ID'];
}

/**
 * Get ViaBill statement ID's
 */
$dk_statements_id = array();
foreach ($account_statements as $dk_statement) {
    $dk_statements_id[] = $dk_statement['ID'];
}

/**
 * Initialize variables
 */

$afstemte = array();
$brugte = array();
$output = "";
$csv_output = array();
$headers = array(
    "bank_statement_id",
    "bank_statement_text",
    "bank_statement_date",
    "bank_statement_amount",
    "bank_statement_order_no",
    "account_id",
    "account_text",
    "account_date",
    "account_amount",
    "account_order_no");
array_push($csv_output, $headers);

/**
 * Find and balance bank statements with account statement
 */

foreach ($bank_statements as $bank_statement) {
    
    // Order no   
    $order_no = $bank_statement['order-no'];

    // Finder account statement, der passer på beløb
    $fits = filter_by_value($account_statements, 'order-no', $order_no);
    
    foreach ($fits as $fit) {
        
        if (!in_array($bank_statement['ID'], $afstemte)) {

            if ($bank_statement['Beloeb'] == $fit['amount']) {
                $brugte[] = $fit['ID'];
                $afstemte[] = $bank_statement['ID'];
                $output .= "Afstemmer bankpostering ". $bank_statement['ID'] ." (DKK " . $bank_statement['Beloeb'] .") med settlement ".$fit['ID']." (DKK " . $fit['amount'] .")\r\n";

                $csv_output[] = array(
                    $bank_statement['ID'],
                    $bank_statement['Tekst'],
                    $bank_statement['Dato'],
                    $bank_statement['Beloeb'],
                    $bank_statement['order-no'],
                    $fit['ID'],
                    $fit['text'],
                    $fit['date'],
                    $fit['amount'],
                    $fit['order-no']);
            }

            /*
            if ($fit['amount'] == $amount && !in_array($fit['ID'], $brugte)) {
                // Beløbet passer og den er ikke blevet brugt førhen
                $brugte[] = $fit['ID'];
                $afstemte[] = $bank_statement['ID'];
                $output .= "Afstemmer bankpostering ". $bank_statement['ID'] ." (DKK " . $bank_statement['Beloeb'] .") med settlement ".$fit['ID']." (DKK " . $fit['amount'] .")\r\n";

                $csv_output[] = array(
                    $bank_statement['ID'],
                    $bank_statement['Tekst'],
                    $bank_statement['Dato'],
                    $bank_statement['Beloeb'],
                    $fit['ID'],
                    $fit['text'],
                    $fit['date'],
                    $fit['amount']);
            }
            */
        }
    }
}

/**
 * Balance unbalanced bank statements with amount
 */

foreach ($bank_statements as $bank_statement) {

    if (!in_array($bank_statement['ID'],$afstemte)) {

        // amount
        $amount = $bank_statement['Beloeb'];

        // Finder account statement, der passer på beløb
        $fits = filter_by_value($account_statements, 'amount', $amount);

        foreach ($fits as $fit) {
            
            if (!in_array($bank_statement['ID'], $afstemte)) {

                if ($fit['amount'] == $amount && !in_array($fit['ID'], $brugte)) {
                    // Beløbet passer og den er ikke blevet brugt førhen
                    $brugte[] = $fit['ID'];
                    $afstemte[] = $bank_statement['ID'];
                    $output .= "Afstemmer bankpostering ". $bank_statement['ID'] ." (DKK " . $bank_statement['Beloeb'] .") med settlement ".$fit['ID']." (DKK " . $fit['amount'] .")\r\n";

                    $csv_output[] = array(
                        $bank_statement['ID'],
                        $bank_statement['Tekst'],
                        $bank_statement['Dato'],
                        $bank_statement['Beloeb'],
                        "",
                        $fit['ID'],
                        $fit['text'],
                        $fit['date'],
                        $fit['amount'],
                        "");
                }
            }
        }
    }
}

/**
 * Get unused account statements
 */

$ubrugte = array_diff($dk_statements_id, $brugte);

$csv_output_ubrugt = array();
$csv_output_ubrugt[] = array(
    "Dato",
    "ID",
    "Konto",
    "Tekst",
    "Beløb",
    "Ordre nr",
    "Balance"
);

foreach ($account_statements as $account_statement) {
    if (!in_array($account_statement['ID'], $brugte)) {
        $csv_output_ubrugt[] = array(
            $account_statement['date'],
            $account_statement['ID'],
            $account_statement['account'],
            $account_statement['text'],
            $account_statement['amount'],
            $account_statement['order-no'],
            $account_statement['balance']
        );
    }
}

output_csv("konto-7442-uafstemt.csv", $csv_output_ubrugt);

/**
 * Check for unbalanced bank statements
 */

$uafstemte = array_diff($bank_statements_id, $afstemte);
$output2 = "";
foreach ($uafstemte as $uafstemt) {
    $output2 .= "Bankpostering " . $uafstemt . "\r\n";
}

$csv_output_uafstemt = array();
$csv_output_uafstemt[] = array(
    "Dato",
    "ID",
    "Tekst",
    "Beløb",
    "Ordre nr",
    "Status",
    "Afstemt"
);

foreach ($bank_statements as $bank_statement) {
    if (!in_array($bank_statement['ID'], $afstemte)) {
        $csv_output_uafstemt[] = array(
            $bank_statement['Dato'],
            $bank_statement['ID'],
            $bank_statement['Tekst'],
            $bank_statement['Beloeb'],
            $bank_statement['order-no'],
            $bank_statement['Status'],
            $bank_statement['Afstemt']
        );
    }
}

output_csv("bank-uafstemt.csv", $csv_output_uafstemt);

/**
 * Log to terminal
 */

print("\r\n\r\nAfstemmer bankposteringer med ViaBill...\r\n \r\n");
print($temp_string = "AFSTEMTE bankposteringer:" . "\n");
print(str_repeat('-', strlen($temp_string)) . "\r\n\r\n");
print_r($output);
print("\r\nDer er i alt blevet afstemt *" . count($afstemte) . "* posteringer");
print("\r\n===========================\r\n");
print("\r\n" . $tmp_string = "UAFSTEMTE bankposteringer:" . "\n");
print(str_repeat('-', strlen($tmp_string)) . "\r\n\r\n");
print_r($output2);
print("\r\nDer er i alt *" . count($uafstemte) . "* uafstemt(e) posteringer\r\n");
print("\r\n===========================\r\n\r\n");

if (count($ubrugte) === 0) {
    print("Der er ingen ubrugte kontoposteringer\r\n");
} else {
    foreach ($ubrugte as $ubrugt) {
        $id = $ubrugt;
        foreach ($account_statements as $account_statement) {
            if ($account_statement['ID'] == $id) {
                print("Ubrugt info " . $account_statement['text'] . " (". $account_statement['ID'] . ")\n");
            }
        }
    }
    print("\nDer er i alt " . count($ubrugte) . " ubrugte kontoposteringer\r\n");
}

/**
 * Output CSV file
 */

output_csv("konto-m-bank.csv", $csv_output);