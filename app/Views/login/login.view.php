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
                <button>Register</button>
                <button>Login</button>
            </div>

            <form action="/action_page.php">
                <label for="fname">Email or Username:</label><br>
                <input type="text" id="fname" name="fname" placeholder="Enter Email or Username"><br>
                <label for="lname">Password:</label><br>
                <input type="text" id="lname" name="lname" placeholder="Enter Password"><br><br>
                <input type="submit" value="Login">
            </form> 
        </div>
    </main>
</body>

</html>