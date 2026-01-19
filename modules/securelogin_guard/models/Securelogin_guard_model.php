<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Securelogin_guard_model extends App_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->table = db_prefix() . 'securelogin_guard_whitelist';
        $this->staff_table = db_prefix() . 'securelogin_guard_whitelist_staffs';
    }

    /**
     * Get all whitelisted IPs with their assigned staff
     */
    public function get_all($filters = [])
    {
        // Filter by status (is_active)
        if (isset($filters['status']) && !empty($filters['status'])) {
            if (is_array($filters['status'])) {
                $this->db->where_in('w.is_active', $filters['status']);
            } else {
                $this->db->where('w.is_active', $filters['status']);
            }
        }
        
        // Filter by IP addresses
        if (isset($filters['ip_addresses']) && !empty($filters['ip_addresses'])) {
            if (is_array($filters['ip_addresses'])) {
                $this->db->where_in('w.ip_address', $filters['ip_addresses']);
            } else {
                $this->db->where('w.ip_address', $filters['ip_addresses']);
            }
        }
        
        // Filter by staff IDs (check in staff assignments table)
        if (isset($filters['staff_ids']) && !empty($filters['staff_ids'])) {
            if (is_array($filters['staff_ids'])) {
                $staff_ids = array_filter($filters['staff_ids'], function($id) {
                    return $id !== '' && $id !== null;
                });
                if (!empty($staff_ids)) {
                    $this->db->where_in('ws.staff_id', $staff_ids);
                }
            } else {
                $this->db->where('ws.staff_id', $filters['staff_ids']);
            }
        } elseif (isset($filters['staff_id']) && $filters['staff_id'] !== null) {
            $this->db->where('ws.staff_id', $filters['staff_id']);
        }
        
        // Join with staff assignments table
        $this->db->select('w.*, GROUP_CONCAT(ws.staff_id) as assigned_staff_ids');
        $this->db->from($this->table . ' w');
        $this->db->join($this->staff_table . ' ws', 'w.id = ws.whitelist_id', 'left');
        $this->db->group_by('w.id');
        $this->db->order_by('w.date_created', 'DESC');
        
        $results = $this->db->get()->result();
        
        // Process results to add staff array
        foreach ($results as $result) {
            if (!empty($result->assigned_staff_ids) && $result->assigned_staff_ids !== null) {
                $result->assigned_staff = array_map('intval', explode(',', $result->assigned_staff_ids));
            } else {
                $result->assigned_staff = [];
            }
            unset($result->assigned_staff_ids);
        }
        
        return $results;
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
     * Get single IP record with assigned staff
     */
    public function get($id)
    {
        $this->db->where('w.id', $id);
        $this->db->select('w.*, GROUP_CONCAT(ws.staff_id) as assigned_staff_ids');
        $this->db->from($this->table . ' w');
        $this->db->join($this->staff_table . ' ws', 'w.id = ws.whitelist_id', 'left');
        $this->db->group_by('w.id');
        
        $result = $this->db->get()->row();
        
        if ($result) {
            if (!empty($result->assigned_staff_ids) && $result->assigned_staff_ids !== null) {
                $result->assigned_staff = array_map('intval', explode(',', $result->assigned_staff_ids));
            } else {
                $result->assigned_staff = [];
            }
            unset($result->assigned_staff_ids);
        }
        
        return $result;
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
        
        // Remove staff_ids from data (will be handled separately)
        $staff_ids = isset($data['staff_ids']) ? $data['staff_ids'] : [];
        unset($data['staff_ids']);
        
        // Remove staff_id from data (old column, not used anymore)
        unset($data['staff_id']);
        
        $this->db->insert($this->table, $data);
        $whitelist_id = $this->db->insert_id();
        
        // Add staff assignments
        if ($whitelist_id && !empty($staff_ids) && is_array($staff_ids)) {
            $this->assign_staff($whitelist_id, $staff_ids);
        }
        
        return $whitelist_id;
    }

    /**
     * Update IP address
     */
    public function update($id, $data)
    {
        // Remove staff_ids from data (will be handled separately)
        $staff_ids = isset($data['staff_ids']) ? $data['staff_ids'] : [];
        unset($data['staff_ids']);
        
        // Remove staff_id from data (old column, not used anymore)
        unset($data['staff_id']);
        
        $this->db->where('id', $id);
        $success = $this->db->update($this->table, $data);
        
        // Update staff assignments
        if ($success) {
            $this->update_staff_assignments($id, $staff_ids);
        }
        
        return $success;
    }

    /**
     * Delete IP address and its staff assignments
     */
    public function delete($id)
    {
        // Delete staff assignments first
        $this->db->where('whitelist_id', $id);
        $this->db->delete($this->staff_table);
        
        // Delete IP record
        $this->db->where('id', $id);
        return $this->db->delete($this->table);
    }

    /**
     * Assign staff to IP whitelist
     */
    public function assign_staff($whitelist_id, $staff_ids)
    {
        if (empty($staff_ids) || !is_array($staff_ids)) {
            return false;
        }
        
        $whitelist_id = (int)$whitelist_id;
        $insert_data = [];
        
        foreach ($staff_ids as $staff_id) {
            $staff_id = (int)$staff_id;
            if ($staff_id > 0) {
                $insert_data[] = [
                    'whitelist_id' => $whitelist_id,
                    'staff_id' => $staff_id,
                    'date_created' => date('Y-m-d H:i:s')
                ];
            }
        }
        
        if (!empty($insert_data)) {
            return $this->db->insert_batch($this->staff_table, $insert_data);
        }
        
        return false;
    }

    /**
     * Update staff assignments for an IP
     */
    public function update_staff_assignments($whitelist_id, $staff_ids)
    {
        // Delete existing assignments
        $this->db->where('whitelist_id', $whitelist_id);
        $this->db->delete($this->staff_table);
        
        // Add new assignments
        if (!empty($staff_ids) && is_array($staff_ids)) {
            return $this->assign_staff($whitelist_id, $staff_ids);
        }
        
        return true;
    }

    /**
     * Get staff assigned to an IP
     */
    public function get_assigned_staff($whitelist_id)
    {
        $this->db->where('whitelist_id', $whitelist_id);
        $this->db->select('staff_id');
        $results = $this->db->get($this->staff_table)->result();
        
        return array_column($results, 'staff_id');
    }

    /**
     * Check if IP exists (any staff assignment)
     */
    public function ip_exists($ip, $exclude_id = null)
    {
        $ip = trim($ip);
        $this->db->where('ip_address', $ip);
        if ($exclude_id) {
            $this->db->where('id !=', $exclude_id);
        }
        return $this->db->count_all_results($this->table) > 0;
    }

    /**
     * Check if IP exists for specific staff
     */
    public function ip_exists_for_staff($ip, $staff_id, $exclude_id = null)
    {
        $ip = trim($ip);
        $staff_id = (int)$staff_id;
        
        $this->db->where('w.ip_address', $ip);
        $this->db->where('ws.staff_id', $staff_id);
        $this->db->from($this->table . ' w');
        $this->db->join($this->staff_table . ' ws', 'w.id = ws.whitelist_id', 'inner');
        
        if ($exclude_id) {
            $this->db->where('w.id !=', $exclude_id);
        }
        
        return $this->db->count_all_results() > 0;
    }

    /**
     * Get active IPs for a specific staff member
     */
    public function get_ips_for_staff($staff_id)
    {
        $this->db->where('w.is_active', 1);
        $this->db->where('ws.staff_id', $staff_id);
        $this->db->select('w.*');
        $this->db->from($this->table . ' w');
        $this->db->join($this->staff_table . ' ws', 'w.id = ws.whitelist_id', 'inner');
        $this->db->group_by('w.id');
        
        return $this->db->get()->result();
    }

    /**
     * Check if there are any IP whitelist entries
     */
    public function has_any_ips()
    {
        return $this->db->count_all_results($this->table) > 0;
    }
}
