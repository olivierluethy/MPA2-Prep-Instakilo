<nav>
    <div class="grid-container">
        <div>
            <img src="assets/logo.png" alt="">
            <h1>Instakilo</h1>
        </div>
        <div>
            <input type="text" placeholder="Suche nach Personen">
        </div>
        <div>
            <?php
                // Check if the user is logged in, if not then redirect him to login page
                if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
                    echo "<button onclick='goToLogin()' class='loginBtn'>Login  <i class='fa fa-sign-in'></i></button>";
                }else{
                    echo "<img onclick='showModal()' src='assets/upload.svg' alt=''>
                    <div class='dropdown'>
                        <img src='assets/profile.png' alt=''>
                        <div class='dropdown-content'>
                            <a href='profile'>Profile</a>
                            <a href='#'>Settings</a>
                            <a href='#'>Darkmode</a>
                            <hr>
                            <a href='logout'>Logout</a>
                        </div>
                    </div>";
                }
            ?>
        </div>
    </div>
</nav>