<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>

<div id="wrapper">
  <div class="content">
    <div class="panel_s">
      <div class="panel-body">
        <div class="panel-table-full">
          <a href="#" class="btn btn-primary mbot15" onclick="openProspectModal();return false;">
            <i class="fa fa-plus"></i> New Prospect
          </a>
          <?php
          render_datatable([
            '#', 'Name', 'Email', 'Address', 'City', 'Country', 'Status'
          ], 'prospects');
          ?>
        </div>
      </div>
    </div>
  </div>
</div>
<?php $this->load->view('admin/prospects/modal', ['statuses'=>$statuses]); ?>
<?php init_tail(); ?>
<script>
$(function () {
    initDataTable('.table-prospects', admin_url + 'prospects/table', undefined, undefined, 'undefined', [0, 'desc']);
});

function openProspectModal(){
    $('#prospectModalTitle').text('New Prospect');
    $('#prospectForm')[0].reset();
    $('input[name="id"]').val('');
    $('select[name="status"]').val('').change();
    $('#prospectModal').modal('show');
}

function edit_prospect(id) {
    $.get(admin_url + 'prospects/get/' + id, function(response) {
        let data = JSON.parse(response);

        $('#prospectModalTitle').text('Edit Prospect');

        $('input[name="name"]').val(data.name);
        $('input[name="email"]').val(data.email);
        $('input[name="address"]').val(data.address);
        $('input[name="city"]').val(data.city);
        $('input[name="country"]').val(data.country);
        $('select[name="status"]').val(data.status).change();

        $('input[name="id"]').val(data.id);

        $('#prospectModal').modal('show');
    });
}

window.addEventListener('load', function () {
    appValidateForm($('#prospectForm'), {
        name: 'required',
        email: { required: true, email: true }
    }, function(form) {

        var data = $(form).serialize();
        var url  = form.action;

        $.post(url, data).done(function(response){
            response = JSON.parse(response);

            if(response.success){
                $('#prospectModal').modal('hide');
                $('.table-prospects').DataTable().ajax.reload(null,false);
                alert_float('success', response.message);
            } else {
                alert_float('danger', response.message);
            }
        });

        return false;
    });
});

$(document).on('click', '.change-status', function () {
    let prospect_id = $(this).data('id');
    let status_id   = $(this).data('status');

    $.post(admin_url + 'prospects/update_status', {
        id: prospect_id,
        status: status_id
    }, function (response) {
        response = JSON.parse(response);
        if (response.success) {
            $('.table-prospects').DataTable().ajax.reload(null,false);
            alert_float('success', 'Status updated');
        }
    });
});
</script>
