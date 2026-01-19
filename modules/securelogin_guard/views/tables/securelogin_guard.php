<?php

defined('BASEPATH') or exit('No direct script access allowed');

$hasPermissionEdit = has_permission('securelogin_guard', '', 'edit');
$hasPermissionDelete = has_permission('securelogin_guard', '', 'delete');
$is_admin = is_admin();

$aColumns = [
    db_prefix() . 'securelogin_guard_whitelist.id as id',
    db_prefix() . 'securelogin_guard_whitelist.ip_address as ip_address',
    db_prefix() . 'securelogin_guard_whitelist.description as description',
    db_prefix() . 'securelogin_guard_whitelist.is_active as is_active',
    db_prefix() . 'securelogin_guard_whitelist.date_created as date_created',
];

$join = [];

$sIndexColumn = 'id';
$sTable = db_prefix() . 'securelogin_guard_whitelist';

$where = [];

// For non-admins, only show their own entries
if (!$is_admin) {
    $current_staff_id = get_staff_user_id();
    $where[] = 'AND EXISTS (SELECT 1 FROM ' . db_prefix() . 'securelogin_guard_whitelist_staffs WHERE whitelist_id = ' . $sTable . '.id AND staff_id = ' . (int)$current_staff_id . ')';
}

// Additional select for assigned staff IDs
$additionalSelect = [
    '(SELECT GROUP_CONCAT(DISTINCT CAST(staff_id AS CHAR) SEPARATOR ",") FROM ' . db_prefix() . 'securelogin_guard_whitelist_staffs WHERE whitelist_id = ' . $sTable . '.id ORDER BY staff_id) as assigned_staff_ids',
];

try {
    $result = data_tables_init($aColumns, $sIndexColumn, $sTable, $join, $where, $additionalSelect);
    
    // Check if result is valid
    if (!isset($result) || !is_array($result) || !isset($result['output']) || !isset($result['rResult'])) {
        $output = [
            'aaData' => [],
            'iTotalRecords' => 0,
            'iTotalDisplayRecords' => 0,
            'sEcho' => isset($_POST['draw']) ? intval($_POST['draw']) : 1
        ];
        $rResult = [];
    } else {
        $output = $result['output'];
        $rResult = $result['rResult'];
    }
} catch (Exception $e) {
    // Log error and return empty result
    log_activity('SecureLogin Guard Table Error: ' . $e->getMessage());
    $output = [
        'aaData' => [],
        'iTotalRecords' => 0,
        'iTotalDisplayRecords' => 0,
        'sEcho' => isset($_POST['draw']) ? intval($_POST['draw']) : 1
    ];
    $rResult = [];
}

// Initialize output if not set (safety check)
if (!isset($output) || !is_array($output)) {
    $output = [
        'aaData' => [],
        'iTotalRecords' => 0,
        'iTotalDisplayRecords' => 0,
        'sEcho' => isset($_POST['draw']) ? intval($_POST['draw']) : 1
    ];
}

// Ensure rResult is an array
if (!isset($rResult) || !is_array($rResult)) {
    $rResult = [];
}

// Get assigned staff for each IP
foreach ($rResult as $aRow) {
    $row = [];
    
    // IP Address column
    $ipOutput = '<code class="tw-font-medium">' . e($aRow['ip_address']) . '</code>';
    $ipOutput .= '<div class="row-options">';
    $ipOutput .= '<a href="#" class="edit-ip-btn" data-id="' . $aRow['id'] . '" data-toggle="modal" data-target="#editIpModal">' . _l('edit') . '</a>';
    if ($hasPermissionDelete) {
        $ipOutput .= ' | <a href="' . admin_url('securelogin_guard/delete/' . $aRow['id']) . '" class="_delete">' . _l('delete') . '</a>';
    }
    $ipOutput .= '</div>';
    
    $row[] = $ipOutput;
    
    // Staff Member column (only for admins)
    if ($is_admin) {
        $staffOutput = '';
        if (!empty($aRow['assigned_staff_ids']) && $aRow['assigned_staff_ids'] !== null) {
            $staff_ids = array_map('intval', explode(',', $aRow['assigned_staff_ids']));
            foreach ($staff_ids as $staff_id) {
                $staffOutput .= '<span class="label label-primary" style="margin-right: 5px;">' . e(get_staff_full_name($staff_id)) . '</span>';
            }
        } else {
            $staffOutput = '<span class="text-muted">' . _l('no_staff_assigned') . '</span>';
        }
        $row[] = $staffOutput;
    }
    
    // Description column
    $row[] = e($aRow['description'] ? $aRow['description'] : '-');
    
    // Status column - using onoffswitch like customers
    if ($hasPermissionEdit) {
        $toggleActive = '<div class="onoffswitch" data-toggle="tooltip" data-title="' . _l('toggle_ip_status') . '">
    <input type="checkbox" data-switch-url="' . admin_url() . 'securelogin_guard/change_ip_status" name="onoffswitch" class="onoffswitch-checkbox" id="ip_' . $aRow['id'] . '" data-id="' . $aRow['id'] . '" ' . ($aRow['is_active'] == 1 ? 'checked' : '') . '>
    <label class="onoffswitch-label" for="ip_' . $aRow['id'] . '"></label>
    </div>';
        // For exporting (using main language strings)
        $toggleActive .= '<span class="hide">' . ($aRow['is_active'] == 1 ? _l('is_active_export', '', false) : _l('is_not_active_export', '', false)) . '</span>';
    } else {
        // For non-editors, just show label
        if ($aRow['is_active'] == 1) {
            $toggleActive = '<span class="label label-success">' . _l('active') . '</span>';
        } else {
            $toggleActive = '<span class="label label-default">' . _l('inactive') . '</span>';
        }
    }
    
    $row[] = $toggleActive;
    
    // Date Created column
    $row[] = e(_d($aRow['date_created']));
    
    // Options column
    $options = '';
    if ($hasPermissionEdit) {
        $options .= icon_btn('#', 'fa-regular fa-pen-to-square', 'edit-ip-btn', [
            'data-id' => $aRow['id'],
            'data-toggle' => 'modal',
            'data-target' => '#editIpModal',
        ]);
    }
    if ($hasPermissionDelete) {
        $options .= icon_btn('securelogin_guard/delete/' . $aRow['id'], 'fa fa-remove', 'btn-danger _delete');
    }
    $row[] = $options;
    
    $row['DT_RowClass'] = 'has-row-options';
    
    $output['aaData'][] = $row;
}

