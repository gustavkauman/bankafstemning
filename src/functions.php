<?php

function udiffCompare($a, $b) {
    return $a['ID'] - $b['ID'];
}

function sort_array($array, $key) {
    
    function cmp($a, $b) {
        return $a[$key] <=> $b[$key];
    }

    return usort($array, 'cmp');
}