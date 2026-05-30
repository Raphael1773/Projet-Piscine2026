<?php

session_start();

if (!isset($_SESSION["id_utilisateur"])) {

    header("Location: front_end/login.html");

    exit();
}

include "back_end/db.php";

$sql = "SELECT * FROM produit ORDER BY id_produit DESC";

$result = $conn->query($sql);

?>

<!DOCTYPE html>

<html>

<head>

    <title>Mercato Nova</title>

    <link rel="stylesheet" href="front_end/style.css?v=20">

</head>

<body class="home-body">

    <div class="navbar">

        <div class="left-section">

            <div class="logo">
                LOGO
            </div>

            <div class="site-name">
                Mercato Nova
            </div>

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

        <button>
            Produits
        </button>

        <button>
            Encheres
        </button>

        <button>
            Particuliers
        </button>

    </div>

    <div class="products-grid">

        <?php

        while ($produit = $result->fetch_assoc()) {

        ?>

            <div class="product-card">

                <?php

                if (
                    !empty($produit["image"]) &&
                    file_exists(
                        "images/" . $produit["image"]
                    )
                ) {

                ?>

                    <img
                        src="images/<?php echo $produit["image"]; ?>"
                        class="product-image"
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

                <p>

                    <?php echo htmlspecialchars($produit["description"]); ?>

                </p>

                <p>

                    <?php echo $produit["prix"]; ?> €

                </p>

                <p>

                    <?php echo htmlspecialchars($produit["type_vente"]); ?>

                </p>

            </div>

        <?php

        }

        ?>

    </div>

</body>

</html>
