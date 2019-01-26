<?php

function output_csv ($destination = 'export.csv', array &$data) {
    if (count($data) === 0) {
        return NULL;
    }

    $df = fopen('reports/' . $destination, 'w');
    
    foreach ($data as $row) {
        fputcsv($df, $row, ';');
    }
    
    fclose($df);
    
}