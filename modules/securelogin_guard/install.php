<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Create database table for IP whitelist
 * Handles initial installation and upgrades
 */
$CI = &get_instance();

$table_name = db_prefix() . 'securelogin_guard_whitelist';

// Create table if it doesn't exist
$CI->db->query("CREATE TABLE IF NOT EXISTS `" . $table_name . "` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ip_address` varchar(45) NOT NULL,
  `description` text,
  `staff_id` int(11) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `date_created` datetime NOT NULL,
  `date_modified` datetime DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ip_address` (`ip_address`),
  KEY `is_active` (`is_active`),
  KEY `staff_id` (`staff_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;");

// Check if table exists and add missing columns (for upgrades)
if ($CI->db->table_exists($table_name)) {
    $columns = $CI->db->list_fields($table_name);
    
    // Add staff_id column if it doesn't exist
    if (!in_array('staff_id', $columns)) {
        try {
            $CI->db->query("ALTER TABLE `" . $table_name . "` 
                ADD COLUMN `staff_id` int(11) DEFAULT NULL AFTER `description`, 
                ADD KEY `staff_id` (`staff_id`);");
        } catch (Exception $e) {
            // Column might already exist, continue
            log_activity('SecureLogin Guard Module: Error adding staff_id column - ' . $e->getMessage());
        }
    }
    
    // Ensure all required indexes exist
    $indexes = $CI->db->query("SHOW INDEXES FROM `" . $table_name . "`")->result_array();
    $index_names = array_column($indexes, 'Key_name');
    
    if (!in_array('staff_id', $index_names)) {
        try {
            $CI->db->query("ALTER TABLE `" . $table_name . "` ADD KEY `staff_id` (`staff_id`);");
        } catch (Exception $e) {
            // Index might already exist
        }
    }
}

/**
 * Add default options (only if they don't exist)
 */
if (!get_option('securelogin_guard_enable_ip_whitelist')) {
    add_option('securelogin_guard_enable_ip_whitelist', '0');
}

if (!get_option('securelogin_guard_bypass_admin')) {
    add_option('securelogin_guard_bypass_admin', '1');
}


