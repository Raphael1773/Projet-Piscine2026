<?php

session_start();

if (!isset($_SESSION["id_utilisateur"])) {
    header("Location: front_end/login.html");
    exit();
}

include "back_end/db.php";

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $titre = $_POST["titre"];
    $description = $_POST["description"];
    $prix = $_POST["prix"];
    $etat = $_POST["etat"];
    $type_vente = $_POST["type_vente"];
    $image = $_POST["image"];
    $categorie = $_POST["categorie"];

    $sql = "INSERT INTO produit
    (
        titre,
        description,
        prix,
        etat,
        date_publication,
        statut,
        type_vente,
        id_vendeur,
        id_categorie,
        image
    )
    VALUES
    (
        ?, ?, ?, ?,
        NOW(),
        'actif',
        ?, ?, ?, ?
    )";

    $stmt = $conn->prepare($sql);

    $stmt->bind_param(
        "ssdssiis",
        $titre,
        $description,
        $prix,
        $etat,
        $type_vente,
        $_SESSION["id_utilisateur"],
        $categorie,
        $image
    );

	if ($stmt->execute()) {

		$id_produit =
			$conn->insert_id;

		if (
			$type_vente == "enchere"
		)
		{
			$jours =
				intval($_POST["jours"]);

			$heures =
				intval($_POST["heures"]);

			$minutes =
				intval($_POST["minutes"]);

			$date_fin =
				date(
					"Y-m-d H:i:s",
					time()
					+
					($jours * 86400)
					+
					($heures * 3600)
					+
					($minutes * 60)
				);

			$sqlEnchere =
			"
			INSERT INTO enchere
			(
				prix_depart,
				date_fin,
				id_produit
			)
			VALUES
			(
				?,
				?,
				?
			)
			";

			$stmtEnchere =
				$conn->prepare(
					$sqlEnchere
				);

			$stmtEnchere->bind_param(
				"dsi",
				$prix,
				$date_fin,
				$id_produit
			);

			$stmtEnchere->execute();
		}

		header(
			"Location: home.php"
		);

		exit();
	}
	else {
        $message = "Erreur lors de l'ajout";
    }
}

$categories = $conn->query(
    "SELECT * FROM categorie"
);

?>

<!DOCTYPE html>
<html>

<head>

    <title>Mettre en vente</title>

    <link rel="stylesheet"
          href="front_end/style.css?v=10">

</head>

<body class="home-body">

<div class="profile-wrapper">

    <div class="profile-box">

        <div class="profile-title-box">
            Mettre un produit en vente
        </div>

        <?php if($message != "") { ?>

            <p style="text-align:center;">
                <?php echo $message; ?>
            </p>

        <?php } ?>

        <form method="POST">

            <input
                type="text"
                name="titre"
                placeholder="Titre"
                required
            >

            <textarea
                name="description"
                placeholder="Description"
                required
            ></textarea>

            <input
                type="number"
                step="0.01"
                name="prix"
                placeholder="Prix"
                required
            >

            <input
                type="text"
                name="etat"
                placeholder="Etat"
                required
            >

			<select
				name="type_vente"
				id="type_vente"
				onchange="gererTypeVente()"
			>

			<?php if ($_SESSION["role"] == "admin") { ?>

				<option value="simple">
					Simple
				</option>

			<?php } ?>

				<option value="enchere">
					Enchère
				</option>

				<option value="particulier">
					Particulier
				</option>

			</select>

			<div
				id="options-enchere"
				style="display:none;"
			>

				<h3>
					Durée de l'enchère
				</h3>

				<input
					type="number"
					name="jours"
					min="0"
					placeholder="Jours"
				>

				<input
					type="number"
					name="heures"
					min="0"
					max="23"
					placeholder="Heures"
				>

				<input
					type="number"
					name="minutes"
					min="0"
					max="59"
					placeholder="Minutes"
				>

			</div>
			<script>

			function gererTypeVente()
			{
				let type =
					document.getElementById(
						"type_vente"
					).value;

				let bloc =
					document.getElementById(
						"options-enchere"
					);

				if (type == "enchere")
				{
					bloc.style.display = "block";
				}
				else
				{
					bloc.style.display = "none";
				}
			}

			window.onload =
				gererTypeVente;

			</script>
			
            <input
                type="text"
                name="image"
                placeholder="Nom image (ex: ps5.jpg)"
            >

            <select
                name="categorie"
                required
            >

                <?php while($cat = $categories->fetch_assoc()) { ?>

                    <option
                        value="<?php echo $cat["id_categorie"]; ?>"
                    >
                        <?php echo $cat["nom"]; ?>
                    </option>

                <?php } ?>

            </select>
			<br><br>

			<a href="home.php">
				<button
					type="button"
					class="profile-btn"
				>
					Retour
				</button>
			</a>

			<br><br>
           

            <button type="submit">
                Publier
            </button>

        </form>

    </div>

</div>

</body>

</html>
