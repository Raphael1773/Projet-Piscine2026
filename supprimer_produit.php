<?php

session_start();

require_once "db.php";

if (
    !isset($_SESSION["id_utilisateur"])
    ||
    $_SESSION["role"] != "admin"
)
{
    exit();
}

$id_produit =
    intval($_POST["id_produit"]);

$sql =
"
SELECT id_enchere
FROM enchere
WHERE id_produit = ?
";

$stmt =
    $conn->prepare($sql);

$stmt->bind_param(
    "i",
    $id_produit
);

$stmt->execute();

$result =
    $stmt->get_result();

$enchere =
    $result->fetch_assoc();

if ($enchere)
{
    $id_enchere =
        $enchere["id_enchere"];

    $sql =
    "
    DELETE FROM offre
    WHERE id_enchere = ?
    ";

    $stmt =
        $conn->prepare($sql);

    $stmt->bind_param(
        "i",
        $id_enchere
    );

    $stmt->execute();

    $sql =
    "
    DELETE FROM enchere
    WHERE id_enchere = ?
    ";

    $stmt =
        $conn->prepare($sql);

    $stmt->bind_param(
        "i",
        $id_enchere
    );

    $stmt->execute();
}

$sql =
"
DELETE FROM produit
WHERE id_produit = ?
";

$stmt =
    $conn->prepare($sql);

$stmt->bind_param(
    "i",
    $id_produit
);

$stmt->execute();

header(
    "Location: ../home.php"
);

exit();