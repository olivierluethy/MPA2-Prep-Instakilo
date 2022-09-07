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
            $allPosts = $allPosts -> fetchAll();

            $alreadyLikedPosts = $Instakilo -> alreadyLikedPosts($_SESSION['id']);
            $alreadyLikedPosts = $alreadyLikedPosts -> fetchAll();

            $unlikedPosts = $Instakilo -> unlikedPosts($_SESSION['id']);
            $unlikedPosts = $unlikedPosts -> fetchAll();
        }else {
            /* Show public images */
            $publicPosts = $Instakilo -> publicPosts();
            $publicPosts = $publicPosts -> fetchAll();
        }

        // Check if the user is already logged in, if yes then redirect him to index page
        if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) {
            $allPostsCounter = 0;
            $alreadyLikedPostsCounter = 0;
            $unlikedPostsCounter = 0;

            foreach ($allPosts as $allPosts2){
                $allPostsCounter++;
            }
            foreach ($alreadyLikedPosts as $alreadyLikedPosts2){
                $alreadyLikedPostsCounter++;
            }
            foreach ($unlikedPosts as $unlikedPosts){
                $unlikedPostsCounter++;
            }
        }else {
            $publicPostsCounter = 0;

            foreach ($publicPosts as $publicPosts2){
                $publicPostsCounter++;
            }
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
            $profile = $profile -> fetchAll();

            $followers = $Instakilo -> followers($_SESSION['id']);
            $followers = $followers -> fetchAll();

            $follows = $Instakilo -> follows($_SESSION['id']);
            $follows = $follows -> fetchAll();

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
        $profile = $profile -> fetchAll();

        $visit = $Instakilo -> visitProfile($id);
        $visit = $visit -> fetchAll();

        $followers = $Instakilo -> followers($id);
        $followers = $followers -> fetchAll();

        $follows = $Instakilo -> follows($id);
        $follows = $follows -> fetchAll();

        $alreadyFollows = $Instakilo -> alreadyFollows($id, $_SESSION['id']);
        $alreadyFollows = $alreadyFollows -> fetchAll();

        $alreadyFollowsCounter = 0;
        foreach($alreadyFollows as $alreadyFollows2){
            $alreadyFollowsCounter++;
        }

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