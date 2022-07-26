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
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
    <link rel="shortcut icon" href="assets/favicon.ico">
    <title>Daten Bearbeitung</title>
</head>

<body>
    <?php
    include("nav.view.php");
    ?>
    <main>

        <h1>Daten</h1>
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
        echo "<h1 style='color: red';>Es wurden noch keine Daten erfasst</h1>";
    }?>

        <a href="create"><button>Daten hinzufügen</button></a>

    </main>

    <?php
    include("footer.view.php");
    ?>
    <script src="public/js/footer.js"></script>
</body>

</html>