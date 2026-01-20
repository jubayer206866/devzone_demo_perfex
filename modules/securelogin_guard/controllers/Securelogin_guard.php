<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Securelogin_guard extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        
        if (!has_permission('securelogin_guard', '', 'view')) {
            set_alert('danger', _l('securelogin_guard_access_denied_contact_admin'));
            log_activity('Tried to access SecureLogin Guard page without permission');
            if (isset($_SERVER['HTTP_REFERER']) && !empty($_SERVER['HTTP_REFERER'])) {
                redirect($_SERVER['HTTP_REFERER']);
            } else {
                redirect(admin_url('access_denied'));
            }
        }
        
        $this->load->model('Securelogin_guard_model', 'securelogin_guard_model');
    }

    /**
     * Main management page
     */
    public function manage()
    {
        $data['title'] = _l('ip_whitelist');
        $data['is_admin'] = is_admin();
        
        $this->load->view('securelogin_guard/manage', $data);
    }

    /**
     * Table data endpoint for datatables (server-side processing)
     */
    public function table()
    {
        $this->app->get_table_data(module_views_path('securelogin_guard', 'tables/securelogin_guard'));
    }


    /**
     * Get form data for add modal (AJAX)
     */
    public function get_add_form()
    {
        if (!has_permission('securelogin_guard', '', 'create')) {
            echo json_encode(['success' => false, 'message' => _l('securelogin_guard_access_denied_contact_admin')]);
            return;
        }
        
        $this->load->model('staff_model');
        
        $has_create_permission = has_permission('securelogin_guard', '', 'create');
        $data['current_ip'] = $this->input->ip_address();
        $data['has_create_permission'] = $has_create_permission;
        
        // If user has create permission, show all staff options (including "All Staff")
        if ($has_create_permission) {
            // Get all active staff members EXCEPT admins (admins are always allowed)
            $data['staff_members'] = $this->staff_model->get('', ['active' => 1, 'admin' => 0]);
        } else {
            $data['staff_members'] = [];
        }
        
        $html = $this->load->view('securelogin_guard/modal_add', $data, true);
        echo json_encode(['success' => true, 'html' => $html]);
    }

    /**
     * Get form data for edit modal (AJAX)
     */
    public function get_edit_form($id)
    {
        if (!has_permission('securelogin_guard', '', 'edit')) {
            echo json_encode(['success' => false, 'message' => _l('securelogin_guard_access_denied_contact_admin')]);
            return;
        }
        
        $is_admin = is_admin();
        $current_staff_id = get_staff_user_id();
        
        // Get current record first to check ownership
        $current = $this->securelogin_guard_model->get($id);
        
        if (!$current) {
            echo json_encode(['success' => false, 'message' => _l('record_not_found')]);
            return;
        }
        
        // Non-admins can only edit their own entries
        if (!$is_admin) {
            $assigned_staff = isset($current->assigned_staff) && is_array($current->assigned_staff) ? $current->assigned_staff : [];
            if (!in_array($current_staff_id, $assigned_staff)) {
                echo json_encode(['success' => false, 'message' => _l('securelogin_guard_access_denied_contact_admin')]);
                return;
            }
        }
        
        $this->load->model('staff_model');
        
        $has_edit_permission = has_permission('securelogin_guard', '', 'edit');
        $data['whitelist'] = $current;
        $data['has_edit_permission'] = $has_edit_permission;
        
        // If user has edit permission, show all staff options (including "All Staff")
        if ($has_edit_permission) {
            // Get all active staff members EXCEPT admins (admins are always allowed)
            $data['staff_members'] = $this->staff_model->get('', ['active' => 1, 'admin' => 0]);
        } else {
            $data['staff_members'] = [];
        }
        
        // Get assigned staff IDs for this IP
        $data['assigned_staff_ids'] = isset($current->assigned_staff) ? $current->assigned_staff : [];
        
        $html = $this->load->view('securelogin_guard/modal_edit', $data, true);
        echo json_encode(['success' => true, 'html' => $html]);
    }

    /**
     * Add new IP address (AJAX)
     */
    public function add()
    {
        if (!has_permission('securelogin_guard', '', 'create')) {
            if ($this->input->is_ajax_request()) {
                echo json_encode(['success' => false, 'message' => _l('securelogin_guard_access_denied_contact_admin')]);
                return;
            }
            set_alert('danger', _l('securelogin_guard_access_denied_contact_admin'));
            log_activity('Tried to add IP address without permission');
            if (isset($_SERVER['HTTP_REFERER']) && !empty($_SERVER['HTTP_REFERER'])) {
                redirect($_SERVER['HTTP_REFERER']);
            } else {
                redirect(admin_url('access_denied'));
            }
        }
        
        if ($this->input->post()) {
            $ip_address = trim($this->input->post('ip_address'));
            $description = $this->input->post('description');
            // Sanitize and limit description to prevent large payloads / HTML storage
            if ($description !== null) {
                $description = mb_substr(strip_tags(trim($description)), 0, 500);
            }
            
            $is_admin = is_admin();
            $current_staff_id = get_staff_user_id();
            
            // Get staff IDs (multiple selection)
            $staff_ids = $this->input->post('staff_ids');
            if (!is_array($staff_ids)) {
                $staff_ids = [];
            }
            
            // Filter and validate staff IDs
            $valid_staff_ids = [];
            $has_all_staff = false;
            
            // Check if "All Staff" is selected (empty value in array)
            if (in_array('', $staff_ids) || in_array(null, $staff_ids) || in_array('0', $staff_ids)) {
                $has_all_staff = true;
            }
            
            if (!empty($staff_ids)) {
                $this->load->model('staff_model');
                foreach ($staff_ids as $staff_id) {
                    // Skip empty values (All Staff option)
                    if ($staff_id === '' || $staff_id === null || $staff_id === '0' || $staff_id === 0) {
                        continue;
                    }
                    $staff_id = (int)$staff_id;
                    if ($staff_id > 0) {
                        // Verify staff exists and is not admin
                        $staff = $this->staff_model->get($staff_id);
                        if ($staff && (!isset($staff->admin) || $staff->admin != 1)) {
                            $valid_staff_ids[] = $staff_id;
                        }
                    }
                }
            }
            
            // If "All Staff" is selected, get all non-admin staff
            if ($has_all_staff) {
                $this->load->model('staff_model');
                $all_staff = $this->staff_model->get('', ['active' => 1, 'admin' => 0]);
                $valid_staff_ids = array_column($all_staff, 'staffid');
            }
            
            // Validate that at least one staff is selected (mandatory field)
            if (empty($valid_staff_ids) && !$has_all_staff) {
                if ($this->input->is_ajax_request()) {
                    echo json_encode(['success' => false, 'message' => _l('please_select_at_least_one_staff')]);
                    return;
                }
                set_alert('danger', _l('please_select_at_least_one_staff'));
                redirect(admin_url('securelogin_guard/add'));
                return;
            }
            
            // Validate IP address
            if (empty($ip_address) || !$this->validate_ip($ip_address)) {
                if ($this->input->is_ajax_request()) {
                    echo json_encode(['success' => false, 'message' => _l('invalid_ip_address')]);
                    return;
                }
                set_alert('danger', _l('invalid_ip_address'));
                redirect(admin_url('securelogin_guard/add'));
                return;
            }
            
            // Normalize IP address (trim and ensure consistent format)
            $ip_address = trim($ip_address);
            
            // Check if IP already exists
            if ($this->securelogin_guard_model->ip_exists($ip_address)) {
                if ($this->input->is_ajax_request()) {
                    echo json_encode(['success' => false, 'message' => _l('ip_address_already_exists')]);
                    return;
                }
                set_alert('danger', _l('ip_address_already_exists'));
                redirect(admin_url('securelogin_guard/add'));
                return;
            }
            
            // Check if IP already exists for any of the selected staff
            foreach ($valid_staff_ids as $staff_id) {
                if ($this->securelogin_guard_model->ip_exists_for_staff($ip_address, $staff_id)) {
                    if ($this->input->is_ajax_request()) {
                        echo json_encode(['success' => false, 'message' => _l('ip_address_already_exists_for_staff')]);
                        return;
                    }
                    set_alert('danger', _l('ip_address_already_exists_for_staff'));
                    redirect(admin_url('securelogin_guard/add'));
                    return;
                }
            }
            
            $data = [
                'ip_address' => $ip_address,
                'description' => $description,
                'staff_ids' => $valid_staff_ids, // Pass as array for model
                'is_active' => 1,
                'date_created' => date('Y-m-d H:i:s'),
                'created_by' => get_staff_user_id(),
            ];
            
            $id = $this->securelogin_guard_model->add($data);
            
            if ($id) {
                $staff_names = [];
                foreach ($valid_staff_ids as $staff_id) {
                    $staff_names[] = get_staff_full_name($staff_id);
                }
                $staff_name = !empty($staff_names) ? implode(', ', $staff_names) : 'None';
                log_activity('IP Address Added to Whitelist [IP: ' . $ip_address . ', Staff: ' . $staff_name . ']');
                
                if ($this->input->is_ajax_request()) {
                    echo json_encode(['success' => true, 'message' => _l('ip_address_added_successfully')]);
                    return;
                }
                set_alert('success', _l('ip_address_added_successfully'));
            } else {
                if ($this->input->is_ajax_request()) {
                    echo json_encode(['success' => false, 'message' => _l('error_adding_ip_address')]);
                    return;
                }
                set_alert('danger', _l('error_adding_ip_address'));
            }
            
            if (!$this->input->is_ajax_request()) {
                redirect(admin_url('securelogin_guard/manage'));
            }
        } else {
            // GET request - show form (fallback for non-AJAX)
            $this->load->model('staff_model');
            
            $has_create_permission = has_permission('securelogin_guard', '', 'create');
            $data['title'] = _l('add_ip_address');
            $data['current_ip'] = $this->input->ip_address();
            $data['has_create_permission'] = $has_create_permission;
            
            // If user has create permission, show all staff options (including "All Staff")
            if ($has_create_permission) {
                $data['staff_members'] = $this->staff_model->get('', ['active' => 1, 'admin' => 0]);
            } else {
                $data['staff_members'] = [];
            }
            
            $this->load->view('securelogin_guard/add', $data);
        }
    }

    /**
     * Edit IP address (AJAX)
     */
    public function edit($id)
    {
        if (!has_permission('securelogin_guard', '', 'edit')) {
            if ($this->input->is_ajax_request()) {
                echo json_encode(['success' => false, 'message' => _l('securelogin_guard_access_denied_contact_admin')]);
                return;
            }
            set_alert('danger', _l('securelogin_guard_access_denied_contact_admin'));
            log_activity('Tried to edit IP address without permission');
            if (isset($_SERVER['HTTP_REFERER']) && !empty($_SERVER['HTTP_REFERER'])) {
                redirect($_SERVER['HTTP_REFERER']);
            } else {
                redirect(admin_url('access_denied'));
            }
        }
        
        $is_admin = is_admin();
        $current_staff_id = get_staff_user_id();
        
        // Get current record first to check ownership
        $current = $this->securelogin_guard_model->get($id);
        
        if (!$current) {
            if ($this->input->is_ajax_request()) {
                echo json_encode(['success' => false, 'message' => _l('record_not_found')]);
                return;
            }
            show_404();
            return;
        }
        
        // Non-admins can only edit their own entries
        if (!$is_admin) {
            // Check if this entry belongs to the current user
            $assigned_staff = isset($current->assigned_staff) && is_array($current->assigned_staff) ? $current->assigned_staff : [];
            if (!in_array($current_staff_id, $assigned_staff)) {
                if ($this->input->is_ajax_request()) {
                    echo json_encode(['success' => false, 'message' => _l('securelogin_guard_access_denied_contact_admin')]);
                    return;
                }
                set_alert('danger', _l('securelogin_guard_access_denied_contact_admin'));
                log_activity('Tried to edit IP address that does not belong to user');
                if (isset($_SERVER['HTTP_REFERER']) && !empty($_SERVER['HTTP_REFERER'])) {
                    redirect($_SERVER['HTTP_REFERER']);
                } else {
                    redirect(admin_url('access_denied'));
                }
                return;
            }
        }
        
        if ($this->input->post()) {
            $ip_address = trim($this->input->post('ip_address'));
            $description = $this->input->post('description');
            if ($description !== null) {
                $description = mb_substr(strip_tags(trim($description)), 0, 500);
            }
            
            // Get staff IDs (multiple selection)
            $staff_ids = $this->input->post('staff_ids');
            if (!is_array($staff_ids)) {
                $staff_ids = [];
            }
            
            // Filter and validate staff IDs
            $valid_staff_ids = [];
            $has_all_staff = false;
            
            // Check if "All Staff" is selected (empty value in array)
            if (in_array('', $staff_ids) || in_array(null, $staff_ids) || in_array('0', $staff_ids)) {
                $has_all_staff = true;
            }
            
            if (!empty($staff_ids)) {
                $this->load->model('staff_model');
                foreach ($staff_ids as $staff_id) {
                    // Skip empty values (All Staff option)
                    if ($staff_id === '' || $staff_id === null || $staff_id === '0' || $staff_id === 0) {
                        continue;
                    }
                    $staff_id = (int)$staff_id;
                    if ($staff_id > 0) {
                        // Verify staff exists and is not admin
                        $staff = $this->staff_model->get($staff_id);
                        if ($staff && (!isset($staff->admin) || $staff->admin != 1)) {
                            $valid_staff_ids[] = $staff_id;
                        }
                    }
                }
            }
            
            // If "All Staff" is selected, get all non-admin staff
            if ($has_all_staff) {
                $this->load->model('staff_model');
                $all_staff = $this->staff_model->get('', ['active' => 1, 'admin' => 0]);
                $valid_staff_ids = array_column($all_staff, 'staffid');
            }
            
            // Validate that at least one staff is selected (mandatory field)
            if (empty($valid_staff_ids) && !$has_all_staff) {
                if ($this->input->is_ajax_request()) {
                    echo json_encode(['success' => false, 'message' => _l('please_select_at_least_one_staff')]);
                    return;
                }
                set_alert('danger', _l('please_select_at_least_one_staff'));
                redirect(admin_url('securelogin_guard/edit/' . $id));
                return;
            }
            
            // Non-admins can only edit their own entries
            if (!$is_admin) {
                // For non-admins, ensure they can only assign to themselves
                $valid_staff_ids = [$current_staff_id];
                $is_active = $current->is_active;
            } else {
                $is_active = $this->input->post('is_active') ? 1 : 0;
            }
            
            // Validate IP address
            if (empty($ip_address) || !$this->validate_ip($ip_address)) {
                if ($this->input->is_ajax_request()) {
                    echo json_encode(['success' => false, 'message' => _l('invalid_ip_address')]);
                    return;
                }
                set_alert('danger', _l('invalid_ip_address'));
                redirect(admin_url('securelogin_guard/edit/' . $id));
                return;
            }
            
            // Normalize IP address
            $ip_address = trim($ip_address);
            
            // Check if IP already exists (excluding current record)
            if ($this->securelogin_guard_model->ip_exists($ip_address, $id)) {
                if ($this->input->is_ajax_request()) {
                    echo json_encode(['success' => false, 'message' => _l('ip_address_already_exists')]);
                    return;
                }
                set_alert('danger', _l('ip_address_already_exists'));
                redirect(admin_url('securelogin_guard/edit/' . $id));
                return;
            }
            
            // Check if IP already exists for any of the selected staff (excluding current record)
            foreach ($valid_staff_ids as $staff_id) {
                if ($this->securelogin_guard_model->ip_exists_for_staff($ip_address, $staff_id, $id)) {
                    if ($this->input->is_ajax_request()) {
                        echo json_encode(['success' => false, 'message' => _l('ip_address_already_exists_for_staff')]);
                        return;
                    }
                    set_alert('danger', _l('ip_address_already_exists_for_staff'));
                    redirect(admin_url('securelogin_guard/edit/' . $id));
                    return;
                }
            }
            
            $data = [
                'ip_address' => $ip_address,
                'description' => $description,
                'staff_ids' => $valid_staff_ids, // Pass as array for model
                'is_active' => $is_active,
                'date_modified' => date('Y-m-d H:i:s'),
            ];
            
            $success = $this->securelogin_guard_model->update($id, $data);
            
            if ($success) {
                $staff_names = [];
                foreach ($valid_staff_ids as $staff_id) {
                    $staff_names[] = get_staff_full_name($staff_id);
                }
                $staff_name = !empty($staff_names) ? implode(', ', $staff_names) : 'None';
                log_activity('IP Address Updated in Whitelist [IP: ' . $ip_address . ', Staff: ' . $staff_name . ']');
                
                if ($this->input->is_ajax_request()) {
                    echo json_encode(['success' => true, 'message' => _l('ip_address_updated_successfully')]);
                    return;
                }
                set_alert('success', _l('ip_address_updated_successfully'));
            } else {
                if ($this->input->is_ajax_request()) {
                    echo json_encode(['success' => false, 'message' => _l('error_updating_ip_address')]);
                    return;
                }
                set_alert('danger', _l('error_updating_ip_address'));
            }
            
            if (!$this->input->is_ajax_request()) {
                redirect(admin_url('securelogin_guard/manage'));
            }
        } else {
            // GET request - show form (fallback for non-AJAX)
            $this->load->model('staff_model');
            
            $has_edit_permission = has_permission('securelogin_guard', '', 'edit');
            $data['title'] = _l('edit_ip_address');
            $data['whitelist'] = $current;
            $data['has_edit_permission'] = $has_edit_permission;
            
            // If user has edit permission, show all staff options (including "All Staff")
            if ($has_edit_permission) {
                $data['staff_members'] = $this->staff_model->get('', ['active' => 1, 'admin' => 0]);
            } else {
                $data['staff_members'] = [];
            }
            
            // Get assigned staff IDs for this IP
            $data['assigned_staff_ids'] = isset($current->assigned_staff) ? $current->assigned_staff : [];
            
            $this->load->view('securelogin_guard/edit', $data);
        }
    }

    /**
     * Delete IP address
     */
    public function delete($id)
    {
        if (!has_permission('securelogin_guard', '', 'delete')) {
            set_alert('danger', _l('securelogin_guard_access_denied_contact_admin'));
            log_activity('Tried to delete IP address without permission');
            if (isset($_SERVER['HTTP_REFERER']) && !empty($_SERVER['HTTP_REFERER'])) {
                redirect($_SERVER['HTTP_REFERER']);
            } else {
                redirect(admin_url('access_denied'));
            }
        }
        if (!$id) {
            redirect(admin_url('securelogin_guard/manage'));
        }

        // Enforce POST for state-changing operation
        if (strtolower($this->input->method()) !== 'post') {
            show_error('Invalid request method', 405);
        }
        
        $whitelist = $this->securelogin_guard_model->get($id);
        
        if (!$whitelist) {
            show_404();
        }
        
        // Non-admins can only delete their own entries
        if (!is_admin()) {
            $current_staff_id = get_staff_user_id();
            $assigned_staff = isset($whitelist->assigned_staff) && is_array($whitelist->assigned_staff) ? $whitelist->assigned_staff : [];
            if (!in_array($current_staff_id, $assigned_staff)) {
                set_alert('danger', _l('securelogin_guard_access_denied_contact_admin'));
                log_activity('Tried to delete IP address that does not belong to user');
                if (isset($_SERVER['HTTP_REFERER']) && !empty($_SERVER['HTTP_REFERER'])) {
                    redirect($_SERVER['HTTP_REFERER']);
                } else {
                    redirect(admin_url('access_denied'));
                }
                return;
            }
        }
        
        $success = $this->securelogin_guard_model->delete($id);
        
        if ($success) {
            log_activity('IP Address Removed from Whitelist [IP: ' . $whitelist->ip_address . ']');
            set_alert('success', _l('ip_address_deleted_successfully'));
        } else {
            set_alert('danger', _l('error_deleting_ip_address'));
        }
        
        redirect(admin_url('securelogin_guard/manage'));
    }

    /**
     * Change IP address active status (for onoffswitch)
     */
    public function change_ip_status($id, $status)
    {
        if ($this->input->is_ajax_request()) {
            if (!has_permission('securelogin_guard', '', 'edit')) {
                echo json_encode(['success' => false, 'message' => _l('securelogin_guard_access_denied_contact_admin')]);
                return;
            }

            $whitelist = $this->securelogin_guard_model->get($id);
            
            if (!$whitelist) {
                echo json_encode(['success' => false, 'message' => _l('record_not_found')]);
                return;
            }
            
            // Non-admins can only toggle their own entries
            if (!is_admin()) {
                $current_staff_id = get_staff_user_id();
                $assigned_staff = isset($whitelist->assigned_staff) && is_array($whitelist->assigned_staff) ? $whitelist->assigned_staff : [];
                if (!in_array($current_staff_id, $assigned_staff)) {
                    echo json_encode(['success' => false, 'message' => _l('securelogin_guard_access_denied_contact_admin')]);
                    return;
                }
            }
            
            $status = (int)$status; // Ensure status is 0 or 1
            $success = $this->securelogin_guard_model->update($id, ['is_active' => $status]);
            
            if ($success) {
                log_activity('IP Address Status Changed [IP: ' . $whitelist->ip_address . ', Status: ' . ($status ? 'Active' : 'Inactive') . ']');
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'message' => _l('error_updating_ip_address')]);
            }
        }
    }

    /**
     * Validate IP address
     */
    private function validate_ip($ip)
    {
        if (empty($ip)) {
            return false;
        }
        
        // Trim whitespace
        $ip = trim($ip);
        
        // Support both IPv4 and IPv6 (without flags, filter_var accepts both)
        if (filter_var($ip, FILTER_VALIDATE_IP)) {
            return true;
        }
        
        // Support CIDR notation (e.g., 192.168.1.0/24 or 2001:db8::/32)
        if (strpos($ip, '/') !== false) {
            $parts = explode('/', $ip, 2);
            if (count($parts) == 2) {
                $ip_part = trim($parts[0]);
                $cidr = trim($parts[1]);
                
                // Validate IP part (accepts both IPv4 and IPv6)
                if (filter_var($ip_part, FILTER_VALIDATE_IP)) {
                    // Validate CIDR mask
                    if (is_numeric($cidr)) {
                        $max_mask = (strpos($ip_part, ':') !== false) ? 128 : 32;
                        if ($cidr >= 0 && $cidr <= $max_mask) {
                            return true;
                        }
                    }
                }
            }
        }
        
        return false;
    }

}

