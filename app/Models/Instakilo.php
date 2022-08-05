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

    public function profile($id){
        $statement = $this->db->prepare('SELECT * FROM users WHERE userId = :id');
        $statement->bindParam(':id', $id, PDO::PARAM_STR);
        $statement->execute();
        return $statement;
    }

    public function followers($id){
        $statement = $this->db->prepare('SELECT COUNT(followId) AS "Followers" FROM followers WHERE fk_userId = :id');
        $statement->bindParam(':id', $id, PDO::PARAM_STR);
        $statement->execute();
        return $statement;
    }

    public function follows($id){
        $statement = $this->db->prepare('SELECT COUNT(followId) AS "Follows" FROM followers WHERE fk_followsId = :id');
        $statement->bindParam(':id', $id, PDO::PARAM_STR);
        $statement->execute();
        return $statement;
    }
}