<?php

session_start();

require_once __DIR__ . "/db.php";

header("Content-Type: application/json");

$data = json_decode(file_get_contents("php://input"));

if ($data === null || !isset($data->email) || !isset($data->mot_de_passe)) {
    echo json_encode([
        "success" => false,
        "message" => "Données de connexion manquantes"
    ]);
    exit();
}

$email = trim($data->email);
$mot_de_passe = $data->mot_de_passe;

$sql = "SELECT * FROM utilisateur WHERE email = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $email);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows > 0) {

    $user = $result->fetch_assoc();

    if (password_verify($mot_de_passe, $user["mot_de_passe"])) {

        $_SESSION["id_utilisateur"] = $user["id_utilisateur"];
        $_SESSION["nom"] = $user["nom"];
        $_SESSION["prenom"] = $user["prenom"];
        $_SESSION["email"] = $user["email"];
        $_SESSION["role"] = $user["role"];

        echo json_encode([
            "success" => true,
            "message" => "Connexion réussie"
        ]);
        exit();

    } else {

        echo json_encode([
            "success" => false,
            "message" => "Mot de passe incorrect"
        ]);
        exit();
    }

} else {

    echo json_encode([
        "success" => false,
        "message" => "Utilisateur introuvable"
    ]);
    exit();
}

?>
