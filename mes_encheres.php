<?php

session_start();

if (!isset($_SESSION["id_utilisateur"])) {
    header("Location: front_end/login.html");
    exit();
}

require_once "back_end/db.php";

$idUtilisateur = $_SESSION["id_utilisateur"];

$sqlEnCours = "
SELECT DISTINCT
    produit.*,
    enchere.id_enchere,
    enchere.date_fin
FROM offre
INNER JOIN enchere
ON offre.id_enchere = enchere.id_enchere
INNER JOIN produit
ON enchere.id_produit = produit.id_produit
WHERE offre.id_utilisateur = ?
AND enchere.date_fin > NOW()
";

$stmt = $conn->prepare($sqlEnCours);
$stmt->bind_param("i", $idUtilisateur);
$stmt->execute();
$encheresEnCours = $stmt->get_result();

$sqlTerminees = "
SELECT DISTINCT
    produit.*,
    enchere.id_enchere,
    enchere.date_fin
FROM offre
INNER JOIN enchere
ON offre.id_enchere = enchere.id_enchere
INNER JOIN produit
ON enchere.id_produit = produit.id_produit
WHERE offre.id_utilisateur = ?
AND enchere.date_fin <= NOW()
";

$stmt = $conn->prepare($sqlTerminees);
$stmt->bind_param("i", $idUtilisateur);
$stmt->execute();
$encheresTerminees = $stmt->get_result();

?>

<!DOCTYPE html>

<html>

<head>

<meta charset="UTF-8">

<title>Mes enchères</title>

<link rel="stylesheet" href="front_end/style.css">

</head>

<body class="home-body">

<div class="navbar">

    <div class="left-section">

        <div class="logo">
    <img src="images/logo.png" alt="Logo Mercato Nova">
</div>

        <a href="home.php" class="site-name">
            Mercato Nova
        </a>

    </div>

    <div class="right-section">

        <a href="home.php" class="icon-link">
            Retour
        </a>

    </div>

</div>

<h1 style="text-align:center;margin-top:40px;">
    Mes enchères
</h1>

<h2 style="text-align:center;">
    Mes enchères en cours
</h2>

<div class="products-grid">

<?php

while ($produit = $encheresEnCours->fetch_assoc()) {

?>

<div class="product-card">

<?php

if (
    !empty($produit["image"]) &&
    file_exists("images/" . $produit["image"])
) {

?>

<img
src="images/<?php echo $produit["image"]; ?>"
class="product-image"
>

<?php

}

?>

<h3>
<?php echo htmlspecialchars($produit["titre"]); ?>
</h3>

<p>
Fin :
<?php echo $produit["date_fin"]; ?>
</p>

<p class="type-produit-home">
En cours
</p>

</div>

<?php

}

?>

</div>

<hr style="margin:50px;">

<h2 style="text-align:center;">
    Mes enchères terminées
</h2>

<div class="products-grid">

<?php

while ($produit = $encheresTerminees->fetch_assoc()) {

    $sqlTop = "
    SELECT id_utilisateur
    FROM offre
    WHERE id_enchere = ?
    ORDER BY montant DESC
    LIMIT 1
    ";

    $stmtTop = $conn->prepare($sqlTop);

    $stmtTop->bind_param(
        "i",
        $produit["id_enchere"]
    );

    $stmtTop->execute();

    $top =
        $stmtTop
        ->get_result()
        ->fetch_assoc();

    $gagne =
        (
            $top &&
            $top["id_utilisateur"]
            == $idUtilisateur
        );

?>

<div class="product-card">

<?php

if (
    !empty($produit["image"]) &&
    file_exists("images/" . $produit["image"])
) {

?>

<img
src="images/<?php echo $produit["image"]; ?>"
class="product-image"
>

<?php

}

?>

<h3>
<?php echo htmlspecialchars($produit["titre"]); ?>
</h3>

<p>
Fin :
<?php echo $produit["date_fin"]; ?>
</p>

<?php if ($gagne) { ?>

<p style="color:lightgreen;">
Vous avez gagné
</p>

<?php } else { ?>

<p style="color:#ff6666;">
Vous avez perdu
</p>

<?php } ?>

</div>

<?php

}

?>

</div>

</body>

</html>