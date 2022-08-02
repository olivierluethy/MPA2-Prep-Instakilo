<nav>
    <div class="part1">
        <img src="assets/logo.png" alt="">
        <h1>Instakilo</h1>
    </div>
    <div class="part2">
        <input type="text" placeholder="Suche nach Personen">
    </div>
    <div class="part3">
    <?php
        // Check if the user is logged in, if not then redirect him to login page
        if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
            echo "<button onclick='goToLogin()' class='loginBtn'>Login  <i class='fa fa-sign-in'></i></button>";
        }else{
            echo "<img src='assets/upload.svg' alt=''>
            <img src='assets/profile.png' alt=''>";
        }
    ?>
    </div>
</nav>