<?php

defined('BASEPATH') or exit('No direct script access allowed');

$aColumns = [
    'name',
    'email',
    'address',
    'city',
    'country'
];

$sIndexColumn = 'id';
$sTable = db_prefix().'prospects';

$result = data_tables_init($aColumns, $sIndexColumn, $sTable);

$output  = $result['output'];
$rResult = $result['rResult'];

foreach ($rResult as $aRow) {
    $row = [];
    foreach ($aColumns as $col) {
        $row[] = $aRow[$col];
    }
    $output['aaData'][] = $row;
}

echo json_encode($output);
