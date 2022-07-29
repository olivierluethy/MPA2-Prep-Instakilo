<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="public/css/login.css">
    <link rel="shortcut icon" href="assets/favicon.ico">
    <title>Login</title>
</head>

<body>
    <main>
        <div class="representation">
            <div class="text1">
                <h1>Discover a new era of being connected</h1>
            </div>
            <div class="text1 text2">
                <h1>With Instakilo</h1>
            </div>
        </div>
        <div class="loginInput">
            <h2>Instakilo</h2>
            <h1>Welcome To Instakilo</h1>
            <div class="switch">
                <button id="registerButton" onclick="navSwitch(1)">Register</button>
                <button id="loginButton" onclick="navSwitch(2)">Login</button>
            </div>

            <form id="login" action="/action_page.php">
                <label for="emailuser">Email or Username:</label><br>
                <input type="text" id="emailuser" name="emailuser" placeholder="Enter Email or Username"><br>
                <label for="password">Password:</label><br>
                <input type="password" id="password" name="password" placeholder="Enter Password"><br><br>
                <input type="submit" value="Login">
            </form>

            <form id="register" action="/action_page.php">
                <label for="email">Email:</label><br>
                <input type="email" id="email" name="email" placeholder="Enter Email"><br>
                <label for="username">Username:</label><br>
                <input type="text" id="username" name="username" placeholder="Enter Username"><br>
                <label for="password">Password:</label><br>
                <input type="password" id="password" name="password" placeholder="Enter Email"><br>
                <label for="verypass">Verify Password:</label><br>
                <input type="password" id="verypass" name="verypass" placeholder="Enter Password again"><br><br>
                <input type="submit" value="Login">
            </form> 
        </div>
    </main>

    <script src="public/js/login.js"></script>
</body>

</html>