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
        produit.titre,
        produit.statut
    FROM lignepanier
    INNER JOIN panier 
        ON lignepanier.id_panier = panier.id_panier
    INNER JOIN produit 
        ON lignepanier.id_produit = produit.id_produit
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

$conn->begin_transaction();

try {

    while ($ligne = $result_lignes->fetch_assoc()) {

        $id_produit = intval($ligne["id_produit"]);
        $quantite = intval($ligne["quantite"]);
        $prix_unitaire = floatval($ligne["prix_unitaire"]);
        $montant_total = $prix_unitaire * $quantite;
        $type_vente = $ligne["type_vente"];

        /*
            On crée une transaction simulée.
        */
        $sql_transaction = "
            INSERT INTO transactionn
            (montant_total, date_transaction, statut, mode_paiement, id_acheteur, id_produit)
            VALUES
            (?, NOW(), 'validee', 'simulation', ?, ?)
        ";

        $stmt_transaction = $conn->prepare($sql_transaction);
        $stmt_transaction->bind_param(
            "dii",
            $montant_total,
            $id_utilisateur,
            $id_produit
        );
        $stmt_transaction->execute();

        /*
            Produit simple :
            on ne change rien.
            Il reste disponible à l'infini.
        */
        if ($type_vente === "simple") {
            continue;
        }

        /*
            Produit particulier ou enchère :
            une fois payé, il devient vendu.
            Il disparaît donc du home.
        */
        if ($type_vente === "particulier" || $type_vente === "enchere") {

            $sql_update_produit = "
                UPDATE produit
                SET statut = 'vendu'
                WHERE id_produit = ?
            ";

            $stmt_update_produit = $conn->prepare($sql_update_produit);
            $stmt_update_produit->bind_param("i", $id_produit);
            $stmt_update_produit->execute();
        }
    }

    /*
        Le panier est validé.
        Comme panier.php affiche seulement le panier actif,
        les produits disparaissent du panier.
    */
    $sql_update_panier = "
        UPDATE panier
        SET statut = 'valide'
        WHERE id_panier = ?
        AND id_utilisateur = ?
        AND statut = 'actif'
    ";

    $stmt_update_panier = $conn->prepare($sql_update_panier);
    $stmt_update_panier->bind_param("ii", $id_panier, $id_utilisateur);
    $stmt_update_panier->execute();

    $conn->commit();

    header("Location: panier.php?paiement=ok");
    exit();

} catch (Exception $e) {

    $conn->rollback();

    header("Location: panier.php?paiement=erreur");
    exit();
}

?>
