<div class="modal fade" id="prospectModal">
  <div class="modal-dialog">
    <div class="modal-content">

      <div class="modal-header">
        <h4 class="modal-title">New Prospect</h4>
      </div>

      <div class="modal-body">
        <?php echo form_open(admin_url('prospects/save'), ['id'=>'prospectForm']); ?>

        <?php echo render_input('name','Name'); ?>
        <?php echo render_input('email','Email','','email'); ?>
        <?php echo render_input('address','Address'); ?>
        <?php echo render_input('city','City'); ?>
        <?php echo render_input('country','Country'); ?>

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

<script>
function openProspectModal(){
  $('#prospectModal').modal('show');
}
</script>
