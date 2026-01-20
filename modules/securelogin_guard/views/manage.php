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
                        </div>
                    </div>
                </div>

                <!-- IP Whitelist Table -->
                <div class="panel_s">
                    <div class="panel-body">
                        <div class="panel-table-full">
                            <?php $this->load->view('securelogin_guard/table_html', ['is_admin' => $is_admin]); ?>
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

<script>
$(function() {
    // Initialize Perfex datatable
    initDataTable('.table-securelogin-guard', admin_url + 'securelogin_guard/table', undefined, undefined, 'undefined',
        <?= hooks()->apply_filters('securelogin_guard_table_default_order', json_encode([4, 'desc'])); ?>
    );
    
    // Load add form when modal opens
    $('#addIpModal').on('show.bs.modal', function() {
        var modalBody = $('#addIpModalBody');
        modalBody.html('<p class="text-center"><i class="fa fa-spinner fa-spin"></i> Loading...</p>');
        
        // Reset submit button
        $('#submitAddForm').prop('disabled', false).html('<?php echo _l('submit'); ?>');
        
        $.ajax({
            url: '<?php echo admin_url('securelogin_guard/get_add_form'); ?>',
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    modalBody.html(response.html);
                    $('.selectpicker').selectpicker('refresh');
                    
                    // Destroy existing validator if any
                    var form = $('#add-ip-form');
                    if (form.length && form.data('validator')) {
                        form.data('validator', null);
                    }
                    
                    // Custom validation for staff selection
                    var staffSelect = form.find('select[name="staff_ids[]"]');
                    if (staffSelect.length > 0) {
                        // Add custom validation method for multi-select (only if not already added)
                        if (!$.validator.methods.requireStaffSelection) {
                            $.validator.addMethod("requireStaffSelection", function(value, element) {
                                var selected = $(element).val();
                                return selected !== null && selected.length > 0;
                            }, '<?php echo _l('please_select_at_least_one_staff'); ?>');
                        }
                        
                        appValidateForm(form, {
                            ip_address: 'required',
                            'staff_ids[]': {
                                requireStaffSelection: true
                            }
                        });
                    } else {
                        appValidateForm(form, {
                            ip_address: 'required'
                        });
                    }
                    
                    // Handle "Add current IP" checkbox
                    $('#use_current_ip').off('change').on('change', function() {
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
                        // Reload datatable
                        if ($.fn.DataTable.isDataTable('.table-securelogin-guard')) {
                            $('.table-securelogin-guard').DataTable().ajax.reload(null, false);
                        }
                        // Reset button state
                        submitBtn.prop('disabled', false).html(originalText);
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
        
        // Reset submit button
        $('#submitEditForm').prop('disabled', false).html('<?php echo _l('update'); ?>');
        
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
                    
                    // Destroy existing validator if any
                    var form = $('#edit-ip-form');
                    if (form.length && form.data('validator')) {
                        form.data('validator', null);
                    }
                    
                    // Custom validation for staff selection
                    var staffSelect = form.find('select[name="staff_ids[]"]');
                    if (staffSelect.length > 0) {
                        // Add custom validation method for multi-select (only if not already added)
                        if (!$.validator.methods.requireStaffSelection) {
                            $.validator.addMethod("requireStaffSelection", function(value, element) {
                                var selected = $(element).val();
                                return selected !== null && selected.length > 0;
                            }, '<?php echo _l('please_select_at_least_one_staff'); ?>');
                        }
                        
                        appValidateForm(form, {
                            ip_address: 'required',
                            'staff_ids[]': {
                                requireStaffSelection: true
                            }
                        });
                    } else {
                        appValidateForm(form, {
                            ip_address: 'required'
                        });
                    }
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
                        // Reload datatable
                        if ($.fn.DataTable.isDataTable('.table-securelogin-guard')) {
                            $('.table-securelogin-guard').DataTable().ajax.reload(null, false);
                        }
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
    
    // Handle delete action
    $(document).on('click', '._delete', function(e) {
        e.preventDefault();
        var url = $(this).attr('href');
            $.post(url, function(response) {
                alert_float('success', '<?php echo _l('ip_address_deleted_successfully'); ?>');
                // Reload datatable
                if ($.fn.DataTable.isDataTable('.table-securelogin-guard')) {
                    $('.table-securelogin-guard').DataTable().ajax.reload(null, false);
                }
            }).fail(function() {
                alert_float('danger', '<?php echo _l('error_deleting_ip_address'); ?>');
            });
        
        return false;
    });
    
    // onoffswitch is handled automatically by main.js, but we can reload datatable after change
    $(document).on('change', '.onoffswitch input', function() {
        // Reload datatable after status change (with a small delay to allow the AJAX request to complete)
        setTimeout(function() {
            if ($.fn.DataTable.isDataTable('.table-securelogin-guard')) {
                $('.table-securelogin-guard').DataTable().ajax.reload(null, false);
            }
        }, 500);
    });
    
    // Reset form when modal is closed
    $('#addIpModal').on('hidden.bs.modal', function() {
        var form = $(this).find('form');
        if (form.length) {
            form[0].reset();
            // Remove validation classes
            form.find('.has-error').removeClass('has-error');
            form.find('.error').remove();
        }
        $(this).find('.selectpicker').selectpicker('refresh');
        // Reset submit button
        $('#submitAddForm').prop('disabled', false).html('<?php echo _l('submit'); ?>');
        // Clear modal body
        $('#addIpModalBody').html('<p class="text-center"><i class="fa fa-spinner fa-spin"></i> Loading...</p>');
    });
    
    $('#editIpModal').on('hidden.bs.modal', function() {
        var form = $(this).find('form');
        if (form.length) {
            form[0].reset();
            // Remove validation classes
            form.find('.has-error').removeClass('has-error');
            form.find('.error').remove();
        }
        $(this).find('.selectpicker').selectpicker('refresh');
        // Reset submit button
        $('#submitEditForm').prop('disabled', false).html('<?php echo _l('update'); ?>');
        // Clear modal body
        $('#editIpModalBody').html('<p class="text-center"><i class="fa fa-spinner fa-spin"></i> Loading...</p>');
    });
});
</script>
</body>
</html>
