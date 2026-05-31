<?php

session_start();

if (!isset($_SESSION["id_utilisateur"])) {
    header("Location: front_end/login.html");
    exit();
}

require_once __DIR__ . "/back_end/db.php";
require_once __DIR__ . "/back_end/finaliser_encheres.php";

/*
    Vérifie automatiquement si des enchères sont terminées.
    Si une enchère est finie :
    - le meilleur enchérisseur reçoit le produit dans son panier ;
    - le produit passe en "reserve" ;
    - il disparaît donc du home.
*/
finaliserEncheres($conn);

$type_filtre = $_GET["type"] ?? "tous";
$recherche = trim($_GET["recherche"] ?? "");

$types_valides = ["simple", "enchere", "particulier"];

$select_produits = "
    SELECT
        produit.*,
        utilisateur.nom,
        utilisateur.prenom,
        enchere.id_enchere,
        enchere.date_fin,
        (
            SELECT MAX(offre.montant)
            FROM offre
            WHERE offre.id_enchere = enchere.id_enchere
        ) AS meilleure_offre
    FROM produit
    INNER JOIN utilisateur
        ON produit.id_vendeur = utilisateur.id_utilisateur
    LEFT JOIN enchere
        ON produit.id_produit = enchere.id_produit
";

/*
    Cas 1 : filtre + recherche
*/
if (in_array($type_filtre, $types_valides) && $recherche !== "") {

    $sql = $select_produits . "
        WHERE produit.statut = 'actif'
        AND produit.type_vente = ?
        AND produit.titre LIKE ?
        AND (
            produit.type_vente <> 'enchere'
            OR enchere.date_fin > NOW()
        )
        ORDER BY produit.id_produit DESC
    ";

    $recherche_sql = "%" . $recherche . "%";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $type_filtre, $recherche_sql);
    $stmt->execute();

    $result = $stmt->get_result();

/*
    Cas 2 : filtre sans recherche
*/
} elseif (in_array($type_filtre, $types_valides)) {

    $sql = $select_produits . "
        WHERE produit.statut = 'actif'
        AND produit.type_vente = ?
        AND (
            produit.type_vente <> 'enchere'
            OR enchere.date_fin > NOW()
        )
        ORDER BY produit.id_produit DESC
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $type_filtre);
    $stmt->execute();

    $result = $stmt->get_result();

/*
    Cas 3 : recherche sans filtre
*/
} elseif ($recherche !== "") {

    $sql = $select_produits . "
        WHERE produit.statut = 'actif'
        AND produit.titre LIKE ?
        AND (
            produit.type_vente <> 'enchere'
            OR enchere.date_fin > NOW()
        )
        ORDER BY produit.id_produit DESC
    ";

    $recherche_sql = "%" . $recherche . "%";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $recherche_sql);
    $stmt->execute();

    $result = $stmt->get_result();

/*
    Cas 4 : tous les produits actifs
*/
} else {

    $sql = $select_produits . "
        WHERE produit.statut = 'actif'
        AND (
            produit.type_vente <> 'enchere'
            OR enchere.date_fin > NOW()
        )
        ORDER BY produit.id_produit DESC
    ";

    $result = $conn->query($sql);
}

?>

<!DOCTYPE html>

<html lang="fr">

<head>

    <meta charset="UTF-8">

    <title>Mercato Nova</title>

    <link rel="stylesheet" href="front_end/style.css?v=50">

</head>

