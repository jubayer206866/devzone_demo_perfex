<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Prospects_model extends App_Model
{
    private $table = 'prospects';

    public function add($data)
    {
        $this->db->insert($this->table, $data);
        return $this->db->insert_id();
    }

    public function get_all()
    {
        return $this->db->get($this->table)->result_array();
    }
    public function update($id, $data)
    {
        $this->db->where('id', $id);
        $this->db->update(db_prefix().'prospects', $data);
    }
    public function get($id)
    {
        $this->db->where('id', $id);
        return $this->db->get(db_prefix().'prospects')->row_array();
    }


}