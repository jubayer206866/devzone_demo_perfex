<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Securelogin_guard_model extends App_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->table = db_prefix() . 'securelogin_guard_whitelist';
    }

    /**
     * Get all whitelisted IPs with filters
     */
    public function get_all($filters = [])
    {
        // Filter by status (is_active)
        if (isset($filters['status']) && !empty($filters['status'])) {
            if (is_array($filters['status'])) {
                $this->db->where_in('is_active', $filters['status']);
            } else {
                $this->db->where('is_active', $filters['status']);
            }
        }
        
        // Filter by IP addresses
        if (isset($filters['ip_addresses']) && !empty($filters['ip_addresses'])) {
            if (is_array($filters['ip_addresses'])) {
                $this->db->where_in('ip_address', $filters['ip_addresses']);
            } else {
                $this->db->where('ip_address', $filters['ip_addresses']);
            }
        }
        
        // Filter by staff IDs
        if (isset($filters['staff_ids']) && !empty($filters['staff_ids'])) {
            if (is_array($filters['staff_ids'])) {
                $has_all_staff = in_array('', $filters['staff_ids']) || in_array(null, $filters['staff_ids']);
                $staff_ids = array_filter($filters['staff_ids'], function($id) {
                    return $id !== '' && $id !== null;
                });
                
                if ($has_all_staff && !empty($staff_ids)) {
                    // Include both NULL staff_id (all staff) and specific staff IDs
                    $this->db->group_start();
                    $this->db->where('staff_id IS NULL', null, false);
                    $this->db->or_where_in('staff_id', $staff_ids);
                    $this->db->group_end();
                } elseif ($has_all_staff) {
                    // Only NULL staff_id (all staff)
                    $this->db->where('staff_id IS NULL', null, false);
                } else {
                    // Only specific staff IDs
                    $this->db->where_in('staff_id', $staff_ids);
                }
            } else {
                // Single staff_id (backward compatibility)
                if ($filters['staff_ids'] === '' || $filters['staff_ids'] === null) {
                    $this->db->where('staff_id IS NULL', null, false);
                } else {
                    $this->db->where('staff_id', $filters['staff_ids']);
                }
            }
        } elseif (isset($filters['staff_id']) && $filters['staff_id'] !== null) {
            // Backward compatibility - single staff_id
            $this->db->where('staff_id', $filters['staff_id']);
        }
        
        $this->db->order_by('date_created', 'DESC');
        return $this->db->get($this->table)->result();
    }

    /**
     * Get all unique IP addresses for filter dropdown
     */
    public function get_all_ips()
    {
        $this->db->select('ip_address');
        $this->db->distinct();
        $this->db->order_by('ip_address', 'ASC');
        return $this->db->get($this->table)->result();
    }

    /**
     * Get single IP record
     */
    public function get($id)
    {
        $this->db->where('id', $id);
        return $this->db->get($this->table)->row();
    }

    /**
     * Add new IP address
     */
    public function add($data)
    {
        // Normalize IP address
        if (isset($data['ip_address'])) {
            $data['ip_address'] = trim($data['ip_address']);
        }
        
        // Ensure staff_id is set to null if not provided or invalid
        if (!isset($data['staff_id']) || empty($data['staff_id']) || $data['staff_id'] === '0' || $data['staff_id'] === 0) {
            $data['staff_id'] = null;
        } else {
            $data['staff_id'] = (int)$data['staff_id'];
            if ($data['staff_id'] <= 0) {
                $data['staff_id'] = null;
            }
        }
        
        $this->db->insert($this->table, $data);
        return $this->db->insert_id();
    }

    /**
     * Update IP address
     */
    public function update($id, $data)
    {
        $this->db->where('id', $id);
        return $this->db->update($this->table, $data);
    }

    /**
     * Delete IP address
     */
    public function delete($id)
    {
        $this->db->where('id', $id);
        return $this->db->delete($this->table);
    }

    /**
     * Check if IP exists (global only - staff_id is null)
     */
    public function ip_exists($ip, $exclude_id = null)
    {
        $ip = trim($ip);
        $this->db->where('ip_address', $ip);
        $this->db->group_start();
        $this->db->where('staff_id IS NULL', null, false);
        $this->db->or_where('staff_id', 0);
        $this->db->group_end();
        if ($exclude_id) {
            $this->db->where('id !=', $exclude_id);
        }
        return $this->db->count_all_results($this->table) > 0;
    }

    

    /**
     * Check if IP exists for staff
     */
    public function ip_exists_for_staff($ip, $staff_id, $exclude_id = null)
    {
        $ip = trim($ip);
        $staff_id = (int)$staff_id;
        $this->db->where('ip_address', $ip);
        $this->db->where('staff_id', $staff_id);
        if ($exclude_id) {
            $this->db->where('id !=', $exclude_id);
        }
        return $this->db->count_all_results($this->table) > 0;
    }

    /**
     * Check if there are valid IP addresses (global or assigned to admins)
     * Returns true if there are IPs that are either:
     * - Global (staff_id IS NULL - for all staff), OR
     * - Assigned to at least one admin user
     */
    public function has_valid_ips_for_whitelist()
    {
        // Get all active IPs
        $this->db->where('is_active', 1);
        $all_ips = $this->db->get($this->table)->result();
        
        if (empty($all_ips)) {
            return false;
        }
        
        // Check if any IP is global (staff_id IS NULL)
        foreach ($all_ips as $ip) {
            if ($ip->staff_id === null || $ip->staff_id == 0) {
                return true; // Found global IP
            }
        }
        
        // Check if any IP is assigned to an admin
        $staff_ids = array_filter(array_column($all_ips, 'staff_id'));
        if (empty($staff_ids)) {
            return false;
        }
        
        // Check if any of these staff IDs are admins
        $this->db->where_in('staffid', $staff_ids);
        $this->db->where('admin', 1);
        $admin_count = $this->db->count_all_results(db_prefix() . 'staff');
        
        return $admin_count > 0;
    }
}

