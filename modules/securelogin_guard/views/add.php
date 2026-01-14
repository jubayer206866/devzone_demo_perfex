<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
            <div class="row">
                <div class="col-md-6">
                    <div class="panel_s">
                        <div class="panel-body">
                            <h4 class="no-margin"><?php echo _l('add_ip_address'); ?></h4>
                            <hr class="hr-panel-heading" />
                            <?php echo form_open(admin_url('securelogin_guard/add'), ['id' => 'add-ip-form']); ?>
                            
                            <div class="form-group">
                                <label for="ip_address" class="control-label"><?php echo _l('ip_address'); ?> <span class="text-danger">*</span></label>
                                <input type="text" 
                                       id="ip_address" 
                                       name="ip_address" 
                                       class="form-control" 
                                       value="<?php echo htmlspecialchars($current_ip); ?>" 
                                       required 
                                       placeholder="192.168.1.1, ::1, or 192.168.1.0/24">
                                <small class="form-text text-muted">
                                    Enter a valid IPv4 (e.g., 192.168.1.1), IPv6 (e.g., ::1), or CIDR notation (e.g., 192.168.1.0/24)
                                </small>
                            </div>
                            
                            <?php if ($is_admin && !empty($staff_members)): ?>
                            <div class="form-group">
                                <?php
                                // Use core staff list helper signature like in core views
                                echo render_select(
                                    'staff_id', // name
                                    $staff_members, // options (array of staff rows)
                                    ['staffid', ['firstname', 'lastname']], // option attrs
                                    'assign_to_staff', // label
                                    '', // selected
                                    ['data-none-selected-text' => _l('all_staff'), 'class' => 'selectpicker'], // attrs
                                    [], // form group attrs
                                    'selectpicker', // selectpicker class
                                    '', // additional classes
                                    false // ajax or multiple
                                );
                                ?>
                                <small class="form-text text-muted">
                                    <?php echo _l('assign_to_staff'); ?> - <?php echo _l('all_staff'); ?> for global IP whitelist
                                </small>
                            </div>
                            <?php else: ?>
                            <input type="hidden" name="staff_id" value="<?php echo get_staff_user_id(); ?>" />
                            <?php endif; ?>
                            
                            <div class="form-group">
                                <?php echo render_textarea('description', 'description', '', [
                                    'rows' => 3,
                                    'placeholder' => 'Optional description for this IP address'
                                ]); ?>
                            </div>
                            
                            <hr />
                            <div class="form-group" style="margin-top: 20px; padding-top: 15px;">
                                <div style="text-align: right;">
                                    <button type="submit" class="btn btn-primary" style="min-width: 100px;">
                                        <i class="fa fa-check"></i> <?php echo _l('submit'); ?>
                                    </button>
                                    <a href="<?php echo admin_url('securelogin_guard/manage'); ?>" class="btn btn-default" style="min-width: 100px; margin-left: 10px;">
                                        <i class="fa fa-times"></i> <?php echo _l('cancel'); ?>
                                    </a>
                                </div>
                            </div>
                            <?php echo form_close(); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php init_tail(); ?>
<script>
$(function() {
    // Initialize selectpicker
    $('.selectpicker').selectpicker();
    
    // Simple form validation
    appValidateForm($('#add-ip-form'), {
        ip_address: 'required'
    });
});
</script>
</body>
</html>

