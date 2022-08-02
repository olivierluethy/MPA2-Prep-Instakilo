<?php
$mostLikedPostsCounter = 0;
$followedPostsCounter = 0;

foreach ($mostLikedPosts as $mostFollowedPosts2){
    $mostFollowedPostsCounter++;
}
foreach ($followedPosts as $followedPosts2){
    $followedPostsCounter++;
}
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
            echo $mostFollowedPostsCounter;
            echo "<div class='grid-container'>";
            foreach ($mostFollowedPosts as $mostFollowedPosts2){
                echo "<div></div>";
            }
            echo "</div>";
        }else {
            echo "<h1 class='noFollowsText'>Zurzeit keine Beiträge vorhanden</h1>";
        }
        
    }else {
        if($followedPostsCounter > 0){
            echo "<div class='grid-container'>";
            foreach ($followedPosts as $followedPosts2){
                echo "<div></div>";
            }
            echo "</div>";
        }else {
            echo "<h1 class='noFollowsText'>Folge Personen um deren Inhalte zu sehen</h1>";
        }
    }?>

    </main>

    <?php
    include("footer.view.php");
    ?>
    <script src="public/js/main.js"></script>
    <script src="public/js/footer.js"></script>
</body>

</html>