<?php

session_start();

require_once "db.php";

if (
    !isset($_SESSION["id_utilisateur"])
)
{
    exit();
}

$id_enchere =
    intval($_POST["id_enchere"]);

$montant =
    floatval($_POST["montant"]);

$sql =
"
SELECT MAX(montant) AS max_mise
FROM offre
WHERE id_enchere = ?
";

$stmt =
    $conn->prepare($sql);

$stmt->bind_param(
    "i",
    $id_enchere
);

$stmt->execute();

$result =
    $stmt->get_result();

$data =
    $result->fetch_assoc();

$max_mise =
    $data["max_mise"];

if (
    $max_mise === null
)
{
    $sql =
    "
    SELECT prix_depart
    FROM enchere
    WHERE id_enchere = ?
    ";

    $stmt =
        $conn->prepare(
            $sql
        );

    $stmt->bind_param(
        "i",
        $id_enchere
    );

    $stmt->execute();

    $res =
        $stmt->get_result();

    $row =
        $res->fetch_assoc();

    $max_mise =
        $row["prix_depart"];
}

if (
    $montant <= $max_mise
)
{
    header(
        "Location: ../home.php?type=enchere&erreur=mise"
    );
    exit();
}

$sql =
"
INSERT INTO offre
(
    montant,
    date_offre,
    id_enchere,
    id_utilisateur
)
VALUES
(
    ?,
    NOW(),
    ?,
    ?
)
";

$stmt =
    $conn->prepare(
        $sql
    );

$stmt->bind_param(
    "dii",
    $montant,
    $id_enchere,
    $_SESSION["id_utilisateur"]
);

$stmt->execute();

header(
    "Location: ../home.php?type=enchere"
);

exit();
