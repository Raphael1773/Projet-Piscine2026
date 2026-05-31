<?php

session_start();
require_once __DIR__ . "/db.php";

if (!isset($_SESSION["id_utilisateur"])) {
    header("Location: ../front_end/login.html");
    exit();
}

if (!isset($_POST["id_negociation"]) || !isset($_POST["action"])) {
    header("Location: ../mes_negociations.php");
    exit();
}

$id_vendeur_connecte = $_SESSION["id_utilisateur"];
$id_negociation = intval($_POST["id_negociation"]);
$action = $_POST["action"];

$sql = "
    SELECT
        negociation.*,
        produit.titre,
        produit.statut AS statut_produit
    FROM negociation
    INNER JOIN produit ON negociation.id_produit = produit.id_produit
    WHERE negociation.id_negociation = ?
    AND negociation.id_vendeur = ?
    AND negociation.statut = 'en_attente'
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $id_negociation, $id_vendeur_connecte);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: ../mes_negociations.php");
    exit();
}

$nego = $result->fetch_assoc();

$id_acheteur = intval($nego["id_acheteur"]);
$id_produit = intval($nego["id_produit"]);
$prix_negocie = floatval($nego["prix_propose"]);

if ($action === "refuser") {

    $sql_refus = "UPDATE negociation SET statut = 'refusee' WHERE id_negociation = ?";
    $stmt_refus = $conn->prepare($sql_refus);
    $stmt_refus->bind_param("i", $id_negociation);
    $stmt_refus->execute();

    $notif = "Votre proposition pour " . $nego["titre"] . " a été refusée.";

    $sql_notif = "
        INSERT INTO notification
        (titre, message, date_notification, lu, id_utilisateur)
        VALUES
        ('Négociation refusée', ?, NOW(), 0, ?)
    ";

    $stmt_notif = $conn->prepare($sql_notif);
    $stmt_notif->bind_param("si", $notif, $id_acheteur);
    $stmt_notif->execute();

    header("Location: ../mes_negociations.php");
    exit();
}

if ($action === "accepter") {

    $sql_accept = "UPDATE negociation SET statut = 'acceptee' WHERE id_negociation = ?";
    $stmt_accept = $conn->prepare($sql_accept);
    $stmt_accept->bind_param("i", $id_negociation);
    $stmt_accept->execute();

    /*
        On cherche ou crée le panier actif de l'acheteur.
    */
    $sql_panier = "
        SELECT id_panier
        FROM panier
        WHERE id_utilisateur = ?
        AND statut = 'actif'
        LIMIT 1
    ";

    $stmt_panier = $conn->prepare($sql_panier);
    $stmt_panier->bind_param("i", $id_acheteur);
    $stmt_panier->execute();
    $result_panier = $stmt_panier->get_result();

    if ($result_panier->num_rows > 0) {
        $panier = $result_panier->fetch_assoc();
        $id_panier = intval($panier["id_panier"]);
    } else {
        $sql_create_panier = "
            INSERT INTO panier
            (date_creation, id_utilisateur, statut)
            VALUES
            (NOW(), ?, 'actif')
        ";

        $stmt_create = $conn->prepare($sql_create_panier);
        $stmt_create->bind_param("i", $id_acheteur);
        $stmt_create->execute();

        $id_panier = $conn->insert_id;
    }

    /*
        Produit particulier = exemplaire unique.
        On l'ajoute au panier avec le prix accepté.
    */
    $sql_ligne = "
        INSERT INTO lignepanier
        (quantite, prix_unitaire, id_panier, id_produit)
        VALUES
        (1, ?, ?, ?)
    ";

    $stmt_ligne = $conn->prepare($sql_ligne);
    $stmt_ligne->bind_param("dii", $prix_negocie, $id_panier, $id_produit);
    $stmt_ligne->execute();

    /*
        On désactive le produit pour éviter que d'autres utilisateurs négocient dessus.
    */
    $sql_produit = "UPDATE produit SET statut = 'reserve' WHERE id_produit = ?";
    $stmt_produit = $conn->prepare($sql_produit);
    $stmt_produit->bind_param("i", $id_produit);
    $stmt_produit->execute();

    /*
        On refuse automatiquement les autres négociations en attente pour ce produit.
    */
    $sql_autres = "
        UPDATE negociation
        SET statut = 'refusee'
        WHERE id_produit = ?
        AND id_negociation != ?
        AND statut = 'en_attente'
    ";

    $stmt_autres = $conn->prepare($sql_autres);
    $stmt_autres->bind_param("ii", $id_produit, $id_negociation);
    $stmt_autres->execute();

    $notif = "Votre proposition pour " . $nego["titre"] . " a été acceptée. Le produit a été ajouté à votre panier.";

    $sql_notif = "
        INSERT INTO notification
        (titre, message, date_notification, lu, id_utilisateur)
        VALUES
        ('Négociation acceptée', ?, NOW(), 0, ?)
    ";

    $stmt_notif = $conn->prepare($sql_notif);
    $stmt_notif->bind_param("si", $notif, $id_acheteur);
    $stmt_notif->execute();

    header("Location: ../mes_negociations.php");
    exit();
}

header("Location: ../mes_negociations.php");
exit();

?>