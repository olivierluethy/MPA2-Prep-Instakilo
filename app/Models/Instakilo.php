<?php
class Framework
{
    public $db;

    public function __construct()
    {
        $this->db = connectDatabase();
    }

    public function index(){
        $statement = $this->db->prepare('SELECT images.imageId, images.titel, images.beschreibung, images.imageType, images.imageData FROM images');
        $statement->execute();
        return $statement;
    }
}