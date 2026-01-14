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
        // Only admins can change settings
        if ($this->input->post() && is_admin()) {
            $this->handle_manage_post();
        }
        
        $this->load->model('staff_model');
        
        $is_admin = is_admin();
        $current_staff_id = get_staff_user_id();
        
        // Build filters array
        $filters = [];
        
        // For non-admins, only show their own entries
        if (!$is_admin) {
            $filters['staff_id'] = $current_staff_id;
        } else {
            // Get filter parameters
            $filter_status = $this->input->get('filter_status');
            $filter_ip = $this->input->get('filter_ip');
            $filter_staff_array = $this->input->get('filter_staff');
            
            // Status filter
            if (!empty($filter_status)) {
                $filters['status'] = is_array($filter_status) ? $filter_status : [$filter_status];
            }
            
            // IP address filter
            if (!empty($filter_ip)) {
                $filters['ip_addresses'] = is_array($filter_ip) ? $filter_ip : [$filter_ip];
            }
            
            // Staff filter
            if (!empty($filter_staff_array)) {
                $filters['staff_ids'] = is_array($filter_staff_array) ? $filter_staff_array : [$filter_staff_array];
            }
            
            // Backward compatibility - single staff_id
            $filter_staff = $this->input->get('staff_id');
            if (!empty($filter_staff) && empty($filter_staff_array)) {
                $filters['staff_id'] = $filter_staff;
            }
        }
        
        $data['title'] = _l('ip_whitelist');
        $data['whitelist'] = $this->securelogin_guard_model->get_all($filters);
        $data['enable_whitelist'] = get_option('securelogin_guard_enable_ip_whitelist');
        $data['bypass_admin'] = get_option('securelogin_guard_bypass_admin');
        $data['current_ip'] = $this->input->ip_address();
        $data['is_admin'] = $is_admin;
        $data['current_staff_id'] = $current_staff_id;
        
        // Only admins can see staff list and filter
        if ($is_admin) {
            $data['staff_members'] = $this->staff_model->get('', ['active' => 1]);
            $data['all_ips'] = $this->securelogin_guard_model->get_all_ips();
            
            // Pass filter values to view
            $data['filter_status'] = $this->input->get('filter_status');
            $data['filter_ip'] = $this->input->get('filter_ip');
            $data['filter_staff_array'] = $this->input->get('filter_staff');
            $data['filter_staff'] = $this->input->get('staff_id'); // Backward compatibility
        } else {
            $data['staff_members'] = [];
            $data['all_ips'] = [];
            $data['filter_staff'] = null;
        }
        
        // If AJAX request, return only table rows
        if ($this->input->is_ajax_request()) {
            $this->load->view('securelogin_guard/table_rows', $data);
            return;
        }
        
        $this->load->view('securelogin_guard/manage', $data);
    }

    /**
     * Get filtered table rows via AJAX
     */
    public function get_filtered_rows()
    {
        if (!has_permission('securelogin_guard', '', 'view')) {
            echo json_encode(['success' => false, 'message' => _l('securelogin_guard_access_denied_contact_admin')]);
            return;
        }
        
        $this->load->model('staff_model');
        
        $is_admin = is_admin();
        $current_staff_id = get_staff_user_id();
        
        // Build filters array
        $filters = [];
        
        // For non-admins, only show their own entries
        if (!$is_admin) {
            $filters['staff_id'] = $current_staff_id;
        } else {
            // Get filter parameters from POST (AJAX)
            $filter_status = $this->input->post('filter_status');
            $filter_ip = $this->input->post('filter_ip');
            $filter_staff_array = $this->input->post('filter_staff');
            
            // Status filter
            if (!empty($filter_status)) {
                $filters['status'] = is_array($filter_status) ? $filter_status : [$filter_status];
            }
            
            // IP address filter
            if (!empty($filter_ip)) {
                $filters['ip_addresses'] = is_array($filter_ip) ? $filter_ip : [$filter_ip];
            }
            
            // Staff filter
            if (!empty($filter_staff_array)) {
                $filters['staff_ids'] = is_array($filter_staff_array) ? $filter_staff_array : [$filter_staff_array];
            }
        }
        
        $data['whitelist'] = $this->securelogin_guard_model->get_all($filters);
        $data['is_admin'] = $is_admin;
        
        $this->load->view('securelogin_guard/table_rows', $data);
    }

    /**
     * Get settings form data (AJAX)
     */
    public function get_settings_form()
    {
        if (!is_admin()) {
            echo json_encode(['success' => false, 'message' => _l('securelogin_guard_access_denied_contact_admin')]);
            return;
        }
        
        $data['enable_whitelist'] = get_option('securelogin_guard_enable_ip_whitelist');
        $data['bypass_admin'] = get_option('securelogin_guard_bypass_admin');
        
        // Check if there are valid IPs (global or assigned to admins)
        $data['has_valid_ips'] = $this->securelogin_guard_model->has_valid_ips_for_whitelist();
        
        $html = $this->load->view('securelogin_guard/modal_settings', $data, true);
        echo json_encode(['success' => true, 'html' => $html, 'has_valid_ips' => $data['has_valid_ips']]);
    }

    /**
     * Update settings (AJAX)
     */
    public function update_settings()
    {
        if (!is_admin()) {
            if ($this->input->is_ajax_request()) {
                echo json_encode(['success' => false, 'message' => _l('securelogin_guard_access_denied_contact_admin')]);
                return;
            }
            set_alert('danger', _l('securelogin_guard_access_denied_contact_admin'));
            redirect(admin_url('securelogin_guard/manage'));
            return;
        }
        
        if ($this->input->post()) {
            // Get checkbox values - if checkbox is checked, it sends "1", if unchecked, hidden field sends "0"
            $enable_whitelist = $this->input->post('enable_whitelist');
            $bypass_admin = $this->input->post('bypass_admin');
            
            // Ensure values are either '1' or '0'
            $enable_whitelist = ($enable_whitelist == '1') ? '1' : '0';
            $bypass_admin = ($bypass_admin == '1') ? '1' : '0';
            
            // Validation: Cannot enable whitelist if bypass_admin is not enabled and no valid IPs exist
            // Valid IPs are: global IPs (staff_id IS NULL) OR IPs assigned to admin users
            if ($enable_whitelist == '1') {
                if ($bypass_admin != '1') {
                    $has_valid_ips = $this->securelogin_guard_model->has_valid_ips_for_whitelist();
                    
                    if (!$has_valid_ips) {
                        if ($this->input->is_ajax_request()) {
                            echo json_encode(['success' => false, 'message' => _l('cannot_enable_whitelist_no_valid_ips')]);
                            return;
                        }
                        set_alert('danger', _l('cannot_enable_whitelist_no_valid_ips'));
                        redirect(admin_url('securelogin_guard/manage'));
                        return;
                    }
                }
            }
            
            update_option('securelogin_guard_enable_ip_whitelist', $enable_whitelist);
            update_option('securelogin_guard_bypass_admin', $bypass_admin);
            
            if ($this->input->is_ajax_request()) {
                echo json_encode(['success' => true, 'message' => _l('settings_updated_successfully')]);
                return;
            }
            
            $message = $enable_whitelist == '1' ? _l('ip_whitelist_enabled') : _l('ip_whitelist_disabled');
            set_alert('success', $message);
            redirect(admin_url('securelogin_guard/manage'));
        } else {
            redirect(admin_url('securelogin_guard/manage'));
        }
    }

    /**
     * Handle POST requests for manage page (legacy support)
     */
    private function handle_manage_post()
    {
        if ($this->input->post('enable_whitelist')) {
            update_option('securelogin_guard_enable_ip_whitelist', '1');
            set_alert('success', _l('ip_whitelist_enabled'));
        } elseif ($this->input->post('disable_whitelist')) {
            update_option('securelogin_guard_enable_ip_whitelist', '0');
            set_alert('success', _l('ip_whitelist_disabled'));
        }
        
        if ($this->input->post('bypass_admin')) {
            update_option('securelogin_guard_bypass_admin', '1');
        } else {
            update_option('securelogin_guard_bypass_admin', '0');
        }
        
        redirect(admin_url('securelogin_guard/manage'));
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
        
        $is_admin = is_admin();
        $data['current_ip'] = $this->input->ip_address();
        $data['is_admin'] = $is_admin;
        
        // Only admins can select staff or create global entries
        if ($is_admin) {
            $data['staff_members'] = $this->staff_model->get('', ['active' => 1]);
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
            if ($current->staff_id != $current_staff_id) {
                echo json_encode(['success' => false, 'message' => _l('securelogin_guard_access_denied_contact_admin')]);
                return;
            }
        }
        
        $this->load->model('staff_model');
        
        $data['whitelist'] = $current;
        $data['is_admin'] = $is_admin;
        
        // Only admins can select staff or create global entries
        if ($is_admin) {
            $data['staff_members'] = $this->staff_model->get('', ['active' => 1]);
        } else {
            $data['staff_members'] = [];
        }
        
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
            
            // Non-admins can only add entries for themselves
            // Admins can add global entries or for any staff
            if ($is_admin) {
                $staff_id = $this->input->post('staff_id');
                // Normalize and validate staff_id
                if (empty($staff_id) || $staff_id === '' || $staff_id === '0' || $staff_id === 0) {
                    $staff_id = null;
                } else {
                    $staff_id = (int)$staff_id;
                    // Validate staff exists
                    if ($staff_id > 0) {
                        $this->load->model('staff_model');
                        $staff = $this->staff_model->get($staff_id);
                        if (!$staff) {
                            $staff_id = null;
                        }
                    } else {
                        $staff_id = null;
                    }
                }
            } else {
                // Non-admins: force staff_id to their own ID
                $staff_id = $current_staff_id;
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
            
            // Check if IP already exists for this staff member
            if ($staff_id && $staff_id > 0) {
                // Check for duplicate for this specific staff
                if ($this->securelogin_guard_model->ip_exists_for_staff($ip_address, $staff_id)) {
                    if ($this->input->is_ajax_request()) {
                        echo json_encode(['success' => false, 'message' => _l('ip_address_already_exists_for_staff')]);
                        return;
                    }
                    set_alert('danger', _l('ip_address_already_exists_for_staff'));
                    redirect(admin_url('securelogin_guard/add'));
                    return;
                }
            } else {
                // Check for duplicate global IP
                if ($this->securelogin_guard_model->ip_exists($ip_address)) {
                    if ($this->input->is_ajax_request()) {
                        echo json_encode(['success' => false, 'message' => _l('ip_address_already_exists')]);
                        return;
                    }
                    set_alert('danger', _l('ip_address_already_exists'));
                    redirect(admin_url('securelogin_guard/add'));
                    return;
                }
            }
            
            $data = [
                'ip_address' => $ip_address,
                'description' => $description,
                'staff_id' => $staff_id,
                'is_active' => 1,
                'date_created' => date('Y-m-d H:i:s'),
                'created_by' => get_staff_user_id(),
            ];
            
            $id = $this->securelogin_guard_model->add($data);
            
            if ($id) {
                $staff_name = $staff_id ? get_staff_full_name($staff_id) : _l('all_staff');
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
            
            $is_admin = is_admin();
            $data['title'] = _l('add_ip_address');
            $data['current_ip'] = $this->input->ip_address();
            $data['is_admin'] = $is_admin;
            
            // Only admins can select staff or create global entries
            if ($is_admin) {
                $data['staff_members'] = $this->staff_model->get('', ['active' => 1]);
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
            if ($current->staff_id != $current_staff_id) {
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
            
            // Non-admins can only edit their own entries
            if ($is_admin) {
                $staff_id = $this->input->post('staff_id');
                $is_active = $this->input->post('is_active') ? 1 : 0;
                
                // Normalize and validate staff_id
                if (empty($staff_id) || $staff_id === '' || $staff_id === '0' || $staff_id === 0) {
                    $staff_id = null;
                } else {
                    $staff_id = (int)$staff_id;
                    // Validate staff exists
                    if ($staff_id > 0) {
                        $this->load->model('staff_model');
                        $staff = $this->staff_model->get($staff_id);
                        if (!$staff) {
                            $staff_id = null;
                        }
                    } else {
                        $staff_id = null;
                    }
                }
            } else {
                // Non-admins: force staff_id to their own ID and keep current active status
                $staff_id = $current_staff_id;
                $is_active = $current->is_active;
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
            
            // Check if IP already exists for this staff member (excluding current record)
            if ($staff_id && $staff_id > 0) {
                if ($this->securelogin_guard_model->ip_exists_for_staff($ip_address, $staff_id, $id)) {
                    if ($this->input->is_ajax_request()) {
                        echo json_encode(['success' => false, 'message' => _l('ip_address_already_exists_for_staff')]);
                        return;
                    }
                    set_alert('danger', _l('ip_address_already_exists_for_staff'));
                    redirect(admin_url('securelogin_guard/edit/' . $id));
                    return;
                }
            } else {
                if ($this->securelogin_guard_model->ip_exists($ip_address, $id)) {
                    if ($this->input->is_ajax_request()) {
                        echo json_encode(['success' => false, 'message' => _l('ip_address_already_exists')]);
                        return;
                    }
                    set_alert('danger', _l('ip_address_already_exists'));
                    redirect(admin_url('securelogin_guard/edit/' . $id));
                    return;
                }
            }
            
            $data = [
                'ip_address' => $ip_address,
                'description' => $description,
                'staff_id' => $staff_id,
                'is_active' => $is_active,
                'date_modified' => date('Y-m-d H:i:s'),
            ];
            
            $success = $this->securelogin_guard_model->update($id, $data);
            
            if ($success) {
                $staff_name = $staff_id ? get_staff_full_name($staff_id) : _l('all_staff');
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
            
            $data['title'] = _l('edit_ip_address');
            $data['whitelist'] = $current;
            $data['is_admin'] = $is_admin;
            
            // Only admins can select staff or create global entries
            if ($is_admin) {
                $data['staff_members'] = $this->staff_model->get('', ['active' => 1]);
            } else {
                $data['staff_members'] = [];
            }
            
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
            if ($whitelist->staff_id != $current_staff_id) {
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
            
            // Check if there are valid IPs left after deletion
            $bypass_admin = get_option('securelogin_guard_bypass_admin');
            $whitelist_was_enabled = get_option('securelogin_guard_enable_ip_whitelist') == '1';
            
            // If bypass_admin is off, check if there are valid IPs (global or assigned to admins)
            if ($bypass_admin != '1' && $whitelist_was_enabled) {
                $has_valid_ips = $this->securelogin_guard_model->has_valid_ips_for_whitelist();
                
                if (!$has_valid_ips) {
                    update_option('securelogin_guard_enable_ip_whitelist', '0');
                    log_activity('IP Whitelist automatically disabled - no valid IP addresses (global or admin) and bypass_admin is off');
                    set_alert('success', _l('ip_address_deleted_successfully') . ' ' . _l('whitelist_auto_disabled_no_valid_ips'));
                } else {
                    set_alert('success', _l('ip_address_deleted_successfully'));
                }
            } else {
                set_alert('success', _l('ip_address_deleted_successfully'));
            }
        } else {
            set_alert('danger', _l('error_deleting_ip_address'));
        }
        
        redirect(admin_url('securelogin_guard/manage'));
    }

    /**
     * Toggle IP address active status
     */
    public function toggle($id)
    {
        if (!has_permission('securelogin_guard', '', 'edit')) {
            set_alert('danger', _l('securelogin_guard_access_denied_contact_admin'));
            log_activity('Tried to edit IP address without permission');
            if (isset($_SERVER['HTTP_REFERER']) && !empty($_SERVER['HTTP_REFERER'])) {
                redirect($_SERVER['HTTP_REFERER']);
            } else {
                redirect(admin_url('access_denied'));
            }
        }
        // Enforce POST for state-changing operation
        if (strtolower($this->input->method()) !== 'post') {
            show_error('Invalid request method', 405);
        }

        $whitelist = $this->securelogin_guard_model->get($id);
        
        if (!$whitelist) {
            show_404();
        }
        
        // Non-admins can only toggle their own entries
        if (!is_admin()) {
            $current_staff_id = get_staff_user_id();
            if ($whitelist->staff_id != $current_staff_id) {
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
        
        $new_status = $whitelist->is_active == 1 ? 0 : 1;
        $success = $this->securelogin_guard_model->update($id, ['is_active' => $new_status]);
        
        if ($success) {
            log_activity('IP Address Status Changed [IP: ' . $whitelist->ip_address . ', Status: ' . ($new_status ? 'Active' : 'Inactive') . ']');
            set_alert('success', _l('ip_address_status_updated'));
        } else {
            set_alert('danger', _l('error_updating_ip_address'));
        }
        
        redirect(admin_url('securelogin_guard/manage'));
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

    /**
     * Add current IP to whitelist
     */
    public function add_current_ip()
    {
        if (!has_permission('securelogin_guard', '', 'create')) {
            set_alert('danger', _l('securelogin_guard_access_denied_contact_admin'));
            log_activity('Tried to add current IP address without permission');
            if (isset($_SERVER['HTTP_REFERER']) && !empty($_SERVER['HTTP_REFERER'])) {
                redirect($_SERVER['HTTP_REFERER']);
            } else {
                redirect(admin_url('access_denied'));
            }
        }
        // Enforce POST for state-changing operation
        if (strtolower($this->input->method()) !== 'post') {
            show_error('Invalid request method', 405);
        }

        try {
            $current_ip = $this->input->ip_address();
            $is_admin = is_admin();
            $current_staff_id = get_staff_user_id();
            
            // Non-admins can only add IPs for themselves
            if ($is_admin) {
                $staff_id = $this->input->post('staff_id');
            } else {
                $staff_id = $current_staff_id;
            }
            
            // Check if staff_id column exists in table
            $columns = $this->db->list_fields(db_prefix() . 'securelogin_guard_whitelist');
            $has_staff_column = in_array('staff_id', $columns);
            
            // Convert empty string to null
            if (empty($staff_id) || $staff_id === false) {
                $staff_id = null;
            } else {
                $staff_id = (int)$staff_id;
                // Validate staff exists if staff_id is provided
                if ($staff_id > 0 && $has_staff_column) {
                    $this->load->model('staff_model');
                    $staff = $this->staff_model->get($staff_id);
                    if (!$staff) {
                        $staff_id = null;
                    }
                } else {
                    $staff_id = null;
                }
            }
            
            // If staff_id column doesn't exist, force staff_id to null
            if (!$has_staff_column) {
                $staff_id = null;
            }
            
            if ($staff_id && $has_staff_column) {
                // Check if IP already exists for this staff member
                if ($this->securelogin_guard_model->ip_exists_for_staff($current_ip, $staff_id)) {
                    set_alert('warning', _l('ip_address_already_exists_for_staff'));
                } else {
                    $data = [
                        'ip_address' => $current_ip,
                        'description' => 'Added automatically from current session',
                        'is_active' => 1,
                        'date_created' => date('Y-m-d H:i:s'),
                        'created_by' => get_staff_user_id(),
                    ];
                    if ($has_staff_column) {
                        $data['staff_id'] = $staff_id;
                    }
                    
                    $id = $this->securelogin_guard_model->add($data);
                    
                    if ($id) {
                        $staff_name = '';
                        if ($staff_id) {
                            $staff_info = $this->staff_model->get($staff_id);
                            if ($staff_info) {
                                $staff_name = $staff_info->firstname . ' ' . $staff_info->lastname;
                            }
                        }
                        log_activity('Current IP Added to Whitelist [IP: ' . $current_ip . ($staff_name ? ', Staff: ' . $staff_name : '') . ']');
                        set_alert('success', _l('ip_address_added_successfully'));
                    } else {
                        set_alert('danger', _l('error_adding_ip_address'));
                    }
                }
            } else {
                // Check if IP already exists globally (staff_id is null)
                $ip_exists = false;
                if ($has_staff_column) {
                    $ip_exists = $this->securelogin_guard_model->ip_exists($current_ip);
                } else {
                    // Fallback: check without staff_id filter if column doesn't exist
                    $this->db->where('ip_address', $current_ip);
                    $ip_exists = $this->db->count_all_results(db_prefix() . 'securelogin_guard_whitelist') > 0;
                }
                
                if ($ip_exists) {
                    set_alert('warning', _l('ip_address_already_exists'));
                } else {
                    $data = [
                        'ip_address' => $current_ip,
                        'description' => 'Added automatically from current session',
                        'is_active' => 1,
                        'date_created' => date('Y-m-d H:i:s'),
                        'created_by' => get_staff_user_id(),
                    ];
                    if ($has_staff_column) {
                        $data['staff_id'] = null;
                    }
                    
                    $id = $this->securelogin_guard_model->add($data);
                    
                    if ($id) {
                        log_activity('Current IP Added to Whitelist [IP: ' . $current_ip . ']');
                        set_alert('success', _l('ip_address_added_successfully'));
                    } else {
                        set_alert('danger', _l('error_adding_ip_address'));
                    }
                }
            }
            
            $redirect_url = admin_url('securelogin_guard/manage');
            if ($staff_id) {
                $redirect_url .= '?staff_id=' . $staff_id;
            }
            
            redirect($redirect_url);
        } catch (Exception $e) {
            log_activity('Error adding current IP: ' . $e->getMessage());
            set_alert('danger', 'Error: ' . $e->getMessage());
            redirect(admin_url('securelogin_guard/manage'));
        }
    }
}

