<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
            <div class="row">
                <div class="col-md-12">
                    <div class="tw-mb-2">
                        <div class="row">
                            <div class="col-md-12">
                                <?php if (has_permission('securelogin_guard', '', 'create')): ?>
                                <a href="#" class="btn btn-primary" data-toggle="modal" data-target="#addIpModal">
                                    <i class="fa-regular fa-plus tw-mr-1"></i>
                                    <?php echo _l('add_ip_address'); ?>
                                </a>
                                <?php endif; ?>
                                <?php if ($is_admin): ?>
                                <a href="#" class="btn btn-default" data-toggle="modal" data-target="#settingsModal" style="margin-left: 10px;">
                                    <i class="fa fa-cog tw-mr-1"></i>
                                    <?php echo _l('settings'); ?>
                                </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- IP Whitelist Table -->
                    <div class="panel_s">
                        <div class="panel-body panel-table-full">
                            <?php if (empty($whitelist)): ?>
                                <p class="no-margin text-muted">
                                    <?php echo _l('no_ip_addresses'); ?>
                                </p>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table dt-table table-data">
                                        <thead>
                                            <tr>
                                                <th><?php echo _l('ip_address'); ?></th>
                                                <?php if ($is_admin): ?>
                                                <th><?php echo _l('staff_member'); ?></th>
                                                <?php endif; ?>
                                                <th><?php echo _l('description'); ?></th>
                                                <th><?php echo _l('status'); ?></th>
                                                <th><?php echo _l('date_created'); ?></th>
                                                <th><?php echo _l('options'); ?></th>
                                            </tr>
                                        </thead>
                                        <tbody id="whitelist-table-body">
                                            <?php foreach ($whitelist as $item): ?>
                                                <tr>
                                                    <td>
                                                        <code><?php echo htmlspecialchars($item->ip_address); ?></code>
                                                    </td>
                                                    <?php if ($is_admin): ?>
                                                    <td>
                                                        <?php if ($item->staff_id): ?>
                                                            <strong><?php echo _l('for_staff'); ?>:</strong> 
                                                            <span class="label label-primary"><?php echo get_staff_full_name($item->staff_id); ?></span>
                                                        <?php else: ?>
                                                            <span class="label label-info"><?php echo _l('all_staff'); ?></span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <?php endif; ?>
                                                    <td>
                                                        <?php echo htmlspecialchars($item->description ? $item->description : '-'); ?>
                                                    </td>
                                                    <td>
                                                        <?php if ($item->is_active == 1): ?>
                                                            <span class="label label-success"><?php echo _l('active'); ?></span>
                                                        <?php else: ?>
                                                            <span class="label label-default"><?php echo _l('inactive'); ?></span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td data-order="<?php echo $item->date_created; ?>">
                                                        <?php echo _dt($item->date_created); ?>
                                                    </td>
                                                    <td>
                                                        <div class="tw-flex tw-items-center tw-space-x-2">
                                                            <?php if (has_permission('securelogin_guard', '', 'edit')): ?>
                                                            <a href="#" 
                                                               class="edit-ip-btn tw-text-neutral-500 hover:tw-text-neutral-700 focus:tw-text-neutral-700"
                                                               data-id="<?php echo $item->id; ?>"
                                                               data-toggle="modal" 
                                                               data-target="#editIpModal"
                                                               data-toggle="tooltip" title="<?php echo _l('edit'); ?>">
                                                                <i class="fa-regular fa-pen-to-square fa-lg"></i>
                                                            </a>
                                                            <?php echo form_open(admin_url('securelogin_guard/toggle/' . $item->id), ['method' => 'post', 'style' => 'display:inline']); ?>
                                                                <button type="submit" class="btn btn-link p-0 tw-text-neutral-500 hover:tw-text-neutral-700 focus:tw-text-neutral-700" data-toggle="tooltip" title="<?php echo $item->is_active == 1 ? _l('disable') : _l('enable'); ?>">
                                                                    <i class="fa-regular fa-<?php echo $item->is_active == 1 ? 'ban' : 'check'; ?> fa-lg"></i>
                                                                </button>
                                                            <?php echo form_close(); ?>
                                                            <?php endif; ?>
                                                            <?php if (has_permission('securelogin_guard', '', 'delete')): ?>
                                                            <?php echo form_open(admin_url('securelogin_guard/delete/' . $item->id), ['method' => 'post', 'style' => 'display:inline', 'onsubmit' => 'return confirm("' . _l('confirm_delete') . '");']); ?>
                                                                <button type="submit" class="btn btn-link p-0 tw-text-neutral-500 hover:tw-text-neutral-700 focus:tw-text-neutral-700" data-toggle="tooltip" title="<?php echo _l('delete'); ?>">
                                                                    <i class="fa-regular fa-trash-can fa-lg"></i>
                                                                </button>
                                                            <?php echo form_close(); ?>
                                                            <?php endif; ?>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                            <div id="whitelist-loading" style="display: none; text-align: center; padding: 20px;">
                                <i class="fa fa-spinner fa-spin"></i> Loading...
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php init_tail(); ?>

