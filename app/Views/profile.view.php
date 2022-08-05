<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="public/css/style.css">
    <link rel="stylesheet" href="public/css/profile.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="shortcut icon" href="assets/favicon.ico">
    <title>Profile</title>
</head>

<body>
    <?php
    include("nav.view.php");
    ?>

    <div class="profile-container">
        <div class="item2">
            <div class="setting">
                Home
            </div>
            <div class="setting">
                Profile Image
            </div>
            <div class="setting">
                Username
            </div>
            <div class="setting">
                Beschreibung
            </div>
            <div class="setting">
                Posts Manager
            </div>
        </div>
        <div class="item3">
            <?php
            foreach($profile as $profile2){
                echo "
                <div class='profile-name'>
                    <img src='assets/profile.png' alt=''>
                    <h1>" . $profile2["username"] . "</h1>
                </div>";
            }?>
            <div class='follow'>
                <?php
                foreach($followers as $followers2){
                    echo "
                    <div class='followers'>
                            <h3>" . $followers2['Followers'] . "</h3>
                            <h3>Followers</h3>
                        </div>
                    ";
                }?>
                <?php
                foreach($follows as $follows2){
                    echo "
                    <div class='follows'>
                            <h3>" . $follows2['Follows'] . "</h3>
                            <h3>Follows</h3>
                        </div>
                    ";
                }?>
            </div>
            <?php
                foreach($profile as $profile2){
                    echo "
                    <div class='description'>
                        <textarea name='' id='' cols='85' rows='25'>" . $profile2['description'] . "</textarea>
                    </div>
                    ";
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