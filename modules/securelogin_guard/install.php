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
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `date_created` datetime NOT NULL,
  `date_modified` datetime DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ip_address` (`ip_address`),
  KEY `is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;");

/**
 * Create table for IP whitelist staff assignments (many-to-many relationship)
 */
$staff_table_name = db_prefix() . 'securelogin_guard_whitelist_staffs';

// Create table if it doesn't exist
$CI->db->query("CREATE TABLE IF NOT EXISTS `" . $staff_table_name . "` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `whitelist_id` int(11) NOT NULL,
  `staff_id` int(11) NOT NULL,
  `date_created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_whitelist_staff` (`whitelist_id`, `staff_id`),
  KEY `whitelist_id` (`whitelist_id`),
  KEY `staff_id` (`staff_id`),
  CONSTRAINT `" . db_prefix() . "securelogin_guard_whitelist_staffs_ibfk_1` FOREIGN KEY (`whitelist_id`) REFERENCES `" . db_prefix() . "securelogin_guard_whitelist` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `" . db_prefix() . "securelogin_guard_whitelist_staffs_ibfk_2` FOREIGN KEY (`staff_id`) REFERENCES `" . db_prefix() . "staff` (`staffid`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;");

