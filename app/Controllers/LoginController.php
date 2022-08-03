<?php

class LoginController{
    public function login(){
        // Initialize the session
        session_start();
        
        // Check if the user is already logged in, if yes then redirect him to index page
        if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) {
            header("location: home");
            exit;
        }
        $pdo = connectDatabase();
		$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        require 'app/Views/login/login.view.php';
        
        // Include config file
        // https://www.php.net/manual/de/function.require-once.php
        define('__ROOT__', dirname(dirname(__FILE__)));
        require_once(__ROOT__.'\Views\login\config.php');

        // Define variables and initialize with empty values
        $email = $password = "";
        $email_err = $password_err = "";

        // Processing form data when form is submitted
        if ($_SERVER["REQUEST_METHOD"] == "POST") {

            // Check if email is empty
            if (empty(trim($_POST["emailuser"]))) {
                $email_err = "Please enter email.";
            } else {
                $email = trim($_POST["emailuser"]);
            }

            // Check if password is empty
            if (empty(trim($_POST["password"]))) {
                $password_err = "Please enter your password.";
            } else {
                $password = trim($_POST["password"]);
            }

            // Validate credentials
            if (empty($email_err) && empty($password_err)) {
                // Prepare a select statement
                $sql = "SELECT userId, email, password FROM users WHERE email = :email";
                
                if($stmt = $pdo->prepare($sql)){
                    // Bind variables to the prepared statement as parameters
                    $stmt->bindParam(":email", $email, PDO::PARAM_STR);
                    
                    // Set parameters
                    $email = trim($_POST["emailuser"]);
                    
                    // Attempt to execute the prepared statement
                    if($stmt->execute()){
                        // Check if username exists, if yes then verify password
                        if($stmt->rowCount() == 1){
                            if($row = $stmt->fetch()){
                                $id = $row["userId"];
                                $email = $row["email"];
                                $hashed_password = $row["password"];
                                if(password_verify($password, $hashed_password)){
                                    // Password is correct, so start a new session
                                    session_start();

                                    // Store data in session variables
                                    $_SESSION["loggedin"] = true;
                                    $_SESSION["id"] = $id;
                                    $_SESSION["email"] = $email;

                                    // Redirect user to index page
                                    header("location: home");
                                } else {
                                    $_SESSION["emailDirection"] = $_POST['email_login'];
                                    // Display an error message if password is not valid
                                    echo 
                                    "<div class='loginPasswordIsWrong'>
                                        <h2>Das von Ihnen eingegebene Passwort war nicht gültig.</h2>
                                        <form action='loginRegister' method='POST'>
                                            <input type='email' name='email' style='display: none' value='". $_SESSION["emailDirection"]."'>
                                            <button type='submit' class='back'>Noch einmal versuchen</button>
                                        </form>
                                        <h2>Falls Sie ihr Passwort vergessen haben, können Sie es hier zurücksetzen.</h2>
                                        <a href='email_versenden'><button id='sendEmail'>Passwort zurücksetzen</button></a>
                                    </div>";
                                }
                            }
                        } else {
                            // Display an error message if email doesn't exist
                            echo "<h2>No account found with that email.</h2>";
                            echo "<a href='loginRegister'><button class='back'>Try again</button></a>";
                        }
                    } else {
                        echo "Oops! Something went wrong. Please try again later.";
                    }

                    // Close statement
                    unset($stmt);
                }
            }
        }
    }

    public function register(){
        $pdo = connectDatabase();
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Check if the user is already logged in, if yes then redirect him to index page
        if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) {
            header("location: home");
            exit;
        }else if($_SERVER['REQUEST_METHOD'] !== 'POST'){
            header("location: loginRegister");
        }else if ($_SERVER["REQUEST_METHOD"] == "POST"){
            // Include config file
            // https://www.php.net/manual/de/function.require-once.php
            define('__ROOT__', dirname(dirname(__FILE__)));
            require_once(__ROOT__.'\Views\login\config.php');
        
            // Define variables and initialize with empty values
            $username = $password = $confirm_password = "";
            $username_err = $password_err = $confirm_password_err = "";
        
            // Processing form data when form is submitted
            // Prepare a select statement
            $sql = "SELECT userId FROM users WHERE email = :email";
            
            if($stmt = $pdo->prepare($sql)){
                // Bind variables to the prepared statement as parameters
                $stmt->bindParam(":email", $param_email, PDO::PARAM_STR);
                
                // Set parameters
                $param_email = trim($_POST["email"]);

                // Close statement
                unset($stmt);
            }
            
            // Validate password
            if(empty(trim($_POST["password"]))){
                $password_err = "Please enter a password.";     
            } elseif(strlen(trim($_POST["password"])) < 6){
                $password_err = "Password must have atleast 6 characters.";
            } else{
                $password = trim($_POST["password"]);
            }
            
            // Validate confirm password
            if(empty(trim($_POST["verypass"]))){
                $confirm_password_err = "Please confirm password.";     
            } else{
                $confirm_password = trim($_POST["verypass"]);
                if(empty($password_err) && ($password != $confirm_password)){
                    $confirm_password_err = "Password did not match.";
                }
            }
    
            // Check input errors before inserting in database
            if(empty($username_err) && empty($password_err) && empty($confirm_password_err)){
                // Prepare an insert statement
                $sql = "INSERT INTO users (email, password) VALUES (:email, :password)";
                
                if($stmt = $pdo->prepare($sql)){
                    // Bind variables to the prepared statement as parameters
                    $stmt->bindParam(":email", $param_email, PDO::PARAM_STR);
                    $stmt->bindParam(":password", $param_password, PDO::PARAM_STR);
                    
                    // Set parameters
                    $param_email = $param_email;
                    $param_password = password_hash($password, PASSWORD_DEFAULT); // Creates a password hash
                    
                    // Attempt to execute the prepared statement
                    if($stmt->execute()){
                        
                        // Prepare a select statement
                        $sql = "SELECT userId, email, password FROM users WHERE email = :email";

                        if($stmt = $pdo->prepare($sql)){
                            // Bind variables to the prepared statement as parameters
                            $stmt->bindParam(":email", $email, PDO::PARAM_STR);
                            
                            // Set parameters
                            $email = $param_email;
                            
                            // Attempt to execute the prepared statement
                            if($stmt->execute()){
                                // After register is successful, auto login
                                if($stmt->rowCount() == 1){
                                    if($row = $stmt->fetch()){
                                        $id = $row["userId"];
                                        $email = $row["email"];
                                        $hashed_password = $row["password"];
                                        if(password_verify($password, $hashed_password)){
                                            // Password is correct, so start a new session
                                            session_start();
                
                                            // Store data in session variables
                                            $_SESSION["loggedin"] = true;
                                            $_SESSION["id"] = $id;
                                            $_SESSION["email"] = $email;
                
                                            // Redirect user to index page
                                            header("location: home");
                                        }
                                    }
                                } else {
                                    // Display an error message if email doesn't exist
                                    echo "<h2>No account found with that email.</h2>";
                                    echo "<a href='loginRegister'><button class='back'>Try again</button></a>";
                                }
                            } else {
                                echo "Oops! Something went wrong. Please try again later.";
                            }
                
                            // Close statement
                            unset($stmt);
                        }
                    } else{
                        echo "Oops! Something went wrong. Please try again later.";
                    }
                    // Close statement
                    unset($stmt);
                }
            }
            // Close connection
            unset($pdo);
        }    
    }

    public function logout(){
        // Initialize the session
        session_start();

        $pdo = connectDatabase();
		$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        require 'app/Views/login/logout.view.php';
    }
}