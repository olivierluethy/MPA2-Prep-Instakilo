<?php

class InstakiloController{
    public function index(){
        // Initialize the session
        session_start();

        $Data = new Framework();
        $pdo = connectDatabase();
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        /* Get most liked posts */
        $mostLikedPosts = $Data -> mostLikedPosts();
        $mostLikedPosts = $mostLikedPosts -> fetchAll();

        $followedPosts = $Data -> followedPosts();
        $followedPosts = $followedPosts -> fetchAll();

        require 'app/Views/main.view.php';
    }
}