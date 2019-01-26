<?php

/**
 * Get all relevant files
 */

require_once 'src/loader.php';

/**
 * Get all settlements from ViaBill
 */

$settlements = filter_by_value(csv_to_array('files/viabill-oktober18.csv'), 'text', 'Settlement');

/**
 * Get all bank statements
 */

$bank_statements = csv_to_array('files/bank-viabill-18.csv');

// Get bank statement ID's
$bank_statements_id = array();
foreach ($bank_statements as $bank_statement) {
    $bank_statements_id[] .= (string) $bank_statement['ID'];
}

/**
 * Get ViaBill statement ID's
 */
$v_statements_id = array();
foreach ($settlements as $v_statement) {
    $v_statements_id[] = $v_statement['ID'];
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
    "bank_statement_amount",
    "settlement_id",
    "settlement_text",
    "settlement_amount");
array_push($csv_output, $headers);

/**
 * Find and balance bank statements with settlements
 */

foreach ($bank_statements as $bank_statement) {
    
    // Beløb   
    $amount = $bank_statement['Beloeb'];

    // Finder settlements, der passer på beløb
    $fits = filter_by_value($settlements, 'amount', $amount);
    
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
                    $fit['ID'],
                    $fit['text'],
                    $fit['event-date'],
                    $fit['amount']);
            }
        }
    }
}

/**
 * Get unused ViaBill statements
 */

$ubrugte = array_diff($v_statements_id, $brugte);

/**
 * Check for unbalanced bank statements
 */

$uafstemte = array_diff($bank_statements_id, $afstemte);
$output2 = "";
foreach ($uafstemte as $uafstemt) {
    $output2 .= "Bankpostering " . $uafstemt . "\r\n";
}

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
    print("Der er ingen ubrugte info fra ViaBill\r\n");
} else {
    foreach ($ubrugte as $ubrugt) {
        $id = $ubrugt;
        foreach ($settlements as $settlement) {
            if ($settlement['ID'] == $id) {
                print("Ubrugt info " . $settlement['text'] . " (". $settlement['ID'] . ")\n");
            }
        }
    }
    print("\nDer er i alt " . count($ubrugte) . " ubrugte info fra ViaBill\r\n");
}

/**
 * Output CSV file
 */

output_csv("viabill-m-bank.csv", $csv_output);