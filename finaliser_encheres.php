<?php

function finaliserEncheres($conn)
{
    /*
        On récupère les enchères terminées mais encore actives.
    */
    $sql = "
        SELECT 
            enchere.id_enchere,
            enchere.id_produit,
            produit.titre
        FROM enchere
        INNER JOIN produit ON enchere.id_produit = produit.id_produit
        WHERE enchere.date_fin <= NOW()
        AND enchere.statut = 'active'
        AND produit.statut = 'actif'
    ";

    $result = $conn->query($sql);

    while ($enchere = $result->fetch_assoc()) {

        $id_enchere = intval($enchere["id_enchere"]);
        $id_produit = intval($enchere["id_produit"]);

        /*
            On cherche la meilleure offre.
            En cas d'égalité, la plus ancienne gagne.
        */
        $sql_gagnant = "
            SELECT 
                offre.id_utilisateur,
                offre.montant
            FROM offre
            WHERE offre.id_enchere = ?
            ORDER BY offre.montant DESC, offre.date_offre ASC
            LIMIT 1
        ";

        $stmt_gagnant = $conn->prepare($sql_gagnant);
        $stmt_gagnant->bind_param("i", $id_enchere);
        $stmt_gagnant->execute();

        $result_gagnant = $stmt_gagnant->get_result();

        /*
            Si personne n'a enchéri, l'enchère expire.
        */
        if ($result_gagnant->num_rows === 0) {

            $sql_expire = "
                UPDATE enchere
                SET statut = 'expiree'
                WHERE id_enchere = ?
            ";

            $stmt_expire = $conn->prepare($sql_expire);
            $stmt_expire->bind_param("i", $id_enchere);
            $stmt_expire->execute();

            continue;
        }

        $gagnant = $result_gagnant->fetch_assoc();

        $id_gagnant = intval($gagnant["id_utilisateur"]);
        $prix_gagnant = floatval($gagnant["montant"]);

        /*
            On cherche le panier actif du gagnant.
        */
        $sql_panier = "
            SELECT id_panier
            FROM panier
            WHERE id_utilisateur = ?
            AND statut = 'actif'
            LIMIT 1
        ";

        $stmt_panier = $conn->prepare($sql_panier);
        $stmt_panier->bind_param("i", $id_gagnant);
        $stmt_panier->execute();

        $result_panier = $stmt_panier->get_result();

        if ($result_panier->num_rows > 0) {

            $panier = $result_panier->fetch_assoc();
            $id_panier = intval($panier["id_panier"]);

        } else {

            $sql_create_panier = "
                INSERT INTO panier
                (date_creation, id_utilisateur, statut)
                VALUES
                (NOW(), ?, 'actif')
            ";

            $stmt_create = $conn->prepare($sql_create_panier);
            $stmt_create->bind_param("i", $id_gagnant);
            $stmt_create->execute();

            $id_panier = $conn->insert_id;
        }

        /*
            On vérifie que le produit n'est pas déjà dans le panier.
        */
        $sql_ligne_existante = "
            SELECT id_ligne
            FROM lignepanier
            WHERE id_panier = ?
            AND id_produit = ?
            LIMIT 1
        ";

        $stmt_ligne_existante = $conn->prepare($sql_ligne_existante);
        $stmt_ligne_existante->bind_param("ii", $id_panier, $id_produit);
        $stmt_ligne_existante->execute();

        $result_ligne_existante = $stmt_ligne_existante->get_result();

        if ($result_ligne_existante->num_rows === 0) {

            $sql_ligne = "
                INSERT INTO lignepanier
                (quantite, prix_unitaire, id_panier, id_produit)
                VALUES
                (1, ?, ?, ?)
            ";

            $stmt_ligne = $conn->prepare($sql_ligne);
            $stmt_ligne->bind_param("dii", $prix_gagnant, $id_panier, $id_produit);
            $stmt_ligne->execute();
        }

        /*
            L'enchère est terminée.
        */
        $sql_update_enchere = "
            UPDATE enchere
            SET statut = 'terminee'
            WHERE id_enchere = ?
        ";

        $stmt_update_enchere = $conn->prepare($sql_update_enchere);
        $stmt_update_enchere->bind_param("i", $id_enchere);
        $stmt_update_enchere->execute();

        /*
            Le produit est réservé au gagnant.
            Donc il ne s'affiche plus dans le home.
        */
        $sql_update_produit = "
            UPDATE produit
            SET statut = 'reserve'
            WHERE id_produit = ?
        ";

        $stmt_update_produit = $conn->prepare($sql_update_produit);
        $stmt_update_produit->bind_param("i", $id_produit);
        $stmt_update_produit->execute();
    }
}

?>