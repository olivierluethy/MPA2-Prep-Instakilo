<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="public/css/style.css">
    <link rel="shortcut icon" href="assets/favicon.ico">
    <title>Daten Erfassung</title>
</head>

<body>

    <?php
    include("nav.view.php");
    ?>

    <form action="create" method="POST">
        <fieldset>
            <legend>Daten</legend>

            <label for="Name">Name:</label>
            <input type="text" name="name" id="name" require><br><br>

            <label for="Vorname">Vorname:</label>
            <input type="text" name="vorname" id="vorname" require><br><br>

            <label for="email">Email:</label>
            <input type="email" name="email" id="email" require><br><br>
        </fieldset>
        <button type="submit" name="form-submit">Daten erfassen</button>
    </form>
    <script src="public/js/clientSideValidation.js"></script>
</body>

</html>