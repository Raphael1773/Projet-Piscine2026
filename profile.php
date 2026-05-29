<?php
session_start();
if (!isset($_SESSION["nom"])) {
    header("Location: front_end/login.html");
    exit();
}
include "back_end/db.php";
 
$sql = "SELECT * FROM utilisateur WHERE nom = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $_SESSION["nom"]);
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
 
    <div class="profile-container">
 
        <h1 class="profile-title">Mon Profil</h1>
 
        <div class="profile-card">
 
            <div class="profile-row">
                <span class="profile-label">Nom</span>
                <span class="profile-value">
                    <?php echo $user["nom"]; ?>
                </span>
            </div>
 
            <div class="profile-row">
                <span class="profile-label">Prénom</span>
                <span class="profile-value">
                    <?php echo $user["prenom"]; ?>
                </span>
            </div>
 
            <div class="profile-row">
                <span class="profile-label">Email</span>
                <span class="profile-value">
                    <?php echo $user["email"]; ?>
                </span>
            </div>
 
            <div class="profile-row">
                <span class="profile-label">Rôle</span>
                <span class="profile-value">
                    <?php echo $user["role"]; ?>
                </span>
            </div>
 
        </div>
 
        <div class="profile-buttons">
            <a href="home.php">
                <button>Retour à l'accueil</button>
            </a>
            <button onclick="deconnexion()">
                Se déconnecter
            </button>
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
                window.location.href =
                    "front_end/login.html";
            }
        }
    </script>
 
</body>
</html>