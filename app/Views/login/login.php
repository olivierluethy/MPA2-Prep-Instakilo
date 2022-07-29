<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="public/css/olivierstyle.css">
    <title>Error Page</title>
</head>

<body>
    <?php
// Initialize the session
session_start();
session_destroy();
session_start();
$_SESSION['email'] = "";

// Check if the user is already logged in, if yes then redirect him to index page
if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) {
    header("location: home");
    exit;
}else if($_SERVER['REQUEST_METHOD'] !== 'POST'){
    header("location: loginRegister");
}

// Include config file
require_once "config.php";

// Define variables and initialize with empty values
$email = $password = "";
$email_err = $password_err = "";

// Processing form data when form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Check if email is empty
    if (empty(trim($_POST["email_login"]))) {
        $email_err = "Please enter email.";
    } else {
        $email = trim($_POST["email_login"]);
    }

    // Check if password is empty
    if (empty(trim($_POST["passwort_login"]))) {
        $password_err = "Please enter your password.";
    } else {
        $password = trim($_POST["passwort_login"]);
    }

    // Validate credentials
    if (empty($email_err) && empty($password_err)) {
        // Prepare a select statement
        // Prepare a select statement
        $sql = "SELECT id, email, password FROM users WHERE email = :email";
        
        if($stmt = $pdo->prepare($sql)){
            // Bind variables to the prepared statement as parameters
            $stmt->bindParam(":email", $email, PDO::PARAM_STR);
            
            // Set parameters
            $email = trim($_POST["email_login"]);
            
            // Attempt to execute the prepared statement
            if($stmt->execute()){
                // Check if username exists, if yes then verify password
                if($stmt->rowCount() == 1){
                    if($row = $stmt->fetch()){
                        $id = $row["id"];
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
                                <button id='sendEmail'>Passwort zurücksetzen</button>
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

    // Close connection
    unset($pdo);
}
?>
</body>

</html>