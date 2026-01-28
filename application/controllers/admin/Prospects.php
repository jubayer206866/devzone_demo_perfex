<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Prospects extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('prospects_model');
    }

    public function index()
    {
        $this->load->model('Leads_model');
        $data['statuses'] = $this->Leads_model->get_status();
        $data['title'] = 'Prospects';
        $this->load->view('admin/prospects/manage', $data);
    }


        public function save()
{
    if (!$this->input->is_ajax_request()) {
        show_404();
    }
    $data = $this->input->post();

    // Duplicate email check
    $this->db->where('email', $data['email']);
    if (!empty($data['id'])) {
        $this->db->where('id !=', $data['id']);
    }

    $exists = $this->db->get(db_prefix().'prospects')->row();

    if ($exists) {
        echo json_encode([
            'success' => false,
            'message' => _l('prospect_email_exists')
        ]);
        die;
    }

    if (!empty($data['id'])) {
        $this->prospects_model->update($data['id'], $data);

        echo json_encode([
            'success' => true,
            'message' => _l('prospect_updated_success')
        ]);
        die;
    } else {
        $this->prospects_model->add($data);

        echo json_encode([
            'success' => true,
            'message' => _l('prospect_created_success')
        ]);
        die;
    }
}

        public function table()
    {
        $this->load->model('Leads_model');
        $data['statuses'] = $this->Leads_model->get_status();

        $this->app->get_table_data('prospects', $data);
    }

    public function get($id)
    { 
        $prospect = $this->prospects_model->get($id);
        echo json_encode($prospect);
    }
    public function delete($id)
    {
        $this->db->where('id', $id);
        $this->db->delete(db_prefix().'prospects');

        redirect(admin_url('prospects'));
    }
        public function update_status()
        {
            $id     = $this->input->post('id');
            $status = $this->input->post('status');

            $this->db->where('id', $id);
            $this->db->update(db_prefix().'prospects', [
                'status' => $status
            ]);

            echo json_encode(['success' => true]);
            exit;
        }


}
