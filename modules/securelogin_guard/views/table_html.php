<?php

defined('BASEPATH') or exit('No direct script access allowed');

$headings = [
    _l('ip_address'),
];

// Add staff member column only for admins
if (isset($is_admin) && $is_admin) {
    $headings[] = _l('staff_member');
}

$headings[] = _l('description');
$headings[] = _l('status');
$headings[] = _l('date_created');
$headings[] = _l('options');

render_datatable(
    $headings,
    'securelogin-guard',
    [],
    [
        'data-last-order-identifier' => 'securelogin_guard',
        'data-default-order'         => get_table_last_order('securelogin_guard'),
    ]
);

