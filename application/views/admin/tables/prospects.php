<?php
defined('BASEPATH') or exit('No direct script access allowed');

$statuses = isset($statuses) ? $statuses : [];

$aColumns = [
    db_prefix().'prospects.id as pid',
    db_prefix().'prospects.name as name',
    db_prefix().'prospects.email as email',
    db_prefix().'prospects.address as address',
    db_prefix().'prospects.city as city',
    db_prefix().'prospects.country as country',
    db_prefix().'leads_status.name as status',
    db_prefix().'leads_status.color as color',
];

$sIndexColumn = db_prefix().'prospects.id';
$sTable       = db_prefix().'prospects';

$join = [
    'LEFT JOIN '.db_prefix().'leads_status ON '.db_prefix().'leads_status.id = '.db_prefix().'prospects.status',
];

$result  = data_tables_init($aColumns, $sIndexColumn, $sTable, $join);
$output  = $result['output'];
$rResult = $result['rResult'];

$counter = 1;

foreach ($rResult as $aRow) {

    $row = [];

    $row[] = $counter++;

    $name  = $aRow['name'] ?? '';
    $pid   = $aRow['pid'];

    $name .= '<div class="row-options">';
    $name .= '<a href="javascript:void(0)" onclick="edit_prospect('.$pid.')">'._l('edit').'</a>';
    $name .= ' | ';
    $name .= '<a href="'.admin_url('prospects/delete/'.$pid).'" 
                class="text-danger _delete">'._l('delete').'</a>';
    $name .= '</div>';

    $row[] = $name;

    $row[] = $aRow['email'] ?? '';
    $row[] = ucfirst($aRow['address'] ?? '');
    $row[] = ucfirst($aRow['city'] ?? '');
    $row[] = ucfirst($aRow['country'] ?? '');

    $status = $aRow['status'] ?? '';
    $color  = $aRow['color'] ?? '#777';

    $current_status_name  = $status ?: '—';
            $current_status_color = $color ?: '#777';

            $dropdown = '
            <div class="dropdown">
            <a href="javascript:void(0)"
                class="dropdown-toggle label"
                data-toggle="dropdown"
                style="background:'.$current_status_color.';">
                '.$current_status_name.' <i class="fa fa-caret-down"></i>
            </a>
            <ul class="dropdown-menu">
            ';

            foreach ($statuses as $s) {
                $dropdown .= '
                <li>
                <a href="javascript:void(0)"
                    class="change-status"
                    data-id="'.$pid.'"
                    data-status="'.$s['id'].'">
                    '.$s['name'].'
                </a>
                </li>';
            }

            $dropdown .= '
            </ul>
            </div>';

            $row[] = $dropdown;




    $output['aaData'][] = $row;
}


