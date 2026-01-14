<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
            <div class="row">
                <div class="col-md-6">
                    <div class="panel_s">
                        <div class="panel-body">
                            <h4 class="no-margin"><?php echo _l('edit_ip_address'); ?></h4>
                            <hr class="hr-panel-heading" />
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
                                    <input type="checkbox" name="is_active" id="is_active" value="1" 
                                           <?php echo $whitelist->is_active == 1 ? 'checked' : ''; ?>>
                                    <label for="is_active">
                                        <?php echo _l('active'); ?>
                                    </label>
                                </div>
                            </div>
                            <?php else: ?>
                            <input type="hidden" name="is_active" value="<?php echo $whitelist->is_active; ?>" />
                            <?php endif; ?>
                            
                            <hr />
                            <div class="form-group" style="margin-top: 20px; padding-top: 15px;">
                                <div style="text-align: right;">
                                    <button type="submit" class="btn btn-primary" style="min-width: 100px;">
                                        <i class="fa fa-save"></i> <?php echo _l('update'); ?>
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
    appValidateForm($('form'), {
        ip_address: 'required'
    });
});
</script>
</body>
</html>

