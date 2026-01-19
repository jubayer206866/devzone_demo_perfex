<?php

defined('BASEPATH') or exit('No direct script access allowed');

/*
Module Name: SecureLogin Guard - IP Whitelist
Description: Restrict login access to specific IP addresses for enhanced security
Version: 1.0.0
Author: DevzoneIT
Author URI: https://codecanyon.net/user/devzoneit
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
$helper_path = APP_MODULES_PATH . SECURELOGIN_GUARD_MODULE_NAME . '/helpers/securelogin_guard_helper.php';
if (file_exists($helper_path)) {
    include_once($helper_path);
}

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
    
    // IP whitelist is always active (no settings needed)
    
    // Determine if this is staff or client login based on hook name or data
    $is_staff = false;
    $userid = null;
    
    // Check hook context - before_staff_login has userid, before_client_login has contact_user_id
    if (isset($data['userid']) && !isset($data['contact_user_id'])) {
        $is_staff = true;
        $userid = $data['userid'];
    }
    
    // Always allow admins to login from any IP
    if ($is_staff && $userid) {
        $CI->db->where('staffid', $userid);
        $CI->db->where('admin', 1);
        $admin_user = $CI->db->get(db_prefix() . 'staff')->row();
        if ($admin_user) {
            return; // Admin - always allow login
        }
    }
    
    // Check if there are any IP whitelist entries
    $CI->db->from(db_prefix() . 'securelogin_guard_whitelist');
    $has_any_ips = $CI->db->count_all_results() > 0;
    
    // If no IP entries exist, allow all logins
    if (!$has_any_ips) {
        return; // No IPs added, allow normal login
    }
    
    $user_ip = $CI->input->ip_address();
    
    // For staff, check if their IP is whitelisted and they are assigned to it
    if ($is_staff && $userid) {
        // Get active IPs assigned to this staff member
        $CI->db->where('w.is_active', 1);
        $CI->db->where('ws.staff_id', $userid);
        $CI->db->select('w.*');
        $CI->db->from(db_prefix() . 'securelogin_guard_whitelist w');
        $CI->db->join(db_prefix() . 'securelogin_guard_whitelist_staffs ws', 'w.id = ws.whitelist_id', 'inner');
        $CI->db->group_by('w.id');
        $whitelist = $CI->db->get()->result();
        
        // If staff is not assigned to any IP, they can login from any IP
        if (empty($whitelist)) {
            return; // Staff not assigned to any IP, allow login from any IP
        }
        
        $is_whitelisted = false;
        
        // Check if IP matches any whitelisted entry assigned to this staff (including CIDR)
        foreach ($whitelist as $entry) {
            if ($user_ip === $entry->ip_address) {
                // Exact match
                $is_whitelisted = true;
                break;
            } elseif (strpos($entry->ip_address, '/') !== false) {
                // CIDR notation check
                // Load helper function if not loaded
                if (!function_exists('securelogin_guard_ip_matches')) {
                    $helper_path = APP_MODULES_PATH . SECURELOGIN_GUARD_MODULE_NAME . '/helpers/securelogin_guard_helper.php';
                    if (file_exists($helper_path)) {
                        include_once($helper_path);
                    }
                }
                if (function_exists('securelogin_guard_ip_matches')) {
                    if (securelogin_guard_ip_matches($user_ip, $entry->ip_address)) {
                        $is_whitelisted = true;
                        break;
                    }
                }
            }
        }
        
        // Staff is assigned to IP(s) but current IP doesn't match - block login
        if (!$is_whitelisted) {
            $email = isset($data['email']) ? $data['email'] : 'N/A';
            log_activity('Login blocked - IP not whitelisted for staff [IP: ' . $user_ip . ', Staff ID: ' . $userid . ', Email: ' . $email . ']');
            
            // Set error message
            set_alert('danger', _l('securelogin_guard_ip_not_whitelisted'));
            
            // Redirect to login page
            redirect(admin_url('authentication'));
            exit;
        }
    } else {
        // For clients, IP whitelist doesn't apply (only staff)
        // Allow client login
        return;
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

