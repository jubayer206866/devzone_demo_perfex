<div class="modal fade" id="prospectModal">
  <div class="modal-dialog">
    <div class="modal-content">

      <div class="modal-header">
        <h4 class="modal-title" id="prospectModalTitle">New Prospect</h4>
      </div>

      <div class="modal-body">
        <?php echo form_open(admin_url('prospects/save'), ['id'=>'prospectForm']); ?>
        <input type="hidden" name="id" value="">

        <?php
        echo render_input('name','Name');
        echo render_input('email','Email','','email');
        echo render_input('address','Address');
        echo render_input('city','City');
        echo render_input('country','Country');
        echo render_select('status', $statuses, ['id','name'], 'Status');
        ?>

        <?php echo form_close(); ?>
      </div>

      <div class="modal-footer">
        <button class="btn btn-primary" onclick="$('#prospectForm').submit();">
          Save
        </button>
      </div>
    </div>
  </div>
</div>
