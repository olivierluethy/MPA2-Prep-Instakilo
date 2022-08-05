<?php

class InstakiloController{
    public function index(){
        // Initialize the session
        session_start();

        $Instakilo = new Instakilo();
        $pdo = connectDatabase();
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        /* Get posts */
        $posts = $Instakilo -> posts($_SESSION['id']);
        $posts = $posts -> fetchAll();

        // $followedPosts = $Instakilo -> followedPosts();
        // $followedPosts = $followedPosts -> fetchAll();

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

        $visit = $Instakilo -> visitProfile($id);
        $visit = $visit -> fetchAll();

        require 'app/Views/visitProfile.view.php';
    }

    public function follow(){
        // Initialize the session
        session_start();

        $Instakilo = new Instakilo();
        $pdo = connectDatabase();
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $id = $_GET['id'];

        $Instakilo->follow(/* User der gefolgt wird */$id, /* Wer folgen den User folgen will */$_SESSION['id']);

        header('Location: http://localhost/Instakilo/');
    }
}