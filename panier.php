<?php
session_start();
require_once __DIR__ . "/back_end/db.php";

if (!isset($_SESSION['id_utilisateur'])) {
    header("Location: front_end/login.html");
    exit();
}

$id_utilisateur = $_SESSION['id_utilisateur'];

$sql_panier = "SELECT id_panier 
               FROM panier 
               WHERE id_utilisateur = ? AND statut = 'actif'
               LIMIT 1";

$stmt_panier = $conn->prepare($sql_panier);
$stmt_panier->bind_param("i", $id_utilisateur);
$stmt_panier->execute();
$result_panier = $stmt_panier->get_result();

$lignes = [];
$id_panier = null;

if ($result_panier->num_rows > 0) {
    $panier = $result_panier->fetch_assoc();
    $id_panier = $panier['id_panier'];

    $sql_lignes = "
    SELECT 
        lignepanier.id_ligne,
        lignepanier.quantite,
        lignepanier.prix_unitaire,
        produit.id_produit,
        produit.titre,
        produit.description,
        produit.type_vente,
        produit.image
    FROM lignepanier
    INNER JOIN produit ON lignepanier.id_produit = produit.id_produit
    WHERE lignepanier.id_panier = ?
";

    $stmt_lignes = $conn->prepare($sql_lignes);
    $stmt_lignes->bind_param("i", $id_panier);
    $stmt_lignes->execute();
    $result_lignes = $stmt_lignes->get_result();

    while ($row = $result_lignes->fetch_assoc()) {
        $lignes[] = $row;
    }
}

$total = 0;
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Mon panier - Mercato Nova</title>
    <link rel="stylesheet" href="front_end/style.css">
</head>

<body class="page-panier">

<header class="header-panier">
    <div class="bloc-logo">
        <div class="logo-rond">LOGO</div>
        <a href="home.php" style="text-decoration:none; color:white;">
        <h1>Mercato Nova</h1>
        </a>
    </div>

    <nav class="nav-panier">
        <a href="home.php">Produits</a>
        <a href="encheres.php">Enchères</a>
        <a href="particuliers.php">Particuliers</a>
        <a href="panier.php">Panier</a>
        <a href="profile.php">Profile</a>
    </nav>
</header>

<main class="conteneur-panier">

    <section class="titre-panier">
        <h2>Mon panier</h2>
        <p>Retrouvez ici les produits que vous souhaitez acheter.</p>
    </section>

    <?php if (empty($lignes)) : ?>

        <section class="panier-vide-style">
            <h3>Votre panier est vide</h3>
            <p>Ajoutez des produits depuis la page d’accueil pour les retrouver ici.</p>
            <a href="home.php" class="bouton-panier">Voir les produits</a>
        </section>

    <?php else : ?>

        <section class="zone-panier">

            <div class="liste-produits-panier">

                <?php foreach ($lignes as $ligne) : ?>
                    <?php
                    $sous_total = $ligne['prix_unitaire'] * $ligne['quantite'];
                    $total += $sous_total;
                    ?>

                    <article class="carte-panier">

                        <div class="image-panier">
    <?php if (!empty($ligne["image"]) && file_exists("images/" . $ligne["image"])) { ?>
        <img src="images/<?= htmlspecialchars($ligne["image"]) ?>" alt="Produit">
    <?php } else { ?>
        <span>Image produit</span>
    <?php } ?>
</div>

                        <div class="infos-panier">
                            <h3><?= htmlspecialchars($ligne['titre']) ?></h3>
                            <p><?= htmlspecialchars($ligne['description']) ?></p>
                            <span class="type-produit">
                                <?= htmlspecialchars($ligne['type_vente']) ?>
                            </span>
                        </div>

                        <div class="prix-panier">
                            <p>Prix unitaire</p>
                            <strong><?= number_format($ligne['prix_unitaire'], 2, ',', ' ') ?> €</strong>
                        </div>

                        <div class="quantite-panier">
                            <p>Quantité</p>
                            <strong><?= intval($ligne['quantite']) ?></strong>
                        </div>

                        <div class="sous-total-panier">
                            <p>Sous-total</p>
                            <strong><?= number_format($sous_total, 2, ',', ' ') ?> €</strong>
                        </div>

<form action="back_end/retirer_panier.php" method="POST">
    <input type="hidden" name="id_ligne" value="<?= intval($ligne['id_ligne']) ?>">
    <button type="submit" class="btn-retirer-panier">Retirer</button>
</form>
                    </article>

                <?php endforeach; ?>

            </div>

            <aside class="resume-panier">
                <h3>Résumé</h3>

                <div class="ligne-resume">
                    <span>Nombre d’articles</span>
                    <strong><?= count($lignes) ?></strong>
                </div>

                <div class="ligne-resume">
                    <span>Total</span>
                    <strong><?= number_format($total, 2, ',', ' ') ?> €</strong>
                </div>

                <form action="valider_panier.php" method="POST">
                    <input type="hidden" name="id_panier" value="<?= intval($id_panier) ?>">
                    <button type="submit" class="bouton-payer">Payer</button>
                </form>

                <a href="home.php" class="continuer-achats">Continuer mes achats</a>
            </aside>

        </section>

    <?php endif; ?>

</main>

</body>
</html>
