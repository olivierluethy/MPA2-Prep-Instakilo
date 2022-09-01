<?php
class Instakilo
{
    public $db;

    public function __construct()
    {
        $this->db = connectDatabase();
    }

    /* When user IS NOT logged in - Show public posts */
    public function publicPosts(){
        $statement = $this->db->prepare('SELECT images.imageId, images.titel, images.beschreibung, images.datum, images.ort, images.imageType, images.imageData, images.fk_userId, users.username FROM images
        INNER JOIN users ON users.userId = images.fk_userId WHERE images.oeffentlich = 1');
        $statement->execute();
        return $statement;
    }

    /* When user IS logged in - Show first posts from other people you follow and then public */
    public function posts($id){
        $statement = $this->db->prepare('SELECT images.imageId, images.titel, images.beschreibung, images.datum, images.ort, images.imageType, images.imageData, images.fk_userId, users.username FROM images
        INNER JOIN users ON users.userId = images.fk_userId
        WHERE images.fk_userId IN (SELECT followers.userId FROM followers WHERE followers.followsId = :id) OR images.fk_userId = :id OR images.oeffentlich = 1 ORDER BY images.likes DESC');
        $statement->bindParam(':id', $id, PDO::PARAM_STR);
        $statement->execute();
        return $statement;
    }

    /* Count how many followers someone has */
    public function followers($id){
        $statement = $this->db->prepare('SELECT COUNT(followId) AS "Followers" FROM followers WHERE userId = :id');
        $statement->bindParam(':id', $id, PDO::PARAM_STR);
        $statement->execute();
        return $statement;
    }

    /* Count how many people someone follows */
    public function follows($id){
        $statement = $this->db->prepare('SELECT COUNT(followId) AS "Follows" FROM followers WHERE followsId = :id');
        $statement->bindParam(':id', $id, PDO::PARAM_STR);
        $statement->execute();
        return $statement;
    }

    /* To visit the profile from someone else */
    public function visitProfile($id){
        $statement = $this->db->prepare('SELECT COUNT(followers.followId) AS "Followers", COUNT(followers.followsId) AS "Follows", users.userId, users.username, users.description, users.imageType, users.imageData FROM followers 
        INNER JOIN users ON users.userId = followers.userId WHERE users.userId = :id');
        $statement->bindParam(':id', $id, PDO::PARAM_STR);
        $statement->execute();
        return $statement;
    }

    /* To your actual profile */
    public function profile($id){
        $statement = $this->db->prepare('SELECT * FROM users WHERE userId = :id');
        $statement->bindParam(':id', $id, PDO::PARAM_STR);
        $statement->execute();
        return $statement;
    }

    /* If a user wants to follow another user */
    public function follow(/* User der gefolgt wird */$id, /* Wer den User folgen will */$User){
        $statement = $this->db->prepare('INSERT INTO followers (userId, followsId) VALUES (:userId, :follows)');
        $statement->bindParam(':userId', $id, PDO::PARAM_STR);
        $statement->bindParam(':follows', $User, PDO::PARAM_STR);
        $statement->execute();

        $statement2 = $this->db->prepare('UPDATE users SET followers = followers + 1 WHERE userId = :id');
        $statement2->bindParam(':id', $id, PDO::PARAM_STR);
        $statement2->execute();
        return $statement; $statement2;
    }

    public function unfollow($id, $User){
        $statement = $this->db->prepare('DELETE FROM followers WHERE followers.userId = :id AND followers.followsId = :User');
        $statement->bindParam(':id', $id, PDO::PARAM_STR);
        $statement->bindParam(':User', $User, PDO::PARAM_STR);
        $statement->execute();

        $statement2 = $this->db->prepare('UPDATE users SET followers = followers - 1 WHERE userId = :id');
        $statement2->bindParam(':id', $id, PDO::PARAM_STR);
        $statement2->execute();        
        return $statement;
    }

    public function alreadyFollows($id, $sessionId){
        $statement = $this->db->prepare('SELECT users.userId FROM users 
        INNER JOIN followers ON followers.userId = users.userId WHERE followers.userId = :id AND followers.followsId = :whoFollowsId');
        $statement->bindParam(':id', $id, PDO::PARAM_STR);
        $statement->bindParam(':whoFollowsId', $_SESSION['id'], PDO::PARAM_STR);
        $statement->execute();
        return $statement;
    }




    // public function followedPosts(){
    //     $statement = $this->db->prepare('SELECT images.imageId, images.titel, images.beschreibung, images.imageType, images.imageData FROM images');
    //     $statement->execute();
    //     return $statement;
    // }

    /*  */
}