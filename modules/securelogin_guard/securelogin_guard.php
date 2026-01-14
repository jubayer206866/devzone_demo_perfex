<?php

defined('BASEPATH') or exit('No direct script access allowed');

/*
Module Name: SecureLogin Guard - IP Whitelist
Description: Restrict login access to specific IP addresses for enhanced security
Version: 1.0.0
Requires at least: 2.3.*
*/

define('SECURELOGIN_GUARD_MODULE_NAME', 'securelogin_guard');

$CI = &get_instance();

/**
 * Register language files, must be registered if the module is using languages
 * Register early so language files are available when needed
 */
register_language_files(SECURELOGIN_GUARD_MODULE_NAME, [SECURELOGIN_GUARD_MODULE_NAME]);

/**
 * Hook into authentication process to check IP whitelist
 */
hooks()->add_action('before_staff_login', 'securelogin_guard_check_ip_whitelist');
hooks()->add_action('before_client_login', 'securelogin_guard_check_ip_whitelist');

/**
 * Add menu item in Setup
 */
hooks()->add_action('admin_init', 'securelogin_guard_init_menu_items');

/**
 * Register staff capabilities for this module
 * Use a language fallback so the UI won't show raw key when translation is missing
 * Try to get the language string, but use fallback if not available yet
 * Use log_errors = false to prevent errors during module initialization
 */
$__slg_name = 'SecureLogin Guard'; // Default fallback
if (function_exists('_l')) {
    // Try to manually load the language file if not already loaded
    // Get language from various sources with fallback
    $current_lang = 'english'; // Default fallback
    if (isset($GLOBALS['language']) && !empty($GLOBALS['language'])) {
        $current_lang = $GLOBALS['language'];
    } elseif (function_exists('get_option')) {
        try {
            $option_lang = get_option('active_language');
            if (!empty($option_lang)) {
                $current_lang = $option_lang;
            }
        } catch (Exception $e) {
            // Database might not be ready during module initialization
        }
    } elseif (isset($CI->config) && $CI->config->item('language')) {
        $current_lang = $CI->config->item('language');
    }
    
    $lang_file_path = APP_MODULES_PATH . SECURELOGIN_GUARD_MODULE_NAME . '/language/' . $current_lang . '/securelogin_guard_lang.php';
    if (file_exists($lang_file_path)) {
        // Try to load the language file - it will only load if not already loaded
        try {
            $CI->lang->load(SECURELOGIN_GUARD_MODULE_NAME . '/securelogin_guard', $current_lang);
        } catch (Exception $e) {
            // Ignore errors during language loading
        }
    }
    
    // Use log_errors = false to prevent errors during module initialization
    $__slg_name_temp = _l('securelogin_guard', '', false);
    if ($__slg_name_temp !== false && $__slg_name_temp !== 'securelogin_guard' && $__slg_name_temp !== '') {
        $__slg_name = $__slg_name_temp;
    }
}
register_staff_capabilities('securelogin_guard', [
    'capabilities' => [
        'view'   => _l('permission_view'),
        'create' => _l('permission_create'),
        'edit'   => _l('permission_edit'),
        'delete' => _l('permission_delete'),
    ],
], $__slg_name);

/**
 * Add action links in module list
 */
hooks()->add_filter('module_securelogin_guard_action_links', 'module_securelogin_guard_action_links');

/**
 * Add additional settings for this module in the module list area
 * @param  array $actions current actions
 * @return array
 */
function module_securelogin_guard_action_links($actions)
{
    $actions[] = '<a href="' . admin_url('securelogin_guard/manage') . '">' . _l('manage_ip_whitelist') . '</a>';

    return $actions;
}

/**
 * Load the module helper
 */
$CI->load->helper(SECURELOGIN_GUARD_MODULE_NAME . '/securelogin_guard');

/**
 * Register activation module hook
 */
register_activation_hook(SECURELOGIN_GUARD_MODULE_NAME, 'securelogin_guard_activation_hook');

function securelogin_guard_activation_hook()
{
    require_once(__DIR__ . '/install.php');
}

/**
 * Check IP whitelist before login
 * This function is called automatically when staff/client tries to login
 */
