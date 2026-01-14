<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php echo form_open(admin_url('securelogin_guard/edit/' . $whitelist->id), ['id' => 'edit-ip-form']); ?>

<div class="form-group">
    <?php echo render_input('ip_address', 'ip_address', $whitelist->ip_address, 'text', [
        'required' => true,
        'placeholder' => '192.168.1.1 or 192.168.1.0/24'
    ]); ?>
    <small class="form-text text-muted">
        <?php echo _l('invalid_ip_address'); ?>
    </small>
</div>

<?php if ($is_admin && !empty($staff_members)): ?>
<div class="form-group">
    <?php
    echo render_select(
        'staff_id',
        $staff_members,
        ['staffid', ['firstname', 'lastname']],
        'assign_to_staff',
        $whitelist->staff_id,
        ['data-none-selected-text' => _l('all_staff'), 'class' => 'selectpicker'],
        [],
        'selectpicker',
        '',
        false
    );
    ?>
    <small class="form-text text-muted">
        <?php echo _l('assign_to_staff'); ?> - <?php echo _l('all_staff'); ?> for global IP whitelist
    </small>
</div>
<?php else: ?>
<input type="hidden" name="staff_id" value="<?php echo $whitelist->staff_id; ?>" />
<?php endif; ?>

<div class="form-group">
    <?php echo render_textarea('description', 'description', $whitelist->description, [
        'rows' => 3,
        'placeholder' => 'Optional description for this IP address'
    ]); ?>
</div>

<?php if ($is_admin): ?>
<div class="form-group">
    <div class="checkbox checkbox-primary">
        <input type="checkbox" name="is_active" id="modal_is_active" value="1" 
               <?php echo $whitelist->is_active == 1 ? 'checked' : ''; ?>>
        <label for="modal_is_active">
            <?php echo _l('active'); ?>
        </label>
    </div>
</div>
<?php else: ?>
<input type="hidden" name="is_active" value="<?php echo $whitelist->is_active; ?>" />
<?php endif; ?>

<?php echo form_close(); ?>

