<?php

class InstakiloController{
    public function index(){
        // Initialize the session
        session_start();

        $Instakilo = new Instakilo();
        $pdo = connectDatabase();
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Check if the user is already logged in, if yes then redirect him to index page
        if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) {
            /* Show posts the user follows and public to */
            $allPosts = $Instakilo -> allPosts();

            $alreadyLikedPosts = $Instakilo -> alreadyLikedPosts();

            $unlikedPosts = $Instakilo -> unlikedPosts();
        }else {
            /* Show public images */
            $publicPosts = $Instakilo -> publicPosts();
        }

        // Check if the user is already logged in, if yes then redirect him to index page
        if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) {
            $allPostsCounter = count($allPosts);
            $alreadyLikedPostsCounter = count($alreadyLikedPosts);
            $unlikedPostsCounter = count($unlikedPosts);
        }else {
            $publicPostsCounter = count($publicPosts);
        }
        
        require 'app/Views/main.view.php';
    }

    public function profile(){
        // Initialize the session
        session_start();

        // Check if the user is already logged in, if yes then redirect him to index page
        if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) {

            $Instakilo = new Instakilo();
            $pdo = connectDatabase();
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $profile = $Instakilo -> profile($_SESSION['id']);

            $followers = $Instakilo -> followers($_SESSION['id']);

            $follows = $Instakilo -> follows($_SESSION['id']);

            require 'app/Views/profile.view.php';
        }else{
            header("location: login");
        }
    }

    public function visitProfile(){
        // Initialize the session
        session_start();

        $Instakilo = new Instakilo();
        $pdo = connectDatabase();
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $id = $_GET['id'];

        $profile = $Instakilo -> profile($id);

        $visit = $Instakilo -> visitProfile($id);

        $followers = $Instakilo -> followers($id);

        $follows = $Instakilo -> follows($id);

        $alreadyFollows = $Instakilo -> alreadyFollows($id, $_SESSION['id']);

        $alreadyFollowsCounter = count($alreadyFollows);

        require 'app/Views/visitProfile.view.php';
    }

    public function follow(){
        // Initialize the session
        session_start();

        $Instakilo = new Instakilo();
        $pdo = connectDatabase();
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $id = $_GET['id'];

        $Instakilo->follow($id);

        header('Location: http://localhost/Instakilo/visitProfile?id='.$id);
    }

    public function unfollow(){
        // Initialize the session
        session_start();

        $Instakilo = new Instakilo();
        $pdo = connectDatabase();
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $id = $_GET['id'];

        $Instakilo->unfollow($id);

        header('Location: http://localhost/Instakilo/visitProfile?id='.$id);
    }

    public function likePost(){
        // Initialize the session
        session_start();

        $Instakilo = new Instakilo();
        $pdo = connectDatabase();
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $id = $_GET['id'];

        $Instakilo->likePost($id);
        
        header("location: home");
    }

    public function unlikePost(){
        // Initialize the session
        session_start();

        $Instakilo = new Instakilo();
        $pdo = connectDatabase();
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $id = $_GET['id'];

        $Instakilo->unlikePost($id);
        
        header("location: home");
    }
}