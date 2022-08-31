<?php
class ImageUpload
{
    public $db;

    public function __construct()
    {
        $this->db = connectDatabase();
    }

    /* Bild hochladen - wenn man eingeloggt ist */
    public function uploadImage($titel, $beschreibung, $datum, $ort, $oeffentlich, $imageProperties, $imgData, $id){
        $titel = htmlspecialchars($_POST['titel']);
		$beschreibung = htmlspecialchars($_POST['beschreibung']);
		$datum = htmlspecialchars($_POST['datum']);
		$ort = htmlspecialchars($_POST['ort']);

        $statement = $this->db->prepare("INSERT INTO `images` (titel, beschreibung, datum, ort, oeffentlich, imageType, imageData, fk_userId) VALUES (:titel, :beschreibung, :datum, :ort, :oeffentlich, :imageType, :imageData, :id)");
		$statement->bindParam(':titel', $titel, PDO::PARAM_STR);
		$statement->bindParam(':beschreibung', $beschreibung, PDO::PARAM_STR);
		$statement->bindParam(':datum', $datum, PDO::PARAM_STR);
		$statement->bindParam(':ort', $ort, PDO::PARAM_STR);
		$statement->bindParam(':oeffentlich', $oeffentlich, PDO::PARAM_STR);
		$statement->bindParam(':imageType', $imageProperties, PDO::PARAM_STR);
		$statement->bindParam(':imageData', $imgData, PDO::PARAM_LOB);
		$statement->bindParam(':id', $id, PDO::PARAM_STR);
		$statement->execute();
    }
}