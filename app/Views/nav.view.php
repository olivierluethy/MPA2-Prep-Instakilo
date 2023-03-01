<nav>
    <div class="grid-container">
        <div onclick='backToMain()'>
            <img src="assets/logo.png" alt="">
            <h1>Instakilo</h1>
        </div>
        <div>
            <input type="text" placeholder="Suche nach Personen">
        </div>
        <div>
        <?php
            if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) {
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
            } else {
                echo "<button onclick='goToLogin()' class='loginBtn'>Login  <i class='fa fa-sign-in'></i></button>";
            }
        ?>
        </div>
    </div>
</nav>