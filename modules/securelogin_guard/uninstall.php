<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Uninstall module - remove database table and options
 */
$CI = &get_instance();

// Drop table
$CI->db->query("DROP TABLE IF EXISTS `" . db_prefix() . "securelogin_guard_whitelist`;");

// Remove options
delete_option('securelogin_guard_enable_ip_whitelist');
delete_option('securelogin_guard_bypass_admin');

