<?php

class LoginController{
    public function index(){
        $Data = new Framework();
        $pdo = connectDatabase();
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Initialize the session
        session_start();

        $daten = $Data -> index();
        $daten = $daten -> fetchAll();

        require 'app/Views/viewData.view.php';
    }

    public function create(){
        $Data = new Framework();
        $pdo = connectDatabase();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $name = $_POST['name'];
            $vorname = $_POST['vorname'];
            $email = $_POST['email'];

            $Data -> create($name, $vorname, $email);

            header('Location: http://localhost/Constant_Framework/');
        }

        require 'app/Views/createData.view.php';
    }

    public function update(){
        $Data = new Framework();
        $id = $_GET['id'];

        $pdo = connectDatabase();
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $name = $_POST['name'];
            $vorname = $_POST['vorname'];
            $email = $_POST['email'];

            $Data -> update($name, $vorname, $email, $id);
            
            header('Location: http://localhost/Constant_Framework/');
        }else{
            $statement = $pdo->prepare('SELECT * FROM Person WHERE id = :id');
            $statement->bindParam(':id', $id, PDO::PARAM_STR);
            $statement->execute();
            $daten = $statement->fetchAll();
        }
        require 'app/Views/editData.view.php';
    }

    public function delete(){
        $Data = new Framework();
        $id = $_GET['id'];

        $pdo = connectDatabase();
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $Data -> delete($id);

        header('Location: http://localhost/Constant_Framework/');
    }
}