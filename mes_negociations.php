<?php

session_start();
require_once __DIR__ . "/back_end/db.php";

if (!isset($_SESSION["id_utilisateur"])) {
    header("Location: front_end/login.html");
    exit();
}

$id_utilisateur = $_SESSION["id_utilisateur"];

$sql = "
    SELECT
        negociation.*,
        produit.titre,
        produit.image,
        produit.prix AS prix_initial,
        acheteur.nom AS nom_acheteur,
        acheteur.prenom AS prenom_acheteur
    FROM negociation
    INNER JOIN produit ON negociation.id_produit = produit.id_produit
    INNER JOIN utilisateur AS acheteur ON negociation.id_acheteur = acheteur.id_utilisateur
    WHERE negociation.id_vendeur = ?
    ORDER BY negociation.date_creation DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_utilisateur);
$stmt->execute();
$result = $stmt->get_result();

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Mes négociations - Mercato Nova</title>
    <link rel="stylesheet" href="front_end/style.css?v=41">
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
        <a href="home.php?type=particulier">Particuliers</a>
        <a href="panier.php">Panier</a>
        <a href="profile.php">Profile</a>
    </nav>
</header>

<main class="conteneur-panier">

    <section class="titre-panier">
        <h2>Mes négociations reçues</h2>
        <p>Acceptez ou refusez les propositions des acheteurs.</p>
    </section>

    <section class="liste-produits-panier">

        <?php if ($result->num_rows === 0) { ?>

            <div class="panier-vide-style">
                <h3>Aucune négociation</h3>
                <p>Vous n’avez pas encore reçu de proposition.</p>
            </div>

        <?php } ?>

        <?php while ($nego = $result->fetch_assoc()) { ?>

            <article class="carte-panier">

                <div class="image-panier">
                    <?php if (!empty($nego["image"]) && file_exists("images/" . $nego["image"])) { ?>
                        <img src="images/<?= htmlspecialchars($nego["image"]) ?>" alt="Produit">
                    <?php } else { ?>
                        <span>Image produit</span>
                    <?php } ?>
                </div>

                <div class="infos-panier">
                    <h3><?= htmlspecialchars($nego["titre"]) ?></h3>
                    <p>
                        Acheteur :
                        <?= htmlspecialchars($nego["prenom_acheteur"] . " " . $nego["nom_acheteur"]) ?>
                    </p>
                    <p>
                        Prix initial :
                        <?= number_format($nego["prix_initial"], 2, ",", " ") ?> €
                    </p>
                    <p>
                        Proposition :
                        <strong><?= number_format($nego["prix_propose"], 2, ",", " ") ?> €</strong>
                    </p>
                    <span class="type-produit">
                        <?= htmlspecialchars($nego["statut"]) ?>
                    </span>
                </div>

                <?php if ($nego["statut"] === "en_attente") { ?>

                    <form action="back_end/repondre_negociation.php" method="POST">
                        <input type="hidden" name="id_negociation" value="<?= intval($nego["id_negociation"]) ?>">
                        <input type="hidden" name="action" value="accepter">
                        <button type="submit" class="btn-action-produit">
                            Accepter
                        </button>
                    </form>

                    <form action="back_end/repondre_negociation.php" method="POST">
                        <input type="hidden" name="id_negociation" value="<?= intval($nego["id_negociation"]) ?>">
                        <input type="hidden" name="action" value="refuser">
                        <button type="submit" class="btn-retirer-panier">
                            Refuser
                        </button>
                    </form>

                <?php } ?>

            </article>

        <?php } ?>

    </section>

</main>

</body>
</html>