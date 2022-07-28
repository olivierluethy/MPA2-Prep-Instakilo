<?php
class ImageUpload
{
    public $db;

    public function __construct()
    {
        $this->db = connectDatabase();
    }

    public function index(){
        $statement = $this->db->prepare('SELECT * FROM Person');
        $statement->execute();
        return $statement;
    }

    public function create($name, $vorname, $email)
    {
        $isValid = true;
        $name = htmlspecialchars($_POST['name']);
        $vorname = htmlspecialchars($_POST['vorname']);
        $email = htmlspecialchars($_POST['email']);
        if (strpos($email, "@") === false) {
            $isValid = false;
        }

        if ($isValid) {
            $statement = $this->db->prepare("INSERT INTO `Person` (name, vorname, email) VALUES 
            (:name, :vorname, :email)");
            $statement->bindParam(':name', $name, PDO::PARAM_STR);
            $statement->bindParam(':vorname', $vorname, PDO::PARAM_STR);
            $statement->bindParam(':email', $email, PDO::PARAM_STR);
            $statement->execute();
        }
        return $isValid;
    }

    public function update($name, $vorname, $email, $id)
    {
        $isValid = true;
        $name = htmlspecialchars($_POST['name']);
        $vorname = htmlspecialchars($_POST['vorname']);
        $email = htmlspecialchars($_POST['email']);
        if (strpos($email, "@") === false) {
            $isValid = false;
        }

        if ($isValid) {
            $statement = $this->db->prepare('UPDATE `Person` SET name = :name, vorname = :vorname, email = :email WHERE id = :id');
            $statement->bindParam(':name', $name, PDO::PARAM_STR);
            $statement->bindParam(':vorname', $vorname, PDO::PARAM_STR);
            $statement->bindParam(':email', $email, PDO::PARAM_STR);
            $statement->bindParam(':id', $id, PDO::PARAM_STR);
            $statement->execute();
        }
        return $isValid;
    }

    public function delete($id)
    {
        $statement = $this->db->prepare('DELETE FROM `Person` WHERE id = :id');
        $statement->bindParam(':id', $id, PDO::PARAM_STR);
        $statement->execute();
    }
}