<!-- Add IP Modal -->
<div class="modal fade" id="addIpModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title"><?php echo _l('add_ip_address'); ?></h4>
            </div>
            <div class="modal-body" id="addIpModalBody">
                <p class="text-center"><i class="fa fa-spinner fa-spin"></i> Loading...</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo _l('cancel'); ?></button>
                <button type="button" class="btn btn-primary" id="submitAddForm"><?php echo _l('submit'); ?></button>
            </div>
        </div>
    </div>
</div>

<!-- Edit IP Modal -->
<div class="modal fade" id="editIpModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title"><?php echo _l('edit_ip_address'); ?></h4>
            </div>
            <div class="modal-body" id="editIpModalBody">
                <p class="text-center"><i class="fa fa-spinner fa-spin"></i> Loading...</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo _l('cancel'); ?></button>
                <button type="button" class="btn btn-primary" id="submitEditForm"><?php echo _l('update'); ?></button>
            </div>
        </div>
    </div>
</div>

<!-- Settings Modal -->
<div class="modal fade" id="settingsModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title"><?php echo _l('settings'); ?></h4>
            </div>
            <div class="modal-body" id="settingsModalBody">
                <p class="text-center"><i class="fa fa-spinner fa-spin"></i> Loading...</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo _l('cancel'); ?></button>
                <button type="button" class="btn btn-primary" id="submitSettingsForm"><?php echo _l('save'); ?></button>
            </div>
        </div>
    </div>
</div>

