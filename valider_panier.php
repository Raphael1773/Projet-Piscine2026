<?php

session_start();

require_once __DIR__ . "/back_end/db.php";

if (!isset($_SESSION["id_utilisateur"])) {
    header("Location: front_end/login.html");
    exit();
}

if (!isset($_POST["id_panier"])) {
    header("Location: panier.php");
    exit();
}

$id_utilisateur = $_SESSION["id_utilisateur"];
$id_panier = intval($_POST["id_panier"]);

$sql_lignes = "
    SELECT 
        lignepanier.id_ligne,
        lignepanier.quantite,
        lignepanier.prix_unitaire,
        lignepanier.id_produit,
        produit.type_vente,
        produit.statut
    FROM lignepanier
    INNER JOIN panier ON lignepanier.id_panier = panier.id_panier
    INNER JOIN produit ON lignepanier.id_produit = produit.id_produit
    WHERE lignepanier.id_panier = ?
    AND panier.id_utilisateur = ?
    AND panier.statut = 'actif'
";

$stmt_lignes = $conn->prepare($sql_lignes);
$stmt_lignes->bind_param("ii", $id_panier, $id_utilisateur);
$stmt_lignes->execute();
$result_lignes = $stmt_lignes->get_result();

if ($result_lignes->num_rows === 0) {
    header("Location: panier.php");
    exit();
}

while ($ligne = $result_lignes->fetch_assoc()) {

    $montant_total = $ligne["prix_unitaire"] * $ligne["quantite"];

    $sql_transaction = "INSERT INTO transactionn
        (montant_total, date_transaction, statut, mode_paiement, id_acheteur, id_produit)
        VALUES
        (?, NOW(), 'validee', 'simulation', ?, ?)";

    $stmt_transaction = $conn->prepare($sql_transaction);
    $stmt_transaction->bind_param(
        "dii",
        $montant_total,
        $id_utilisateur,
        $ligne["id_produit"]
    );
    $stmt_transaction->execute();
}

/*
    Le panier devient validé.
*/
$sql_update_panier = "UPDATE panier
                      SET statut = 'valide'
                      WHERE id_panier = ?
                      AND id_utilisateur = ?";

$stmt_update = $conn->prepare($sql_update_panier);
$stmt_update->bind_param("ii", $id_panier, $id_utilisateur);
$stmt_update->execute();

header("Location: panier.php?commande=ok");
exit();

?>