<?php
class ImageUpload
{
    public $db;

    public function __construct()
    {
        $this->db = connectDatabase();
    }

    public function uploadImage($title, $beschreibung, $datum, $ort, $public,  $imageType, $imgData, $id){
        $statement = $this->db->prepare("INSERT INTO `images` (titel, beschreibung, datum, ort, oeffentlich, imageType, imageData, fk_userId) VALUES (:title, :beschreibung, :datum, :ort, :oeffentlich, :imageType, :imgData, :id)");
		$statement->bindParam(':title', $title, PDO::PARAM_STR);
		$statement->bindParam(':beschreibung', $beschreibung, PDO::PARAM_STR);
		$statement->bindParam(':datum', $datum, PDO::PARAM_STR);
		$statement->bindParam(':ort', $ort, PDO::PARAM_STR);
        $statement->bindParam(':oeffentlich', $public, PDO::PARAM_STR);
        $statement->bindParam(':imageType', $imageType, PDO::PARAM_STR);
        $statement->bindParam(':imgData', $imgData, PDO::PARAM_STR);
        $statement->bindParam(':id', $id, PDO::PARAM_STR);
		$statement->execute();
    }
}