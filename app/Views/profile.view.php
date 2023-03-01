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
            <div class="profile-name">
                <img src="assets/profile.png" alt="">
                <h1><?= $profile[0]["username"] ?></h1>
            </div>
            <div class="follow">
                <div class="followers">
                    <h3><?= $followers[0]['Followers'] ?></h3>
                    <h3>Followers</h3>
                </div>
                <div class="follows">
                    <h3><?= $follows[0]['Follows'] ?></h3>
                    <h3>Follows</h3>
                </div>
            </div>
            <div class="description">
                <?php if ($profile[0]['description'] == ""): ?>
                <p>Keine Beschreibung vorhanden</p>
                <?php else: ?>
                <p><?= $profile[0]['description'] ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php
    include("imageUpload.view.php");
    include("footer.view.php");
    ?>
    <script src="public/js/main.js"></script>
    <script src="public/js/footer.js"></script>
    <script src="public/js/validationForUpload.js"></script>
</body>

</html>