<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Prospects_model extends App_Model
{
    // private $table = db_prefix() .'prospects';

    public function add($data)
    {
        $this->db->insert($this->table, [
            'name'    => $data['name'],
            'email'   => $data['email'],
            'address' => $data['address'],
            'city'    => $data['city'],
            'country' => $data['country'],
        ]);
        return $this->db->insert_id();
    }
}
