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
        <div class="item2">
            <img src="assets/profile.png" alt="">
        </div>
        <div class="item3">Name of creator</div>
        <div class="item4">Number</div>
        <div class="item5">Number</div>
        <div class="item6">
            <textarea name='' id='' cols='85' rows='10'>" . $profile2['beschreibung'] . "</textarea><br>
        <?php
        // Check if the user is already logged in, if yes then redirect him to index page
        if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) {
            echo "<button href='follow'>Follow</button>";
        }else {
            echo "<button href='login'>Follow</button>";
        }
        ?>
        </div>
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