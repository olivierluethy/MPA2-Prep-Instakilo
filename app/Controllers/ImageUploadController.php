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
			if (!empty($_FILES['filename']['tmp_name'])) {
			$imgData = file_get_contents($_FILES['filename']['tmp_name']);
			    // never assume the upload succeeded
				if ($_FILES['filename']['error'] !== UPLOAD_ERR_OK) {
					die("Upload failed with error code " . $_FILES['filename']['error']);
				}
			
				$imageProperties = getimagesize($_FILES['filename']['tmp_name']);
			
				if ($imageProperties === false) {
					die("Unable to determine image type of uploaded file. Please upload an image file!");
				}
			
				/* Check if File is an image */
				$allowedImageTypes = array(IMAGETYPE_GIF, IMAGETYPE_JPEG, IMAGETYPE_PNG);
				if (!in_array($imageProperties[2], $allowedImageTypes)) {
					die("Not a GIF, JPEG, or PNG image");
				} else {
					if ($_FILES['filename']['size'] > 500000) {
						die("<strong><h2>Sorry, your file is too large.<br>Please use an image that is smaller or equal to 500KB!</h2></strong><br>
							<a href='home'><button>Go Back!</button></a>");
					} else {
						$titel = $_POST['titel'];
						$beschreibung = $_POST['beschreibung'];
						$datum = $_POST['datum'];
						$ort = $_POST['ort'];
						$oeffentlich = ($_POST['oeffentlich'] == 'Yes') ? 1 : 0;
			
						$imageUpload->uploadImage($titel, $beschreibung, $datum, $ort, $oeffentlich, $imageProperties['mime'], $imgData, $_SESSION['id']);
			
						header("Location: home");
						exit();
					}
				}
			}
		}
	}
}