<?php

class ImageUploadController{
    public function index(){

        // Initialize the session
        session_start();

		if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
			header('Location: login');
		}

		$imageUpload = new ImageUpload();
        $pdo = connectDatabase();
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

		if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            /* For Image Upload */
            if(count($_FILES) > 0) {
                if(is_uploaded_file($_FILES['filename']['tmp_name'])) {
                    $imgData = file_get_contents($_FILES['filename']['tmp_name']);
                    $imageProperties = getimageSize($_FILES['filename']['tmp_name']);

					$titel = $_POST['titel'];
					$beschreibung = $_POST['beschreibung'];
					$datum = $_POST['datum'];
					$ort = $_POST['ort'];
					$oeffentlich = $_POST['oeffentlich'];
					if ($oeffentlich == 'Yes') {
						$oeffentlich = 1;
					}else {
						$oeffentlich = 0;
					}

            		$imageUpload->uploadImage($titel, $beschreibung, $datum, $ort, $oeffentlich, $imageProperties['mime'], $imgData, $_SESSION['id']);

					header("location: home");
				}
			}
		}
	}
}