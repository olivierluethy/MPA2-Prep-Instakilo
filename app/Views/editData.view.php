<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="public/css/style.css">
    <link rel="shortcut icon" href="assets/favicon.ico">
    <title>Daten Bearbeitung</title>
</head>

<body>

    <?php
    include("nav.view.php");
    ?>

    <form action="update?id=<?= $daten[0][0] ?>" method="POST">
        <fieldset>
            <legend>Personal Daten</legend>
            <label for="Name">Name:</label>
            <input type="text" name="name" id="name" value="<?= $daten[0][1] ?>" require><br><br>

            <label for="Vorname">Vorname:</label>
            <input type="text" name="vorname" id="vorname" value="<?= $daten[0][2] ?>" require><br><br>

            <label for="email">Email:</label>
            <input type="text" name="email" id="email" value="<?= $daten[0][3] ?>" require><br><br>
        </fieldset>
        <button type="submit" name="form-submit">Daten bearbeiten</button>
    </form>
    <script src="public/js/clientSideValidation.js"></script>
</body>

</html>