function securelogin_guard_check_ip_whitelist($data)
{
    $CI = &get_instance();
    
    // Check if IP whitelist is enabled
    if (get_option('securelogin_guard_enable_ip_whitelist') != '1') {
        return; // Whitelist is disabled, allow login
    }
    
    // Determine if this is staff or client login based on hook name or data
    $is_staff = false;
    $userid = null;
    
    // Check hook context - before_staff_login has userid, before_client_login has contact_user_id
    if (isset($data['userid']) && !isset($data['contact_user_id'])) {
        $is_staff = true;
        $userid = $data['userid'];
    }
    
    // Check if bypass for admin is enabled
    if (get_option('securelogin_guard_bypass_admin') == '1' && $is_staff && $userid) {
        $CI->db->where('staffid', $userid);
        $CI->db->where('admin', 1);
        $admin_user = $CI->db->get(db_prefix() . 'staff')->row();
        if ($admin_user) {
            return; // Bypass for admin - allow login
        }
    }
    
    $user_ip = $CI->input->ip_address();
    
    // Check if staff_id column exists in the table
    $columns = $CI->db->list_fields(db_prefix() . 'securelogin_guard_whitelist');
    $has_staff_column = in_array('staff_id', $columns);
    
    // Get active whitelisted IPs - check both staff-specific and global (staff_id IS NULL)
    $CI->db->where('is_active', 1);
    if ($is_staff && $userid && $has_staff_column) {
        // For staff, check both staff-specific and global IPs
        $CI->db->group_start();
        $CI->db->where('staff_id', $userid);
        // Explicit IS NULL to avoid ambiguous NULL handling
        $CI->db->or_where('staff_id IS NULL', null, false);
        $CI->db->group_end();
    } else {
        // For clients or if staff_id column doesn't exist, only check global IPs (staff_id IS NULL)
        if ($has_staff_column) {
            // Explicit IS NULL to avoid ambiguous NULL handling
            $CI->db->where('staff_id IS NULL', null, false);
        }
        // If staff_id column doesn't exist, check all IPs (backward compatibility)
    }
    $whitelist = $CI->db->get(db_prefix() . 'securelogin_guard_whitelist')->result();
    
    $is_whitelisted = false;
    
    // Check if IP matches any whitelisted entry (including CIDR)
    foreach ($whitelist as $entry) {
        if ($user_ip === $entry->ip_address) {
            // Exact match
            $is_whitelisted = true;
            break;
        } elseif (strpos($entry->ip_address, '/') !== false) {
            // CIDR notation check
            // Load helper function if not loaded
            if (!function_exists('securelogin_guard_ip_matches')) {
                $CI->load->helper(SECURELOGIN_GUARD_MODULE_NAME . '/securelogin_guard');
            }
            if (function_exists('securelogin_guard_ip_matches')) {
                if (securelogin_guard_ip_matches($user_ip, $entry->ip_address)) {
                    $is_whitelisted = true;
                    break;
                }
            }
        }
    }
    
    if (!$is_whitelisted) {
        // Log the blocked attempt
        $email = isset($data['email']) ? $data['email'] : 'N/A';
        $user_type = $is_staff ? 'Staff' : 'Client';
        log_activity('Login blocked - IP not whitelisted [IP: ' . $user_ip . ', Email: ' . $email . ', Type: ' . $user_type . ']');
        
        // Set error message
        set_alert('danger', _l('securelogin_guard_ip_not_whitelisted'));
        
        // Redirect to login page
        if ($is_staff) {
            redirect(admin_url('authentication'));
        } else {
            redirect(site_url('authentication/login'));
        }
        exit;
    }
}

/**
 * Init SecureLogin Guard module menu items in setup in admin_init hook
 * @return null
 */
function securelogin_guard_init_menu_items()
{
    // Add a top-level main sidebar item instead of Setup submenu
    if (has_permission('securelogin_guard', '', 'view') || is_admin()) {
        $CI = &get_instance();
        $CI->app_menu->add_sidebar_menu_item('securelogin-guard', [
            'slug'     => 'securelogin-guard',
            'name'     => _l('securelogin_guard'),
            'href'     => admin_url('securelogin_guard/manage'),
            'position' => 200,
            'icon'     => 'fa fa-shield-halved',
        ]);
    }
}

