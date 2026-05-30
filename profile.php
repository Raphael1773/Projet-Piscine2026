<?php
session_start();
if (!isset($_SESSION["nom"])) {
    header("Location: front_end/login.html");
    exit();
}
include "back_end/db.php";

$sql = "SELECT * FROM utilisateur WHERE id_utilisateur = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $_SESSION["id_utilisateur"]);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Mon Profil - Mercato Nova</title>
    <link rel="stylesheet" href="front_end/style.css">
</head>
<body class="home-body">
 
    <div class="navbar">
        <div class="left-section">
            <div class="logo">LOGO</div>
            <a href="home.php" class="site-name"> Mercato Nova </a>        
        </div>
        <div class="search-section">
            <input type="text" placeholder="Recherche..." class="search-bar">
        </div>
        <div class="right-section">
            <a href="panier.php" class="icon-link">Panier</a>
            <a href="profile.php" class="icon-link">Profil</a>
        </div>
    </div>
 
    <div class="profile-wrapper">
        <div class="profile-box">
 
            <div class="profile-title-box">Profil</div>
 
            <div class="profile-content">
                <div class="profile-left">
                    <p><span class="profile-label">Nom :</span> <?php echo $user["nom"]; ?></p>
                    <p><span class="profile-label">Prénom :</span> <?php echo $user["prenom"]; ?></p>
                    <p><span class="profile-label">Email :</span> <?php echo $user["email"]; ?></p>
                </div>
                <div class="profile-right">
                    <span class="profile-label">Rôle :</span>
                    <div class="role-box"><?php echo $user["role"]; ?></div>
                </div>
            </div>
 
            <div class="profile-actions">
                <a href="mise_en_vente.php">
                    <button class="profile-btn">Mettre en vente un produit</button>
                </a>
                <button class="profile-btn deconnexion-btn" onclick="deconnexion()">
                    Se Déconnecter
                </button>
            </div>
 
        </div>
    </div>
 
    <script>
        async function deconnexion() {
            const response = await fetch(
                "back_end/logout.php",
                { method: "POST" }
            );
            const data = await response.json();
            if (data.success) {
                window.location.href = "front_end/login.html";
            }
        }
    </script>
 
</body>
</html>