<script>
$(function() {
    initDataTable('.table-data', window.location.href, undefined, undefined, undefined, [4, 'desc']);
    $('.selectpicker').selectpicker();
    
    
    // Load add form when modal opens
    $('#addIpModal').on('show.bs.modal', function() {
        var modalBody = $('#addIpModalBody');
        modalBody.html('<p class="text-center"><i class="fa fa-spinner fa-spin"></i> Loading...</p>');
        
        $.ajax({
            url: '<?php echo admin_url('securelogin_guard/get_add_form'); ?>',
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    modalBody.html(response.html);
                    $('.selectpicker').selectpicker('refresh');
                    appValidateForm($('#add-ip-form'), {
                        ip_address: 'required'
                    });
                    
                    // Handle "Add current IP" checkbox
                    $('#use_current_ip').on('change', function() {
                        var ipField = $('#modal_ip_address');
                        var currentIp = ipField.data('current-ip');
                        if ($(this).is(':checked')) {
                            ipField.val(currentIp);
                        } else {
                            ipField.val('');
                        }
                    });
                } else {
                    modalBody.html('<div class="alert alert-danger">' + (response.message || 'Error loading form') + '</div>');
                }
            },
            error: function() {
                modalBody.html('<div class="alert alert-danger">Error loading form. Please try again.</div>');
            }
        });
    });
    
    // Submit add form
    $('#submitAddForm').on('click', function() {
        var form = $('#add-ip-form');
        if (form.valid()) {
            var formData = form.serialize();
            var submitBtn = $(this);
            var originalText = submitBtn.html();
            submitBtn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Processing...');
            
            $.ajax({
                url: form.attr('action'),
                type: 'POST',
                data: formData,
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        $('#addIpModal').modal('hide');
                        alert_float('success', response.message);
                        setTimeout(function() {
                            window.location.reload();
                        }, 1000);
                    } else {
                        alert_float('danger', response.message || 'Error occurred');
                        submitBtn.prop('disabled', false).html(originalText);
                    }
                },
                error: function() {
                    alert_float('danger', '<?php echo _l('error_adding_ip_address'); ?>');
                    submitBtn.prop('disabled', false).html(originalText);
                }
            });
        }
    });
    
    // Load edit form when modal opens (using event delegation for dynamic content)
    $(document).on('click', '.edit-ip-btn', function(e) {
        e.preventDefault();
        var id = $(this).data('id');
        var modalBody = $('#editIpModalBody');
        modalBody.html('<p class="text-center"><i class="fa fa-spinner fa-spin"></i> Loading...</p>');
        
        // Show modal first
        $('#editIpModal').modal('show');
        
        $.ajax({
            url: '<?php echo admin_url('securelogin_guard/get_edit_form/'); ?>' + id,
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    modalBody.html(response.html);
                    $('.selectpicker').selectpicker('refresh');
                    appValidateForm($('#edit-ip-form'), {
                        ip_address: 'required'
                    });
                } else {
                    modalBody.html('<div class="alert alert-danger">' + (response.message || 'Error loading form') + '</div>');
                }
            },
            error: function() {
                modalBody.html('<div class="alert alert-danger">Error loading form. Please try again.</div>');
            }
        });
    });
    
    // Submit edit form
    $('#submitEditForm').on('click', function() {
        var form = $('#edit-ip-form');
        if (form.valid()) {
            var formData = form.serialize();
            var submitBtn = $(this);
            var originalText = submitBtn.html();
            submitBtn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Processing...');
            
            $.ajax({
                url: form.attr('action'),
                type: 'POST',
                data: formData,
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        $('#editIpModal').modal('hide');
                        alert_float('success', response.message);
                        setTimeout(function() {
                            window.location.reload();
                        }, 1000);
                    } else {
                        alert_float('danger', response.message || 'Error occurred');
                        submitBtn.prop('disabled', false).html(originalText);
                    }
                },
                error: function() {
                    alert_float('danger', '<?php echo _l('error_updating_ip_address'); ?>');
                    submitBtn.prop('disabled', false).html(originalText);
                }
            });
        }
    });
    
    // Load settings form when modal opens
    $('#settingsModal').on('show.bs.modal', function() {
        var modalBody = $('#settingsModalBody');
        modalBody.html('<p class="text-center"><i class="fa fa-spinner fa-spin"></i> Loading...</p>');
        
        $.ajax({
            url: '<?php echo admin_url('securelogin_guard/get_settings_form'); ?>',
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    modalBody.html(response.html);
                    
                    // Add validation for enable whitelist checkbox
                    var enableCheckbox = $('#modal_enable_whitelist');
                    var bypassCheckbox = $('#modal_bypass_admin');
                    var hasValidIps = response.has_valid_ips === true || response.has_valid_ips === 1 || enableCheckbox.data('has-valid-ips') == '1';
                    
                    enableCheckbox.on('change', function() {
                        if ($(this).is(':checked')) {
                            var bypassChecked = bypassCheckbox.is(':checked');
                            
                            // If no valid IPs (global or admin) and bypass_admin is not checked, prevent enabling
                            if (!hasValidIps && !bypassChecked) {
                                $(this).prop('checked', false);
                                alert_float('warning', 'Cannot enable IP whitelist. You must either add at least one IP address (for All Staff or assigned to an admin) or enable "Bypass IP check for administrators" option.');
                                return false;
                            }
                        }
                    });
                    
                    // Also check when bypass_admin changes - if it's unchecked and no valid IPs, uncheck enable_whitelist
                    bypassCheckbox.on('change', function() {
                        if (!$(this).is(':checked') && !hasValidIps && enableCheckbox.is(':checked')) {
                            enableCheckbox.prop('checked', false);
                            alert_float('warning', 'Cannot keep IP whitelist enabled. You must either add at least one IP address (for All Staff or assigned to an admin) or enable "Bypass IP check for administrators" option.');
                        }
                    });
                } else {
                    modalBody.html('<div class="alert alert-danger">' + (response.message || 'Error loading settings') + '</div>');
                }
            },
            error: function() {
                modalBody.html('<div class="alert alert-danger">Error loading settings. Please try again.</div>');
            }
        });
    });
    
    // Submit settings form
    $('#submitSettingsForm').on('click', function() {
        var form = $('#settings-form');
        var formData = form.serialize();
        var submitBtn = $(this);
        var originalText = submitBtn.html();
        submitBtn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Saving...');
        
        $.ajax({
            url: form.attr('action'),
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $('#settingsModal').modal('hide');
                    alert_float('success', response.message || 'Settings updated successfully');
                    setTimeout(function() {
                        window.location.reload();
                    }, 1000);
                } else {
                    alert_float('danger', response.message || 'Error occurred');
                    submitBtn.prop('disabled', false).html(originalText);
                }
            },
            error: function() {
                alert_float('danger', 'Error updating settings. Please try again.');
                submitBtn.prop('disabled', false).html(originalText);
            }
        });
    });
    
    // Reset form when modal is closed
    $('#addIpModal, #editIpModal, #settingsModal').on('hidden.bs.modal', function() {
        $(this).find('form')[0].reset();
        $(this).find('.selectpicker').selectpicker('refresh');
    });
});
</script>
</body>
</html>

