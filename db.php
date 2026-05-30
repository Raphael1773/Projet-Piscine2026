<?php

$host = "localhost";
$user = "root";
$password = "";
$database = "mercato_nova";

$conn = new mysqli(
    $host,
    $user,
    $password,
    $database
);

if ($conn->connect_error) {

    die(
        "Erreur connexion : "
        . $conn->connect_error
    );
}

?>