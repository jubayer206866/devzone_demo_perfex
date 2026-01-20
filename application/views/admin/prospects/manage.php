<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>

<div class="content">
  <div class="row">
    <div class="col-md-12">
      <div class="panel_s">
        <div class="panel-body">

          <a href="#" class="btn btn-primary mbot15" onclick="openProspectModal();return false;">
            <i class="fa fa-plus"></i> New Prospect
          </a>

          <?php
          render_datatable([
            'Name',
            'Email',
            'Address',
            'City',
            'Country',
          ], 'prospects');
          ?>

        </div>
      </div>
    </div>
  </div>
</div>

<?php $this->load->view('admin/prospects/modal'); ?>
<?php init_tail(); ?>

<script>
$(function () {
    initDataTable(
        '.table-prospects',
        admin_url + 'prospects/table'
    );
});
</script>
</body>
</html>
