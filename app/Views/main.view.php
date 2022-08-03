<?php
$mostFollowedPostsCounter = 0;
$followedPostsCounter = 0;

foreach ($mostLikedPosts as $mostFollowedPosts2){
    $mostFollowedPostsCounter++;
}
// foreach ($followedPosts as $followedPosts2){
//     $followedPostsCounter++;
// }
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="public/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="shortcut icon" href="assets/favicon.ico">
    <title>Instakilo</title>
</head>

<body>
    <?php
    include("nav.view.php");
    ?>
    <main>
        <?php
        // Check if the user is logged in, if not then redirect him to login page
        if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
            if ($mostLikedPostsCounter > 0){
                
            }else {
                echo "<h1 class='noFollowsText'>Zurzeit keine Beiträge vorhanden</h1>";
            }
        }
            
        echo $mostFollowedPostsCounter;
        echo "<div class='grid-container'>";
        foreach ($mostLikedPosts as $mostLikedPosts2){
            echo "<div>
                <p>". $mostLikedPosts2['titel'] . "</p>
                <img src='imageView.php?image_id=". $mostLikedPosts2['imageId'] ."' />
            </div>";
        }
        echo "</div>";
        // }else {
        //     if($followedPostsCounter > 0){
        //         echo "<div class='grid-container'>";
        //         foreach ($followedPosts as $followedPosts2){
        //             echo "<div></div>";
        //         }
        //         echo "</div>";
        //     }else {
        //         echo "<h1 class='noFollowsText'>Folge Personen um deren Inhalte zu sehen</h1>";
        //     }
        // }?>
    </main>

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
                    <textarea id="story" name="beschreibung" rows="5" cols="33" placeholder="Schreiben sie eine Beschreibung"></textarea><br><br>
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