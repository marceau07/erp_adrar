<?php
session_start();
$csrfToken = bin2hex(random_bytes(32)); // Génère un jeton CSRF unique
$_SESSION['csrf_token'] = $csrfToken;
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="title" content="ADRAR ERP - Connexion">
    <meta name="description" content="ERP de l'ADRAR. Suivez vos stages et alimentez vos cours pour vos sessions en cours et à venir si vous êtes formateur ; et rendez les travaux attendus si vous êtes stagiaire.">
    <link rel="apple-touch-icon" sizes="180x180" href="//<?= $_SERVER["SERVER_NAME"] ?>/erp/public/img/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="//<?= $_SERVER["SERVER_NAME"] ?>/erp/public/img/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="//<?= $_SERVER["SERVER_NAME"] ?>/erp/public/img/favicon-16x16.png">
    <link rel="manifest" href="//<?= $_SERVER["SERVER_NAME"] ?>/erp/public/site.webmanifest">

    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="//<?= $_SERVER["SERVER_NAME"] ?>/erp/public/connexion.php">
    <meta property="og:title" content="ADRAR ERP - Connexion">
    <meta property="og:description" content="ERP de l'ADRAR. Connectez-vous pour en voir plus.">
    <meta property="og:image" content="//<?= $_SERVER["SERVER_NAME"] ?>/erp/public/img/logo_adrar.png">

    <!-- Twitter -->
    <meta property="twitter:card" content="summary_large_image">
    <meta property="twitter:url" content="//<?= $_SERVER["SERVER_NAME"] ?>/erp/public/connexion.php">
    <meta property="twitter:title" content="ADRAR ERP - Connexion">
    <meta property="twitter:description" content="ERP de l'ADRAR. Connectez-vous pour en voir plus.">
    <meta property="twitter:image" content="//<?= $_SERVER["SERVER_NAME"] ?>/erp/public/img/logo_adrar.png">

    <meta name="robots" content="index, follow">
    <title>ADRAR ERP - Connexion</title>
    <link rel="stylesheet" href="css/_reset.css">
    <link rel="stylesheet" href="//<?= $_SERVER["SERVER_NAME"] ?>/erp/public/formation/css/bootstrap.min.css">
    <link rel="stylesheet" href="css/_style.css">
    <link rel="stylesheet" href="css/connexion.css">
</head>

<body>
    <div class="container justify-content-center">
        <div class="row">
            <div class="col-12 text-center">
                <img src="img/logo_adrar.png" alt="Logo de l'ADRAR">
                <h1 class="text-white">Re bienvenue sur l'ERP</h1>
            </div>
            <div class="col-12">
                <form action="../src/c/c_requetes.php" method="post">
                    <div class="<?= (isset($_GET['message']) && !empty($_GET['message']) && isset($_COOKIE['DECONNECTE']) && !empty($_COOKIE['DECONNECTE']) ? "" : "hidden ") ?>text-center alert <?= (isset($_GET['type']) && !empty($_GET['type']) && $_GET['type'] == "error" ? "alert-danger" : ((isset($_GET['type']) && !empty($_GET['type']) && $_GET['type'] == "success") ? "bg-success" : "alert-info")) ?>"><?= @urldecode($_GET['message']) ?></div>
                    <input type="hidden" name="form_login_url" value="<?= $_GET['url'] ?>">
                    <input type="hidden" name="form_login_csrf" value="<?= $csrfToken ?>">
                    <div>
                        <label for="form_login_username" class="text-white">Nom d'utilisateur</label>
                        <input type="text" class="form-control border-0 float-end" name="form_login_username" placeholder="johndoe" autocomplete="off">
                    </div>
                    <div>
                        <label for="form_login_pass" class="text-white">Mot de passe</label>
                        <input type="password" class="form-control border-0 float-end" name="form_login_pass" placeholder="motdepasse" autocomplete="off">
                    </div>
                    <p class="text-white">Mot de passe oublié ? <a href="oublie.php">Générez-en un nouveau</a></a></p>
                    <input type="submit" value="Se connecter" class="btn btn-primary full-width text-white">
                </form>
            </div>
        </div>
    </div>
</body>

</html>