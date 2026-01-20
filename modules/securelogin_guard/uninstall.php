<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Uninstall module - remove database tables and options
 */
$CI = &get_instance();

// Drop tables (drop staff assignments table first due to foreign key constraints)
$CI->db->query("DROP TABLE IF EXISTS `" . db_prefix() . "securelogin_guard_whitelist_staffs`;");
$CI->db->query("DROP TABLE IF EXISTS `" . db_prefix() . "securelogin_guard_whitelist`;");

// Remove options (if any exist)
// Note: No options to remove as settings are now automatic

