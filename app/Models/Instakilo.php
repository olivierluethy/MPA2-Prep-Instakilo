<?php
class Framework
{
    public $db;

    public function __construct()
    {
        $this->db = connectDatabase();
    }

    public function index(){
        $statement = $this->db->prepare('SELECT images.imageId, images.title, images.beschreibung, images.imageType, images.imageData FROM images
        INNER JOIN followers ON followers.fk_followsId = images.fk_userId
        INNER JOIN users ON  users.userId = followers.fk_userId WHERE users.email = :email');
        $statement->bindParam(':email', $_SESSION["email"], PDO::PARAM_STR);
        $statement->execute();
        return $statement;
    }
}