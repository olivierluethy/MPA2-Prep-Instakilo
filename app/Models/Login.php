<?php
class Login
{
    public $db;

    public function __construct()
    {
        $this->db = connectDatabase();
    }

    public function index(){
        $statement = $this->db->prepare('SELECT * FROM Person');
        $statement->execute();
        return $statement;
    }
}