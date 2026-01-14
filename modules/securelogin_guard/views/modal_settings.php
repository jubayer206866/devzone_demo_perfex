<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php echo form_open(admin_url('securelogin_guard/update_settings'), ['id' => 'settings-form']); ?>

<div class="form-group">
    <div class="checkbox checkbox-primary">
        <input type="hidden" name="enable_whitelist" value="0">
        <input type="checkbox" name="enable_whitelist" id="modal_enable_whitelist" value="1" 
               <?php echo $enable_whitelist == '1' ? 'checked' : ''; ?>
               data-has-valid-ips="<?php echo isset($has_valid_ips) && $has_valid_ips ? '1' : '0'; ?>">
        <label for="modal_enable_whitelist">
            <?php echo _l('enable_ip_whitelist'); ?>
        </label>
    </div>
    <small class="form-text text-muted">
        When enabled, only whitelisted IP addresses will be allowed to login.
    </small>
</div>

<div class="form-group">
    <div class="checkbox checkbox-primary">
        <input type="hidden" name="bypass_admin" value="0">
        <input type="checkbox" name="bypass_admin" id="modal_bypass_admin" value="1" 
               <?php echo $bypass_admin == '1' ? 'checked' : ''; ?>>
        <label for="modal_bypass_admin">
            <?php echo _l('bypass_admin'); ?>
        </label>
    </div>
    <small class="form-text text-muted">
        When enabled, administrators can login from any IP address, bypassing the whitelist check.
    </small>
</div>

<?php echo form_close(); ?>

