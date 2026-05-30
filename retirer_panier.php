<?php

session_start();

require_once __DIR__ . "/db.php";

if (!isset($_SESSION["id_utilisateur"])) {
    header("Location: ../front_end/login.html");
    exit();
}

if (!isset($_POST["id_ligne"])) {
    header("Location: ../panier.php");
    exit();
}

$id_utilisateur = $_SESSION["id_utilisateur"];
$id_ligne = intval($_POST["id_ligne"]);

/*
    On vérifie que la ligne appartient bien au panier actif de l'utilisateur.
*/
$sql = "
    DELETE lignepanier
    FROM lignepanier
    INNER JOIN panier ON lignepanier.id_panier = panier.id_panier
    WHERE lignepanier.id_ligne = ?
    AND panier.id_utilisateur = ?
    AND panier.statut = 'actif'
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $id_ligne, $id_utilisateur);
$stmt->execute();

header("Location: ../panier.php");
exit();

?>