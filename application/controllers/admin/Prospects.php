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
        $data['title'] = 'Prospects';
        $this->load->view('admin/prospects/manage', $data);
    }

    public function save()
    {
        $this->prospects_model->add($this->input->post());
        echo json_encode(['success' => true]);
    }
     public function table()
    {
        if (!has_permission('prospects', '', 'view')) {
            ajax_access_denied();
        }

        $this->app->get_table_data('prospects');
    }

}
