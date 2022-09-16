<?php
class Instakilo
{
    public $db;

    public function __construct()
    {
        $this->db = connectDatabase();
    }

    /* ---------- POST SECTION ---------- */
    /* Alle Posts */
    public function allPosts(){
        $statement = $this->db->prepare('SELECT images.imageId, images.titel, images.beschreibung, images.datum, images.ort, images.imageType, images.imageData FROM images');
        $statement->execute();
        return $statement;
    }

    /* When user IS NOT logged in - Show public posts */
    public function publicPosts(){
        $statement = $this->db->prepare('SELECT images.imageId, images.titel, images.beschreibung, images.datum, images.ort, images.imageType, images.imageData, images.fk_userId, users.username, COUNT(likes.likeId) AS "likes" FROM images
        LEFT JOIN likes ON likes.fk_imageId = images.imageId
        INNER JOIN users ON users.userId = images.fk_userId
        WHERE images.oeffentlich = 1
        HAVING COUNT(likes.likeId) = 0 OR COUNT(likes.likeId) > 0');
        $statement->execute();
        return $statement;

        /* ---------------- Eintrag mit 0 likes und alle restlichen Columns sind gleich NULL, verstehe nicht wieso? --------------*/
        // SELECT images.imageId, images.titel, images.beschreibung, images.datum, images.ort, images.imageType, images.imageData, images.fk_userId, users.username, COUNT(likes.likeId) AS "likes" FROM images
        // INNER JOIN likes ON likes.fk_imageId = images.imageId
        // INNER JOIN users ON users.userId = images.fk_userId
        // WHERE images.oeffentlich = 1
        // HAVING COUNT(likes.likeId) = 0 OR COUNT(likes.likeId) > 0;

        // SELECT images.imageId, images.titel, images.beschreibung, images.datum, images.ort, images.imageType, images.imageData, images.fk_userId, users.username FROM images
        // INNER JOIN users ON users.userId = images.fk_userId
        // WHERE images.oeffentlich = 1 OR images.imageId IN (SELECT likes.fk_imageId FROM likes)

        // $statement = $this->db->prepare('SELECT images.imageId, images.titel, images.beschreibung, images.datum, images.ort, images.imageType, images.imageData, images.fk_userId, users.username, COUNT(likes.likeId) AS "likes" FROM images
        // INNER JOIN likes ON likes.fk_imageId = images.imageId 
        // INNER JOIN users ON users.userId = images.fk_userId
        // WHERE images.oeffentlich = 1
        // HAVING COUNT(likes.likeId) >= 0 AND users.username != ""');
        // $statement->execute();
        // return $statement;

        // SELECT images.imageId, images.titel, images.beschreibung, images.datum, images.ort, images.imageType, images.imageData, images.fk_userId, users.username, COUNT(likes.likeId) AS "likes" FROM images
        // INNER JOIN likes ON likes.fk_imageId = images.imageId
        // INNER JOIN users ON users.userId = images.fk_userId
        // WHERE images.oeffentlich = 1 AND user.username NOT NULL
        // HAVING COUNT(likes.likeId) = 0 OR COUNT(likes.likeId) > 0;
    }

    /* When user IS logged in - Show first posts from other people you follow and then public posts - Show already liked posts */
    public function alreadyLikedPosts(){
        $statement = $this->db->prepare('SELECT images.imageId, images.titel, images.beschreibung, images.datum, images.ort, images.imageType, images.imageData, images.fk_userId, users.username, COUNT(likes.likeId) AS "likes" FROM images
        LEFT JOIN likes ON likes.fk_imageId = images.imageId
        INNER JOIN users ON users.userId = images.fk_userId
        WHERE images.fk_userId IN (SELECT likes.fk_userId FROM likes WHERE likes.fk_userId = :liked)
        HAVING users.username != NULL');
        $statement->bindParam(':liked', $_SESSION['id'], PDO::PARAM_STR);
        $statement->execute();
        return $statement;
    }

    /* When user IS logged in - Show first posts from other people you follow and then public - Not liked posts */
    public function unlikedPosts(){
        $statement = $this->db->prepare('SELECT images.imageId, images.titel, images.beschreibung, images.datum, images.ort, images.imageType, images.imageData, images.fk_userId, users.username, COUNT(likes.likeId) AS "likes" FROM images
        LEFT JOIN likes ON likes.fk_imageId = images.imageId
        INNER JOIN users ON users.userId = images.fk_userId
        WHERE users.userId NOT IN (SELECT likes.fk_userId FROM likes WHERE likes.fk_userId = :liked)');
        $statement->bindParam(':liked', $_SESSION['id'], PDO::PARAM_STR);
        $statement->execute();
        return $statement;
    }

    /* If someone wants to like a post */
    public function likePost($id){
        $statement = $this->db->prepare('INSERT INTO likes (fk_imageId, likerId) VALUES (:imageId, :liker)');
        $statement->bindParam(':imageId', $id, PDO::PARAM_STR); /* Von wem das Bild kommt */
        $statement->bindParam(':liker', $_SESSION['id'], PDO::PARAM_STR); /* Wer das Bild liken will */
        $statement->execute();
        return $statement;
    }

    /* If somene liked a post so he cann also remove the like */
    public function unlikePost($id){
        $statement = $this->db->prepare('DELETE FROM likes WHERE likes.fk_imageId = :imageId AND likes.likerId = :liker');
        $statement->bindParam(':imageId', $id, PDO::PARAM_STR); /* Von wem das Bild kommt */
        $statement->bindParam(':liker', $_SESSION['id'], PDO::PARAM_STR); /* Wer das Bild nicht mehr liken will */
        $statement->execute();
        return $statement;
    }

    /* Count how many likes a post has */
    public function likes(){
        $statement = $this->db->prepare('SELECT COUNT(likeId) AS "Likes", fk_imageId, fk_userId FROM likes');
        $statement->execute();
        return $statement;
    }

    /* Check if user already likes a post */
    public function alreadyLike($id){
        $statement = $this->db->prepare('SELECT users.userId FROM users 
        INNER JOIN likes ON likes.fk_userId = users.userId WHERE likes.fk_userId = :id AND likes.followsId = :whoFollowsId');
        $statement->bindParam(':id', $id, PDO::PARAM_STR); /* Id des Bildes */
        $statement->bindParam(':whoFollowsId', $_SESSION['id'], PDO::PARAM_STR); /* Id des aktuellen Benutzers */
        $statement->execute();
        return $statement;
    }

    /* ---------- PROFILE SECTION ---------- */
    /* To your actual profile */
    public function profile($id){
        $statement = $this->db->prepare('SELECT * FROM users WHERE userId = :id');
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

    /* ---------- FOLLOW SECTION ---------- */
    /* If a user wants to follow another user */
    public function follow($id){
        $statement = $this->db->prepare('INSERT INTO followers (userId, followsId) VALUES (:userId, :follows)');
        $statement->bindParam(':userId', $id, PDO::PARAM_STR); /* User der gefolgt wird */
        $statement->bindParam(':follows', $_SESSION['id'], PDO::PARAM_STR); /* Wer den User folgen will */
        $statement->execute();
        return $statement;
    }

    /* Unfollow a person */
    public function unfollow($id){
        $statement = $this->db->prepare('DELETE FROM followers WHERE followers.userId = :id AND followers.followsId = :User');
        $statement->bindParam(':id', $id, PDO::PARAM_STR); /* User der gefolgt wird */
        $statement->bindParam(':User', $_SESSION['id'], PDO::PARAM_STR); /* Wer folgen den User folgen will */
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

    /* Check if user already follows a person */
    public function alreadyFollows($id){
        $statement = $this->db->prepare('SELECT users.userId FROM users 
        INNER JOIN followers ON followers.userId = users.userId WHERE followers.userId = :id AND followers.followsId = :whoFollowsId');
        $statement->bindParam(':id', $id, PDO::PARAM_STR);
        $statement->bindParam(':whoFollowsId', $_SESSION['id'], PDO::PARAM_STR);
        $statement->execute();
        return $statement;
    }
}