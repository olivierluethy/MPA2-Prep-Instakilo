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
        return $statement->fetch();
    }

    /* When user IS NOT logged in - Show public posts */
    public function publicPosts(){
        $statement = $this->db->prepare('SELECT images.imageId, images.titel, images.beschreibung, images.datum, images.ort, images.imageType, images.imageData, images.fk_userId, users.username, COUNT(likes.likeId) AS "likes" FROM images
        INNER JOIN users ON users.userId = images.fk_userId
        LEFT JOIN likes ON likes.fk_imageId = images.imageId
        WHERE images.oeffentlich = 1');
        $statement->execute();
        return $statement->fetch();
    }

    /* When user IS logged in - Show first private posts from people you follow and and then public posts - Show already liked posts */
    public function alreadyLikedPosts(){
        $statement = $this->db->prepare('SELECT images.imageId, images.titel, images.beschreibung, images.datum, images.ort, images.imageType, images.imageData, images.fk_userId, users.username, COUNT(likes.likeId) AS "likes" FROM images
        LEFT JOIN likes ON likes.fk_imageId = images.imageId
        INNER JOIN users ON users.userId = images.fk_userId
        WHERE :liked IN (SELECT likes.fk_userId FROM likes WHERE likes.fk_userId = :liked)
        HAVING images.imageId IS NOT NULL');
        $statement->bindParam(':liked', $_SESSION['id'], PDO::PARAM_STR);
        $statement->execute();
        return $statement->fetch();
    }

    /* When user IS logged in - Show first private posts from people you follow and and then public posts - Not liked posts */
    public function unlikedPosts(){
        $statement = $this->db->prepare('SELECT images.imageId, images.titel, images.beschreibung, images.datum, images.ort, images.imageType, images.imageData, images.fk_userId, users.username, COUNT(likes.likeId) AS "likes" FROM images
        LEFT JOIN likes ON likes.fk_imageId = images.imageId
        INNER JOIN users ON users.userId = images.fk_userId
        WHERE :liked NOT IN (SELECT likes.fk_userId FROM likes WHERE likes.fk_userId = :liked)
        HAVING images.imageId IS NOT NULL');
        $statement->bindParam(':liked', $_SESSION['id'], PDO::PARAM_STR);
        $statement->execute();
        return $statement->fetch();
    }

    /* If someone wants to like a post */
    public function likePost($id){
        $statement = $this->db->prepare('INSERT INTO likes (fk_imageId, fk_userId) VALUES (:imageId, :liker)');
        $statement->bindParam(':imageId', $id, PDO::PARAM_STR); /* Von wem das Bild kommt */
        $statement->bindParam(':liker', $_SESSION['id'], PDO::PARAM_STR); /* Wer das Bild liken will */
        $statement->execute();
        return $statement->fetch();
    }

    /* If somene liked a post so he cann also remove the like */
    public function unlikePost($id){
        $statement = $this->db->prepare('DELETE FROM likes WHERE likes.fk_imageId = :imageId AND likes.fk_userId = :liker');
        $statement->bindParam(':imageId', $id, PDO::PARAM_STR); /* Von wem das Bild kommt */
        $statement->bindParam(':liker', $_SESSION['id'], PDO::PARAM_STR); /* Wer das Bild nicht mehr liken will */
        $statement->execute();
        return $statement->fetch();
    }

    /* Count how many likes a post has */
    public function likes(){
        $statement = $this->db->prepare('SELECT COUNT(likeId) AS "Likes", fk_imageId, fk_userId FROM likes');
        $statement->execute();
        return $statement->fetch();
    }

    /* Check if user already likes a post */
    public function alreadyLike($id){
        $statement = $this->db->prepare('SELECT users.userId FROM users 
        INNER JOIN likes ON likes.fk_userId = users.userId WHERE likes.fk_userId = :id AND likes.followsId = :whoFollowsId');
        $statement->bindParam(':id', $id, PDO::PARAM_STR); /* Id des Bildes */
        $statement->bindParam(':whoFollowsId', $_SESSION['id'], PDO::PARAM_STR); /* Id des aktuellen Benutzers */
        $statement->execute();
        return $statement->fetch();
    }

    /* ---------- PROFILE SECTION ---------- */
    /* To your actual profile */
    public function profile($id){
        $statement = $this->db->prepare('SELECT * FROM users WHERE userId = :id');
        $statement->bindParam(':id', $id, PDO::PARAM_STR);
        $statement->execute();
        return $statement->fetch();
    }

    /* To visit the profile from someone else */
    public function visitProfile($id){
        $statement = $this->db->prepare('SELECT COUNT(followers.followId) AS "Followers", COUNT(followers.followsId) AS "Follows", users.userId, users.username, users.description, users.imageType, users.imageData FROM followers 
        INNER JOIN users ON users.userId = followers.userId WHERE users.userId = :id');
        $statement->bindParam(':id', $id, PDO::PARAM_STR);
        $statement->execute();
        return $statement->fetch();
    }

    /* ---------- FOLLOW SECTION ---------- */
    /* If a user wants to follow another user */
    public function follow($id){
        $statement = $this->db->prepare('INSERT INTO followers (userId, followsId) VALUES (:userId, :follows)');
        $statement->bindParam(':userId', $id, PDO::PARAM_STR); /* User der gefolgt wird */
        $statement->bindParam(':follows', $_SESSION['id'], PDO::PARAM_STR); /* Wer den User folgen will */
        $statement->execute();
        return $statement->fetch();
    }

    /* Unfollow a person */
    public function unfollow($id){
        $statement = $this->db->prepare('DELETE FROM followers WHERE followers.userId = :id AND followers.followsId = :User');
        $statement->bindParam(':id', $id, PDO::PARAM_STR); /* User der gefolgt wird */
        $statement->bindParam(':User', $_SESSION['id'], PDO::PARAM_STR); /* Wer folgen den User folgen will */
        $statement->execute();    
        return $statement->fetch();
    }

    /* Count how many followers someone has */
    public function followers($id){
        $statement = $this->db->prepare('SELECT COUNT(followId) AS "Followers" FROM followers WHERE userId = :id');
        $statement->bindParam(':id', $id, PDO::PARAM_STR);
        $statement->execute();
        return $statement->fetch();
    }

    /* Count how many people someone follows */
    public function follows($id){
        $statement = $this->db->prepare('SELECT COUNT(followId) AS "Follows" FROM followers WHERE followsId = :id');
        $statement->bindParam(':id', $id, PDO::PARAM_STR);
        $statement->execute();
        return $statement->fetch();
    }

    /* Check if user already follows a person */
    public function alreadyFollows($id){
        $statement = $this->db->prepare('SELECT users.userId FROM users 
        INNER JOIN followers ON followers.userId = users.userId WHERE followers.userId = :id AND followers.followsId = :whoFollowsId');
        $statement->bindParam(':id', $id, PDO::PARAM_STR);
        $statement->bindParam(':whoFollowsId', $_SESSION['id'], PDO::PARAM_STR);
        $statement->execute();
        return $statement->fetch();
    }
}