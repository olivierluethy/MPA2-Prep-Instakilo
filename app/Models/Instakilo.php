<?php
class Instakilo
{
    public $db;

    public function __construct()
    {
        $this->db = connectDatabase();
    }

    // public function followedPosts(){
    //     $statement = $this->db->prepare('SELECT images.imageId, images.titel, images.beschreibung, images.imageType, images.imageData FROM images');
    //     $statement->execute();
    //     return $statement;
    // }

    public function posts(){
        $statement = $this->db->prepare('SELECT images.imageId, images.titel, images.beschreibung, images.datum, images.ort, images.likes, images.imageType, images.ImageData, users.username FROM images
        INNER JOIN users ON users.userId = images.fk_userId');
        $statement->execute();
        return $statement;
    }
}