<?php

class ImageUploadController{
    public function index(){
        $imageUpload = new ImageUpload();

        // Initialize the session
        session_start();

        require_once 'app/Views/login/config.php';

        $pdo = connectDatabase();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            /* For Image Upload */
            if(count($_FILES) > 0) {
                if(is_uploaded_file($_FILES['filename']['tmp_name'])) {
                    $imgData =addslashes(file_get_contents($_FILES['filename']['tmp_name']));
                    $imageProperties = getimageSize($_FILES['filename']['tmp_name']);

                    $public = false;

                    $title = $_POST['title'];
                    $beschreibung = $_POST['beschreibung'];
                    $datum = $_POST['datum'];
                    $ort = $_POST['ort'];
                    $oeffentlich = $_POST['oeffentlich'];

                    if ($oeffentlich == 'Yes') {
                        $public = 1;
                    }else {
                        $public = 0;
                    }
                
                    $imageUpload->uploadImage($title, $beschreibung, $datum, $ort, $public, $imageProperties['mime'], $imgData, $_SESSION['id']);

                    header('Location: http://localhost/Instakilo/');
                }
            }
        }
    }
}