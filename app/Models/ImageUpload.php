<?php
class ImageUpload
{
    public $db;

    public function __construct()
    {
        $this->db = connectDatabase();
    }

    /* Bild hochladen - wenn man eingeloggt ist */
    public function uploadImage($title, $description, $date, $location, $isPublic, $imageProperties, $imgData, $userId) {
		// Validate input
		$errors = [];
		
		// Check if title is empty
		if (empty($title)) {
			$errors[] = "Title is required";
		}
		
		// Check if title is longer than 255 characters
		if (strlen($title) > 255) {
			$errors[] = "Title must be 255 characters or less";
		}
		
		// Check if description is longer than 1000 characters
		if (!empty($description) && strlen($description) > 1000) {
			$errors[] = "Description must be 1000 characters or less";
		}
		
		// Check if location is longer than 255 characters
		if (!empty($location) && strlen($location) > 255) {
			$errors[] = "Location must be 255 characters or less";
		}
		
		// Check if isPublic is either 0 or 1
		if (!in_array($isPublic, ['0', '1'])) {
			$errors[] = "Invalid value for public status";
		}
		
		// Check if imageProperties is an array and if it contains type and mime keys
		if (!is_array($imageProperties) || !isset($imageProperties['type']) || !isset($imageProperties['mime'])) {
			$errors[] = "Invalid image properties";
		}
		
		// Check if imgData is a resource
		if (!is_resource($imgData)) {
			$errors[] = "Invalid image data";
		}
		
		// If there are errors, return them
		if (!empty($errors)) {
			return ['success' => false, 'errors' => $errors];
		}
		
		// Otherwise, insert the image into the database
		$statement = $this->db->prepare("INSERT INTO `images` (title, description, date, location, isPublic, imageType, imageData, fk_userId) VALUES (:title, :description, :date, :location, :isPublic, :imageType, :imageData, :userId)");
		$statement->bindParam(':title', $title, PDO::PARAM_STR);
		$statement->bindParam(':description', $description, PDO::PARAM_STR);
		$statement->bindParam(':date', $date, PDO::PARAM_STR);
		$statement->bindParam(':location', $location, PDO::PARAM_STR);
		$statement->bindParam(':isPublic', $isPublic, PDO::PARAM_STR);
		$statement->bindParam(':imageType', $imageProperties['type'], PDO::PARAM_STR);
		$statement->bindParam(':imageData', $imgData, PDO::PARAM_LOB);
		$statement->bindParam(':userId', $userId, PDO::PARAM_STR);
		$statement->execute();
		
		return ['success' => true];
	}	
}