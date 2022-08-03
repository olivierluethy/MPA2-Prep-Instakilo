<?php

class InstakiloController{
    public function index(){
        // Initialize the session
        session_start();

        $Instakilo = new Instakilo();
        $pdo = connectDatabase();
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        /* Get most liked posts */
        $mostLikedPosts = $Instakilo -> mostLikedPosts();
        $mostLikedPosts = $mostLikedPosts -> fetchAll();

        // $followedPosts = $Instakilo -> followedPosts();
        // $followedPosts = $followedPosts -> fetchAll();

        require 'app/Views/main.view.php';
    }
}