<body class="home-body">

    <?php

    if (isset($_GET["erreur"]) && $_GET["erreur"] == "mise") {
        echo '
        <div
            style="
                background:#ffdddd;
                color:red;
                padding:10px;
                margin:10px;
                text-align:center;
            ">
            Mise insuffisante.
        </div>
        ';
    }

    ?>

    <div class="navbar">

        <div class="left-section">

            <div class="logo">
                <img src="images/logo.png" alt="Logo Mercato Nova">
            </div>

            <a href="home.php" class="site-name">
                Mercato Nova
            </a>

        </div>

        <div class="search-section">

            <form action="home.php" method="GET" class="form-recherche">

                <input
                    type="text"
                    name="recherche"
                    placeholder="Rechercher un produit..."
                    class="search-bar"
                    value="<?php echo isset($_GET['recherche']) ? htmlspecialchars($_GET['recherche']) : ''; ?>"
                >

                <?php if (isset($_GET["type"])) { ?>

                    <input
                        type="hidden"
                        name="type"
                        value="<?php echo htmlspecialchars($_GET["type"]); ?>"
                    >

                <?php } ?>

                <button type="submit" class="btn-recherche">
                    Rechercher
                </button>

            </form>

        </div>

        <div class="right-section">

            <a href="panier.php" class="icon-link">
                Panier
            </a>

            <a href="mes_encheres.php" class="icon-link">
                Mes enchères
            </a>

            <a href="profile.php" class="icon-link">
                Profil
            </a>

        </div>

    </div>

    <div class="menu-buttons">

        <a href="home.php?type=simple">
            <button>
                Produits
            </button>
        </a>

        <a href="home.php?type=enchere">
            <button>
                Enchères
            </button>
        </a>

        <a href="home.php?type=particulier">
            <button>
                Particuliers
            </button>
        </a>

    </div>

    <div class="products-grid">

        <?php

        if ($result && $result->num_rows > 0) {

            while ($produit = $result->fetch_assoc()) {

                $prix_affiche = $produit["prix"];

                if (
                    $produit["type_vente"] == "enchere"
                    &&
                    !empty($produit["meilleure_offre"])
                ) {
                    $prix_affiche = $produit["meilleure_offre"];
                }

        ?>

                <div class="product-card">

                    <?php if (!empty($produit["image"]) && file_exists("images/" . $produit["image"])) { ?>

                        <img
                            src="images/<?php echo htmlspecialchars($produit["image"]); ?>"
                            class="product-image"
                            alt="<?php echo htmlspecialchars($produit["titre"]); ?>"
                        >

                    <?php } else { ?>

                        <div class="product-image">
                            Pas d'image
                        </div>

                    <?php } ?>

                    <h3>
                        <?php echo htmlspecialchars($produit["titre"]); ?>
                    </h3>

                    <button
                        class="btn-details"
                        onclick="toggleDetails(<?php echo intval($produit['id_produit']); ?>)"
                    >
                        +
                    </button>

                    <div
                        id="details-<?php echo intval($produit['id_produit']); ?>"
                        class="details-produit"
                    >

                        <p>
                            <?php echo htmlspecialchars($produit["description"]); ?>
                        </p>

                        <p>
                            État :
                            <?php echo htmlspecialchars($produit["etat"]); ?>
                        </p>

                        <p>
                            Date :
                            <?php echo htmlspecialchars($produit["date_publication"]); ?>
                        </p>

                        <?php if ($produit["type_vente"] == "enchere") { ?>

                            <p>
                                Fin de l'enchère :
                                <?php echo htmlspecialchars($produit["date_fin"]); ?>
                            </p>

                        <?php } ?>

                        <p>
                            Vendeur :
                            <?php echo htmlspecialchars($produit["prenom"]); ?>
                            <?php echo htmlspecialchars($produit["nom"]); ?>
                        </p>

                        <?php

                        if (
                            $produit["type_vente"] == "enchere"
                            &&
                            !empty($produit["id_enchere"])
                        ) {

                            echo "<hr>";
                            echo "<h4>Historique des mises</h4>";

                            $sqlHistorique = "
                                SELECT
                                    offre.montant,
                                    offre.date_offre,
                                    utilisateur.nom,
                                    utilisateur.prenom
                                FROM offre
                                INNER JOIN utilisateur
                                    ON offre.id_utilisateur = utilisateur.id_utilisateur
                                WHERE offre.id_enchere = ?
                                ORDER BY offre.date_offre DESC
                            ";

                            $stmtHistorique = $conn->prepare($sqlHistorique);
                            $stmtHistorique->bind_param("i", $produit["id_enchere"]);
                            $stmtHistorique->execute();

                            $historique = $stmtHistorique->get_result();

                            if ($historique->num_rows === 0) {

                                echo "<p>Aucune mise pour le moment.</p>";

                            } else {

                                while ($mise = $historique->fetch_assoc()) {

                        ?>

                                    <p>
                                        <?php echo htmlspecialchars($mise["prenom"]); ?>
                                        <?php echo htmlspecialchars($mise["nom"]); ?>

                                        -

                                        <?php
                                        echo number_format(
                                            $mise["montant"],
                                            2,
                                            ",",
                                            " "
                                        );
                                        ?>

                                        €

                                        <br>

                                        <?php echo htmlspecialchars($mise["date_offre"]); ?>
                                    </p>

                        <?php

                                }
                            }
                        }

                        ?>

                    </div>

                    <p class="prix-produit">
                        <?php echo number_format($prix_affiche, 2, ",", " "); ?> €
                    </p>

                    <?php

                    if (
                        $produit["type_vente"] == "enchere"
                        &&
                        !empty($produit["id_enchere"])
                    ) {

                        $sqlTop = "
                            SELECT
                                utilisateur.nom,
                                utilisateur.prenom,
                                offre.montant
                            FROM offre
                            INNER JOIN utilisateur
                                ON offre.id_utilisateur = utilisateur.id_utilisateur
                            WHERE offre.id_enchere = ?
                            ORDER BY offre.montant DESC
                            LIMIT 1
                        ";

                        $stmtTop = $conn->prepare($sqlTop);
                        $stmtTop->bind_param("i", $produit["id_enchere"]);
                        $stmtTop->execute();

                        $top = $stmtTop->get_result()->fetch_assoc();

                        if ($top) {

                    ?>

                            <p class="top-bidder">
                                Meilleure offre :

                                <?php echo htmlspecialchars($top["prenom"]); ?>
                                <?php echo htmlspecialchars($top["nom"]); ?>

                                (

                                <?php
                                echo number_format(
                                    $top["montant"],
                                    2,
                                    ",",
                                    " "
                                );
                                ?>

                                €)
                            </p>

                    <?php

                        }
                    }

                    ?>

                    <p class="type-produit-home">
                        <?php echo htmlspecialchars($produit["type_vente"]); ?>
                    </p>

                    <?php if ($produit["type_vente"] === "simple") { ?>

                        <form action="back_end/ajouter_panier.php" method="POST">

                            <input
                                type="hidden"
                                name="id_produit"
                                value="<?php echo intval($produit["id_produit"]); ?>"
                            >

                            <button type="submit" class="btn-action-produit">
                                Ajouter au panier
                            </button>

                        </form>

                    <?php } elseif ($produit["type_vente"] === "enchere") { ?>

                        <?php

                        $estVendeur =
                        (
                            $_SESSION["id_utilisateur"]
                            ==
                            $produit["id_vendeur"]
                        );

                        $estMeilleurEncherisseur = false;

                        if (!empty($produit["id_enchere"])) {

                            $sqlTopUser = "
                                SELECT id_utilisateur
                                FROM offre
                                WHERE id_enchere = ?
                                ORDER BY montant DESC
                                LIMIT 1
                            ";

                            $stmtTopUser = $conn->prepare($sqlTopUser);
                            $stmtTopUser->bind_param("i", $produit["id_enchere"]);
                            $stmtTopUser->execute();

                            $topUser = $stmtTopUser->get_result()->fetch_assoc();

                            if (
                                $topUser
                                &&
                                $topUser["id_utilisateur"]
                                ==
                                $_SESSION["id_utilisateur"]
                            ) {
                                $estMeilleurEncherisseur = true;
                            }
                        }

                        ?>

                        <?php if ($estVendeur) { ?>

                            <p>
                                Vous êtes le vendeur
                            </p>

                        <?php } elseif ($estMeilleurEncherisseur) { ?>

                            <p>
                                Vous êtes déjà le meilleur enchérisseur
                            </p>

                        <?php } else { ?>

                            <button
                                type="button"
                                class="btn-action-produit"
                                onclick="ouvrirMise(<?php echo intval($produit['id_produit']); ?>)"
                            >
                                Enchérir
                            </button>

                            <div
                                id="mise-<?php echo intval($produit['id_produit']); ?>"
                                style="display:none;"
                            >

                                <form
                                    action="back_end/encherir.php"
                                    method="POST"
                                >

                                    <input
                                        type="hidden"
                                        name="id_enchere"
                                        value="<?php echo intval($produit['id_enchere']); ?>"
                                    >

                                    <input
                                        type="number"
                                        step="0.01"
                                        name="montant"
                                        placeholder="Votre mise"
                                        required
                                    >

                                    <button type="submit">
                                        Confirmer
                                    </button>

                                    <button
                                        type="button"
                                        onclick="fermerMise(<?php echo intval($produit['id_produit']); ?>)"
                                    >
                                        Annuler
                                    </button>

                                </form>

                            </div>

                        <?php } ?>

                    <?php } elseif ($produit["type_vente"] === "particulier") { ?>

                        <a href="particuliers.php?id_produit=<?php echo intval($produit["id_produit"]); ?>">

                            <button class="btn-action-produit">
                                Négocier
                            </button>

                        </a>

                    <?php } ?>

                    <?php if (isset($_SESSION["role"]) && $_SESSION["role"] == "admin") { ?>

                        <form
                            action="back_end/supprimer_produit.php"
                            method="POST"
                            onsubmit="return confirm('Supprimer ce produit ?');"
                        >

                            <input
                                type="hidden"
                                name="id_produit"
                                value="<?php echo intval($produit["id_produit"]); ?>"
                            >

                            <button
                                type="submit"
                                class="btn-action-produit"
                            >
                                Supprimer
                            </button>

                        </form>

                    <?php } ?>

                </div>

        <?php

            }

        } else {

        ?>

            <p class="message-vide">

                <?php if ($recherche !== "") { ?>

                    Aucun produit trouvé pour "<?php echo htmlspecialchars($recherche); ?>".

                <?php } else { ?>

                    Aucun produit disponible pour le moment.

                <?php } ?>

            </p>

        <?php

        }

        ?>

    </div>

    <script>

        function toggleDetails(id)
        {
            let bloc = document.getElementById("details-" + id);
            let bouton = event.target;

            if (bloc.style.display === "block") {
                bloc.style.display = "none";
                bouton.innerHTML = "+";
            } else {
                bloc.style.display = "block";
                bouton.innerHTML = "-";
            }
        }

        function ouvrirMise(id)
        {
            document.getElementById("mise-" + id).style.display = "block";
        }

        function fermerMise(id)
        {
            document.getElementById("mise-" + id).style.display = "none";
        }

    </script>

</body>

</html>
