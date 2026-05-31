<?php

session_start();
require_once __DIR__ . "/back_end/db.php";

if (!isset($_SESSION["id_utilisateur"])) {
    header("Location: front_end/login.html");
    exit();
}

$id_utilisateur = $_SESSION["id_utilisateur"];

if (!isset($_GET["id_produit"])) {
    header("Location: home.php?type=particulier");
    exit();
}

$id_produit = intval($_GET["id_produit"]);

$sql = "
    SELECT produit.*, utilisateur.nom, utilisateur.prenom
    FROM produit
    INNER JOIN utilisateur ON produit.id_vendeur = utilisateur.id_utilisateur
    WHERE produit.id_produit = ?
    AND produit.type_vente = 'particulier'
    AND produit.statut = 'actif'
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_produit);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: home.php?type=particulier");
    exit();
}

$produit = $result->fetch_assoc();

$message = "";

if (isset($_GET["success"])) {
    $message = "Votre proposition a bien été envoyée au vendeur.";
}

if (isset($_GET["erreur"])) {
    $message = "Erreur : impossible d'envoyer cette proposition.";
}

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Négociation - Mercato Nova</title>
    <link rel="stylesheet" href="front_end/style.css?v=40">
</head>

<body class="page-panier">

<header class="header-panier">
    <div class="bloc-logo">
<div class="logo-rond">
    <img src="images/logo.png" alt="Logo Mercato Nova">
</div>
        <h1>Mercato Nova</h1>
    </div>

    <nav class="nav-panier">
        <a href="home.php">Produits</a>
        <a href="home.php?type=enchere">Enchères</a>
        <a href="home.php?type=particulier">Particuliers</a>
        <a href="panier.php">Panier</a>
        <a href="profile.php">Profile</a>
    </nav>
</header>

<main class="conteneur-negociation">

    <section class="carte-detail-negociation">

        <div class="image-detail-negociation">
            <?php if (!empty($produit["image"]) && file_exists("images/" . $produit["image"])) { ?>
                <img src="images/<?= htmlspecialchars($produit["image"]) ?>" alt="Produit">
            <?php } else { ?>
                <span>Image produit</span>
            <?php } ?>
        </div>

        <div class="infos-detail-negociation">
            <h2><?= htmlspecialchars($produit["titre"]) ?></h2>

            <p class="description-negociation">
                <?= htmlspecialchars($produit["description"]) ?>
            </p>

            <p>
                Vendeur :
                <strong>
                    <?= htmlspecialchars($produit["prenom"] . " " . $produit["nom"]) ?>
                </strong>
            </p>

            <p>
                Prix demandé :
                <strong><?= number_format($produit["prix"], 2, ",", " ") ?> €</strong>
            </p>

            <?php if ($message !== "") { ?>
                <div class="message-negociation">
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php } ?>

            <?php if ($produit["id_vendeur"] == $id_utilisateur) { ?>

                <p class="message-negociation">
                    Vous êtes le vendeur de ce produit. Vous ne pouvez pas négocier avec vous-même.
                </p>

            <?php } else { ?>

                <form action="back_end/creer_negociation.php" method="POST" class="form-negociation">
                    <input type="hidden" name="id_produit" value="<?= intval($produit["id_produit"]) ?>">

                    <label for="prix_propose">Votre proposition</label>
                    <input
                        type="number"
                        step="0.01"
                        min="1"
                        name="prix_propose"
                        id="prix_propose"
                        placeholder="Ex : 35.00"
                        required
                    >

                    <label for="message">Message au vendeur</label>
                    <textarea
                        name="message"
                        id="message"
                        placeholder="Bonjour, je vous propose ce prix..."
                    ></textarea>

                    <button type="submit" class="bouton-payer">
                        Envoyer la proposition
                    </button>
                </form>

            <?php } ?>

        </div>

    </section>

</main>

</body>
</html>