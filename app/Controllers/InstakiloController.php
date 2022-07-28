<?php

class InstakiloController{
    public function index(){
        $Data = new Framework();
        $pdo = connectDatabase();
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $daten = $Data -> index();
        $daten = $daten -> fetchAll();

        require 'app/Views/main.view.php';
    }
}