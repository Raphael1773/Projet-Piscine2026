<?php

session_start();
require_once __DIR__ . "/db.php";

if (!isset($_SESSION["id_utilisateur"])) {
    header("Location: ../front_end/login.html");
    exit();
}

if (!isset($_POST["id_produit"]) || !isset($_POST["prix_propose"])) {
    header("Location: ../home.php?type=particulier");
    exit();
}

$id_acheteur = $_SESSION["id_utilisateur"];
$id_produit = intval($_POST["id_produit"]);
$prix_propose = floatval($_POST["prix_propose"]);
$message = trim($_POST["message"] ?? "");

if ($prix_propose <= 0) {
    header("Location: ../particuliers.php?id_produit=" . $id_produit . "&erreur=1");
    exit();
}

$sql_produit = "
    SELECT *
    FROM produit
    WHERE id_produit = ?
    AND type_vente = 'particulier'
    AND statut = 'actif'
";

$stmt_produit = $conn->prepare($sql_produit);
$stmt_produit->bind_param("i", $id_produit);
$stmt_produit->execute();
$result_produit = $stmt_produit->get_result();

if ($result_produit->num_rows === 0) {
    header("Location: ../home.php?type=particulier");
    exit();
}

$produit = $result_produit->fetch_assoc();
$id_vendeur = intval($produit["id_vendeur"]);

if ($id_vendeur === $id_acheteur) {
    header("Location: ../particuliers.php?id_produit=" . $id_produit . "&erreur=1");
    exit();
}

$sql_insert = "
    INSERT INTO negociation
    (statut, date_creation, prix_propose, id_produit, id_acheteur, id_vendeur)
    VALUES
    ('en_attente', NOW(), ?, ?, ?, ?)
";

$stmt_insert = $conn->prepare($sql_insert);
$stmt_insert->bind_param("diii", $prix_propose, $id_produit, $id_acheteur, $id_vendeur);
$stmt_insert->execute();

$id_negociation = $conn->insert_id;

if ($message !== "") {
    $sql_message = "
        INSERT INTO messagenegociation
        (contenu, date_envoi, id_negociation, id_expediteur)
        VALUES
        (?, NOW(), ?, ?)
    ";

    $stmt_message = $conn->prepare($sql_message);
    $stmt_message->bind_param("sii", $message, $id_negociation, $id_acheteur);
    $stmt_message->execute();
}

$sql_notif = "
    INSERT INTO notification
    (titre, message, date_notification, lu, id_utilisateur)
    VALUES
    ('Nouvelle négociation', ?, NOW(), 0, ?)
";

$texte_notif = "Vous avez reçu une proposition pour le produit : " . $produit["titre"];
$stmt_notif = $conn->prepare($sql_notif);
$stmt_notif->bind_param("si", $texte_notif, $id_vendeur);
$stmt_notif->execute();

header("Location: ../particuliers.php?id_produit=" . $id_produit . "&success=1");
exit();

?>