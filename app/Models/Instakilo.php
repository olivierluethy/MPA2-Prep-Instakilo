<?php
class Framework
{
    public $db;

    public function __construct()
    {
        $this->db = connectDatabase();
    }

    public function followedPosts(){
        $statement = $this->db->prepare('SELECT images.imageId, images.titel, images.beschreibung, images.imageType, images.imageData FROM images
        INNER JOIN users ON users.userId = images.fk_userId INNER JOIN followers ON followers.fk_userId = users.userId WHERE followers.fk_userId = :id');
        $statement->bindParam(':id', $_SESSION['id']);
        $statement->execute();
        return $statement;
    }

    public function mostLikedPosts(){
        $statement = $this->db->prepare('SELECT images.imageId, images.titel, images.beschreibung, images.imageType, images.imageData FROM images
        ORDER BY images.likes DESC');
        $statement->execute();
        return $statement;
    }
}