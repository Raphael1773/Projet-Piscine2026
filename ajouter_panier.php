<?php

session_start();

require_once __DIR__ . "/db.php";

if (!isset($_SESSION["id_utilisateur"])) {
    header("Location: ../front_end/login.html");
    exit();
}

if (!isset($_POST["id_produit"])) {
    header("Location: ../home.php");
    exit();
}

$id_utilisateur = $_SESSION["id_utilisateur"];
$id_produit = intval($_POST["id_produit"]);

/*
    On vérifie que le produit existe,
    qu'il est actif,
    et que c'est bien une vente simple.
*/
$sql_produit = "SELECT * FROM produit 
                WHERE id_produit = ? 
                AND statut = 'actif' 
                AND type_vente = 'simple'";

$stmt_produit = $conn->prepare($sql_produit);
$stmt_produit->bind_param("i", $id_produit);
$stmt_produit->execute();
$result_produit = $stmt_produit->get_result();

if ($result_produit->num_rows === 0) {
    header("Location: ../home.php");
    exit();
}

$produit = $result_produit->fetch_assoc();
$prix = $produit["prix"];

/*
    On cherche le panier actif.
*/
$sql_panier = "SELECT id_panier FROM panier
               WHERE id_utilisateur = ? AND statut = 'actif'
               LIMIT 1";

$stmt_panier = $conn->prepare($sql_panier);
$stmt_panier->bind_param("i", $id_utilisateur);
$stmt_panier->execute();
$result_panier = $stmt_panier->get_result();

/*
    Si aucun panier actif n'existe, on le crée.
*/
if ($result_panier->num_rows === 0) {

    $sql_create_panier = "INSERT INTO panier (date_creation, id_utilisateur, statut)
                          VALUES (NOW(), ?, 'actif')";

    $stmt_create = $conn->prepare($sql_create_panier);
    $stmt_create->bind_param("i", $id_utilisateur);
    $stmt_create->execute();

    $id_panier = $conn->insert_id;

} else {

    $panier = $result_panier->fetch_assoc();
    $id_panier = $panier["id_panier"];
}

/*
    On regarde si le produit est déjà dans le panier.
*/
$sql_ligne = "SELECT id_ligne, quantite FROM lignepanier
              WHERE id_panier = ? AND id_produit = ?";

$stmt_ligne = $conn->prepare($sql_ligne);
$stmt_ligne->bind_param("ii", $id_panier, $id_produit);
$stmt_ligne->execute();
$result_ligne = $stmt_ligne->get_result();

if ($result_ligne->num_rows > 0) {

    $ligne = $result_ligne->fetch_assoc();
    $nouvelle_quantite = $ligne["quantite"] + 1;

    $sql_update = "UPDATE lignepanier
                   SET quantite = ?
                   WHERE id_ligne = ?";

    $stmt_update = $conn->prepare($sql_update);
    $stmt_update->bind_param("ii", $nouvelle_quantite, $ligne["id_ligne"]);
    $stmt_update->execute();

} else {

    $sql_insert = "INSERT INTO lignepanier
                   (quantite, prix_unitaire, id_panier, id_produit)
                   VALUES (1, ?, ?, ?)";

    $stmt_insert = $conn->prepare($sql_insert);
    $stmt_insert->bind_param("dii", $prix, $id_panier, $id_produit);
    $stmt_insert->execute();
}

header("Location: ../panier.php");
exit();

?>