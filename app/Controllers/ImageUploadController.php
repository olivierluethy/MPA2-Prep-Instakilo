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

					// never assume the upload succeeded
					if ($_FILES['filename']['error'] !== UPLOAD_ERR_OK) {
						die("Upload failed with error code " . $_FILES['filename']['error']);
					}

                    $imageProperties = getimageSize($_FILES['filename']['tmp_name']);

					if ($imageProperties === FALSE) {
						die("Unable to determine image type of uploaded file");
						die("Please upload a image file!");
					}

					/* https://stackoverflow.com/questions/9314164/php-uploading-files-image-only-checking */
					/* https://www.w3schools.com/php/php_file_upload.asp */
					/* Check if File is a image */
					if (($imageProperties[2] !== IMAGETYPE_GIF) && ($imageProperties[2] !== IMAGETYPE_JPEG) && ($imageProperties[2] !== IMAGETYPE_PNG)) {
						die("Not a gif/jpeg/png");
					}else {
						if($_FILES['filename']['size'] > 500000){
							die("<strong><h2>Sorry, your file is too large.<br>Please use a image that is smaller or qual to 500KB!</h2></strong><br>
							<a href='home'><button>Go Back!</button></a>");
						}else {
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
	}
}