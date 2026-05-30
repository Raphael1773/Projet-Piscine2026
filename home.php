<?php

session_start();

if (!isset($_SESSION["id_utilisateur"])) {

    header("Location: front_end/login.html");

    exit();
}

require_once __DIR__ . "/back_end/db.php";

/*
    Filtre de la page :
    - tous : tous les produits
    - simple : achats immédiats
    - enchere : enchères
    - particulier : négociation
*/

$type_filtre = $_GET["type"] ?? "tous";

if (
    $type_filtre === "simple" ||
    $type_filtre === "enchere" ||
    $type_filtre === "particulier"
) {

	$sql = "
	SELECT
		produit.*,
		utilisateur.nom,
		utilisateur.prenom
	FROM produit
	LEFT JOIN utilisateur
	ON produit.id_vendeur = utilisateur.id_utilisateur
	WHERE produit.type_vente = ?
	ORDER BY produit.id_produit DESC
	";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $type_filtre);
    $stmt->execute();

    $result = $stmt->get_result();

} else {

	$sql = "
	SELECT
		produit.*,
		utilisateur.nom,
		utilisateur.prenom
	FROM produit
	LEFT JOIN utilisateur
	ON produit.id_vendeur = utilisateur.id_utilisateur
	ORDER BY produit.id_produit DESC
	";
    $result = $conn->query($sql);
}

?>

<!DOCTYPE html>

<html>

<head>

    <meta charset="UTF-8">

    <title>Mercato Nova</title>

    <link rel="stylesheet" href="front_end/style.css?v=30">

</head>

<body class="home-body">

    <div class="navbar">

        <div class="left-section">

            <div class="logo">
                LOGO
            </div>

            <a href="home.php" class="site-name">
              Mercato Nova
            </a>

        </div>

        <div class="search-section">

            <input
                type="text"
                placeholder="Recherche..."
                class="search-bar"
            >

        </div>

        <div class="right-section">

            <a href="panier.php" class="icon-link">
                Panier
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

        ?>

                <div class="product-card">

                    <?php

                    if (
                        !empty($produit["image"]) &&
                        file_exists("images/" . $produit["image"])
                    ) {

                    ?>

                        <img
                            src="images/<?php echo htmlspecialchars($produit["image"]); ?>"
                            class="product-image"
                            alt="<?php echo htmlspecialchars($produit["titre"]); ?>"
                        >

                    <?php

                    } else {

                    ?>

                        <div class="product-image">

                            Pas d'image

                        </div>

                    <?php

                    }

                    ?>

                    <h3>

                        <?php echo htmlspecialchars($produit["titre"]); ?>

                    </h3>

                    <button
						class="btn-details"
						onclick="toggleDetails(<?php echo $produit['id_produit']; ?>)"
					>
						+
					</button>

					<div
						id="details-<?php echo $produit['id_produit']; ?>"
						class="details-produit"
					>

						<p>

							<?php echo htmlspecialchars($produit["description"]); ?>

						</p>

						<p>

							Etat :
							<?php echo htmlspecialchars($produit["etat"]); ?>

						</p>

						<p>

							Date :
							<?php echo htmlspecialchars($produit["date_publication"]); ?>

						</p>

						<p>

							Vendeur :
							<?php echo htmlspecialchars($produit["prenom"]); ?>
							<?php echo htmlspecialchars($produit["nom"]); ?>

						</p>

					</div>

                    <p class="prix-produit">

                        <?php echo number_format($produit["prix"], 2, ",", " "); ?> €

                    </p>

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

                        <a href="encheres.php?id_produit=<?php echo intval($produit["id_produit"]); ?>">

                            <button class="btn-action-produit">
                                Enchérir
                            </button>

                        </a>

                    <?php } elseif ($produit["type_vente"] === "particulier") { ?>

                        <a href="particuliers.php?id_produit=<?php echo intval($produit["id_produit"]); ?>">

                            <button class="btn-action-produit">
                                Négocier
                            </button>

                        </a>

                    <?php } ?>

                </div>

        <?php

            }

        } else {

        ?>

            <p class="message-vide">
                Aucun produit disponible pour le moment.
            </p>

        <?php

        }

        ?>

    </div>
	<script>

	function toggleDetails(id)
	{
		let bloc =
			document.getElementById(
				"details-" + id
			);

		let bouton =
			event.target;

		if (
			bloc.style.display === "block"
		) {

			bloc.style.display = "none";

			bouton.innerHTML = "+";

		}
		else {

			bloc.style.display = "block";

			bouton.innerHTML = "-";

		}
	}

	</script>
</body>

</html>
