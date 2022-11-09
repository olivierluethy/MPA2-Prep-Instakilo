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
        // Überprüft ob der Benutzer eingeloggt ist oder nicht
        if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) {
            if($allPostsCounter > 0){
				// Alle gelikten Posts
                if($alreadyLikedPostsCounter > 0){
                    echo "<div class='grid-container'>";
                    foreach ($alreadyLikedPosts as $alreadyLikedPosts2){
                        echo 
						"<div>
							<!--Already liked posts-->
							<div class='profile'>
								<div class='imgAndUser'>
									<img src='assets/profile.png' alt=''>
									<p onclick='visitProfile(" . $alreadyLikedPosts2['fk_userId'] . ")' class='username' title='Das Profil von ". $alreadyLikedPosts2['username'] ." anschauen'>" . $alreadyLikedPosts2['username'] . "</p>
								</div>
							</div>
							<div class='post'>
								<p>". $alreadyLikedPosts2['titel'] . "</p>
								<img src='data:" . $alreadyLikedPosts2['imageType'] . ";base64, ".base64_encode($alreadyLikedPosts2['imageData']). "'/>
							</div>
							<div class='likeAndDes'>
								<img onclick='unlikePost(" . $alreadyLikedPosts2['imageId'] . ")' src='assets/heart-red.png' alt=''>
								<p>" . $alreadyLikedPosts2['likes'] . "</p>
								<p class='desc'>". $alreadyLikedPosts2['beschreibung'] . "</p>
							</div>
						</div>";
                    }
                }
				if($unlikedPostsCounter > 0){
					// Ungelikte Posts
					echo "<div class='grid-container'>";
					foreach ($unlikedPosts as $unlikedPosts2){
						echo 
						"<div>
							<!--Unliked posts-->
							<div class='profile'>
								<div class='imgAndUser'>
									<img src='assets/profile.png' alt=''>
									<p onclick='visitProfile(" . $unlikedPosts2['fk_userId'] . ")' class='username' title='Das Profil von ". $unlikedPosts2['username'] ." anschauen'>" . $unlikedPosts2['username'] . "</p>
								</div>
							</div>
							<div class='post'>
								<p>". $unlikedPosts2['titel'] . "</p>
								<img src='data:" . $unlikedPosts2['imageType'] . ";base64, ".base64_encode($unlikedPosts2['imageData']). "'/>
							</div>
							<div class='likeAndDes'>
								<img onclick='likePost(" . $unlikedPosts2['imageId'] . ")' src='assets/heart.png' alt=''>
								<p>&nbsp" . $unlikedPosts2['likes'] . "</p>
								<p class='desc'>". $unlikedPosts2['beschreibung'] . "</p>
							</div>
						</div>";
					}
				}
			}else {
				echo "<h1>Derzeit noch keine Beiträge</h1>";
			}
        }
        /* Falls man nicht eingeloggt ist */ 
        else if ($publicPostsCounter > 0) {
            echo "<div class='grid-container'>";
                foreach ($publicPosts as $publicPosts2){
                    echo 
					"<div>
						<!--Public posts-->
						<div class='profile'>
							<div class='imgAndUser'>
								<img src='assets/profile.png' alt=''>
								<p onclick='visitProfile(" . $publicPosts2['fk_userId'] . ")' class='username' title='Das Profil von ". $publicPosts2['username'] ." anschauen'>" . $publicPosts2['username'] . "</p>
							</div>
						</div>
						<div class='post'>
							<p>". $publicPosts2['titel'] . "</p>
							<img src='data:" . $publicPosts2['imageType'] . ";base64, ".base64_encode($publicPosts2['imageData']). "'/>
						</div>
						<div class='likeAndDes'>
							<img onclick='goToLogin()' src='assets/heart.png' alt=''>
							<p>&nbsp" . $publicPosts2['likes'] . "</p>
							<p class='desc'>". $publicPosts2['beschreibung'] . "</p>
						</div>
					</div>";
                }
        } else {
            echo "<h1>Derzeit noch keine Beiträge</h1>";
        }
        ?>
        </div>
    </main>

    <?php
    include("imageUpload.view.php");
    include("footer.view.php");
    ?>
    <script src="public/js/main.js"></script>
    <script src="public/js/footer.js"></script>
    <script src="public/js/validationForUpload.js"></script>
</body>

</html>