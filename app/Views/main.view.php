<?php
$dataCounter = 0;

foreach ($daten as $data){
    $dataCounter++;
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
    echo "<h1>You're not logged in</h1>";
}
?>
        <?php
    if($dataCounter > 0){
        echo "
        <table>

        <tr>
            <th>Name</th>
            <th>Vorname</th>
            <th>Email</th>
            <th>Wurde erfasst am:</th>
            <th>Bearbeiten</th>
            <th>Löschen</th>
        </tr>";
        foreach ($daten as $data){
            echo "<tr>
            <td>" . $data['name'] ."</td>
            <td>" . $data['vorname'] . "</td>
            <td>" . $data['email'] . "</td>
            <td>" . $data['created_at'] . "</td>
            <td><a href='update?id=" . $data['id'] . "'>Daten bearbeiten</a></td>
            <td><a href='delete?id=" . $data['id'] . "'>Daten Löschen</a></td>
            </tr>";
        }
        echo "</table>";
    }else {
        echo "<h1 class='noFollowsText'>Folge Personen um deren Inhalte zu sehen</h1>";
    }?>

    </main>

    <?php
    include("footer.view.php");
    ?>
    <script src="public/js/main.js"></script>
    <script src="public/js/footer.js"></script>
</body>

</html>