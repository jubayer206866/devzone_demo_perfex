<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php echo form_open(admin_url('securelogin_guard/add'), ['id' => 'add-ip-form']); ?>

<div class="form-group">
    <div class="checkbox checkbox-primary">
        <input type="checkbox" name="use_current_ip" id="use_current_ip" value="1">
        <label for="use_current_ip">
            <?php echo _l('add_current_ip'); ?>
        </label>
    </div>
</div>

<div class="form-group">
    <label for="modal_ip_address" class="control-label"><?php echo _l('ip_address'); ?> <span class="text-danger">*</span></label>
    <input type="text" 
           id="modal_ip_address" 
           name="ip_address" 
           class="form-control" 
           value="" 
           required 
           placeholder="192.168.1.1, ::1, or 192.168.1.0/24"
           data-current-ip="<?php echo htmlspecialchars($current_ip); ?>">
    <small class="form-text text-muted">
        Enter a valid IPv4 (e.g., 192.168.1.1), IPv6 (e.g., ::1), or CIDR notation (e.g., 192.168.1.0/24)
    </small>
</div>

<?php if ($has_create_permission && !empty($staff_members)): ?>
    <?php
    // Add "All Staff" option at the beginning
    $staff_options = array();
    foreach ($staff_members as $staff) {
        $staff_options[] = $staff;
    }
    
    $staff_label = _l('assign_to_staff', '', false) . ' <span class="text-danger">*</span>';
    echo render_select(
        'staff_ids[]',
        $staff_options,
        ['staffid', ['firstname', 'lastname']],
        $staff_label,
        '',
        ['data-none-selected-text' => _l('select_staff'), 'class' => 'selectpicker', 'multiple' => true, 'required' => true],
        [],
        'selectpicker',
        '',
        false
    );
    ?>
    <small class="form-text text-muted">
        <?php echo _l('assign_to_staff'); ?> - Select one or more staff members. Admins are always allowed to login from any IP.
    </small>
<?php else: ?>
<input type="hidden" name="staff_ids[]" value="<?php echo get_staff_user_id(); ?>" />
<?php endif; ?>

<div class="form-group">
    <?php echo render_textarea('description', 'description', '', [
        'rows' => 3,
        'placeholder' => 'Optional description for this IP address'
    ]); ?>
</div>

<?php echo form_close(); ?>
