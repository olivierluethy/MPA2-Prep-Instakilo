<?php
$alreadyFollowsCounter = 0;
foreach($alreadyFollows as $alreadyFollows2){
    $alreadyFollowsCounter++;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="public/css/style.css">
    <link rel="stylesheet" href="public/css/visitProfile.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="shortcut icon" href="assets/favicon.ico">
    <title>Profile</title>
</head>

<body>
    <?php
    include("nav.view.php");
    ?>

    <div class="visit-container">
        <?php
        foreach($profile as $profile2){
            echo "<div class='item2'>
                <img src='assets/profile.png' alt=''>
            </div>
            <div class='item3'><h2>". $profile2['username'] ."</h2></div>";
        }
        foreach($followers as $followers2){
            echo "
                <div class='item4'>
                    <h3>" . $followers2['Followers'] . "</h3>
                    <h3>Followers</h3>
                </div>
            ";
        }
        foreach($follows as $follows2){
            echo "
                <div class='item5'>
                    <h3>" . $follows2['Follows'] . "</h3>
                    <h3>Follows</h3>
                </div>
            ";
        }
        foreach($profile as $profile2){
            echo "
                <div class='item6'>";
                    if($profile2['description'] == ""){
                        echo "<p>Keine Beschreibung</p>";
                    }else{
                        echo "<textarea name='' id='' cols='85' rows='10'>" . $profile2['description'] . "</textarea><br>";
                    }
                    // Check if the user is already logged in, if yes then redirect him to index page
                    if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) {

                        if($_SESSION['id'] != $profile2['userId']){
                            if($alreadyFollowsCounter > 0){
                                echo "<button onclick='unfollow(" . $profile2['userId'] . ")'>Unfollow</button>";
                            }else {
                                echo "<button onclick='follow(" . $profile2['userId'] . ")'>Follow</button>";
                            }
                        }
                    }else {
                        echo "<button onclick='goToLogin()'>Follow</button>";
                    }
                echo "</div>";
        }
        ?>
    </div>

    <!-- The Modal -->
    <div id="myModal" class="modal">
        <!-- Modal content -->
        <div class="modal-content">
            <div class="modal-header">
                <span class="close">&times;</span>
                <h2>Upload Image</h2>
            </div>
            <div class="modal-body">
                <form action="imageUpload" method="POST" enctype="multipart/form-data"><br>
                    <label for="file">Choose File:</label><br>
                    <input type="file" id="myFile" name="filename"><br>
                    <label for="title">Titel:</label><br>
                    <input type="text" id="title" name="title" placeholder="Bitte Titel eingeben"><br><br>
                    <label for="beschreibung">Beschreibung:</label><br>
                    <textarea id="story" name="beschreibung" rows="5" cols="71"
                        placeholder="Schreiben sie eine Beschreibung"></textarea><br><br>
                    <label for="datum">Datum:</label><br>
                    <input type="date" id="datum" name="datum"><br><br>
                    <label for="ort">Ort:</label><br>
                    <input type="text" id="ort" name="ort" placeholder="Bitte Ort eingeben"><br><br>
                    <label for="oeffentlich">Öffentlich:</label><br>
                    <input type="checkbox" id="oeffentlich" name="oeffentlich" value="Yes"><br><br>
                    <input type="submit" value="Hochladen">
                </form>
            </div>
        </div>
    </div>
    <?php
    include("footer.view.php");
    ?>
    <script src="public/js/main.js"></script>
    <script src="public/js/footer.js"></script>
    <script src="public/js/validationForUpload.js"></script>
</body>

</html>