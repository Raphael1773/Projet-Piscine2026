<?php

include "db.php";

$data = json_decode(file_get_contents("php://input"));

$nom = $data->nom;
$prenom = $data->prenom;
$email = $data->email;

$mot_de_passe = password_hash(
    $data->mot_de_passe,
    PASSWORD_DEFAULT
);

$sql = "INSERT INTO utilisateur
(nom, prenom, email, mot_de_passe)
VALUES (?, ?, ?, ?)";

$stmt = $conn->prepare($sql);

$stmt->bind_param(
    "ssss",
    $nom,
    $prenom,
    $email,
    $mot_de_passe
);

if ($stmt->execute()) {

    echo json_encode([
        "success" => true
    ]);

} else {

    echo json_encode([
        "success" => false
    ]);
}

?>