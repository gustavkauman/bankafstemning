<?php

/**
 * Get all relevant files
 */

require_once 'src/loader.php';

/**
 * Get all orders from ViaBill
 */

$v_file = csv_to_array('files/viabill.csv');
$v_settlements = filter_by_value($v_file, 'text', 'Settlement');
$v_orders = array_udiff($v_file, $v_settlements, 'udiffCompare');

/**
 * Get all statements from account
 */

$nav_file = csv_to_array('files/Konto-7457-oktober.csv');

/**
 * Initialize variables
 */

$brugte = array();
$afstemte = array();
$output = "";
$csv_output = array();
$headers = array(
    "account_statement_id",
    "account_statement_text",
    "account_statement_amount",
    "settlement_id",
    "settlement_text",
    "settlement_amount");
array_push($csv_output, $headers);

/**
 * Get account statement ID's
 */
$ac_statements_id = array();
foreach ($nav_file as $ac_statement) {
    $ac_statements_id[] = $ac_statement['ID'];
}

/**
 * Get ViaBill statement ID's
 */
$v_statements_id = array();
foreach ($v_file as $v_statement) {
    if (!$v_statement['text'] = 'Settlement')
    $v_statements_id[] = $v_statement['ID'];
}

/**
 * Find and balance nav_file with ViaBill statements
 */
foreach ($nav_file as $statement) {
   
   // Get amount
   $amount = $statement['Amount']; 

   // Find ViaBill statements that fit
   $fits = filter_by_value($v_file, 'amount', $amount);

   foreach ($fits as $fit) {

        if ($statement['Date'] != $fit['event-date']) {
            
        }

        if (!in_array($statement['ID'], $afstemte)) {

            if ($fit['amount'] == $amount && !in_array($fit['ID'], $brugte)) {
            $brugte[] = $fit['ID'];
            $afstemte[] = $statement['ID'];
            $output .= "Afstemmer kontopostering ". $statement['ID'] . " (DKK " . $amount .") med ViaBill info ".$fit['ID']." (DKK " . $fit['amount'] .")\r\n";

             $csv_output[] = array(
                $statement['ID'],
                $statement['Text'],
                $amount,
                $fit['ID'],
                $fit['text'],
                $fit['amount']);
            }
        }
    }
}

/**
 * Check for unbalanced account statements
 */

$uafstemte = array_diff($ac_statements_id, $afstemte);
$output2 = "";
foreach ($uafstemte as $uafstemt) {
    $output2 .= "Kontopostering " . $uafstemt . "\r\n";
}

/**
 * Get unused ViaBill statements
 */

$ubrugte = array_diff($v_statements_id, $brugte);

/**
 * Log to terminal
 */

print("\r\n\r\nAfstemmer kontoposteringer med ViaBill...\r\n \r\n");
print($temp_string = "AFSTEMTE kontoposteringer:" . "\r\n");
print(str_repeat('-', strlen($temp_string)) . "\r\n\r\n");
print_r($output);
print("\r\nDer er i alt blevet afstemt *" . count($afstemte) . "* posteringer");
print("\r\n\r\n===========================\r\n");
print("\r\n" . $tmp_string = "UAFSTEMTE kontoposteringer:" . "\n");
print(str_repeat('-', strlen($tmp_string)) . "\r\n\r\n");
print_r($output2);
print("\r\nDer er i alt *" . count($uafstemte) . "* uafstemte posteringer\r\n");
print("\r\n===========================\r\n\r\n");

if (count($ubrugte) === 0) {
    print("Der er ingen ubrugte info fra ViaBill\r\n");
} else {
    foreach ($ubrugte as $ubrugt) {
        print("Ubrugte info" . $ubrugt['text'] . " (". $ubrugt['ID'] . ")");
    }
    print("Der er i alt" . count($ubrugte) . "ubrugte\r\n");
}

/**
 * Output CSV file
 */

output_csv("konto-m-viabill.csv", $csv_output);
