<?php include_once("../../src/m/connect.php"); ?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <!-- Google tag (gtag.js) -->
    <!-- <script async src="https://www.googletagmanager.com/gtag/js?id=G-35N34572F3"></script>
    <script>
        window.dataLayer = window.dataLayer || [];

        function gtag() {
            dataLayer.push(arguments);
        }
        gtag('js', new Date());

        gtag('config', 'G-35N34572F3');
    </script> -->
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
    <meta name="theme-color" content="#317EFB">
    <meta name="description" content="Retrouvez l'ensemble des cours de votre Session sur cette page.">
    <link rel="apple-touch-icon" sizes="180x180" href="//<?= $_SERVER["SERVER_NAME"] ?>/erp/public/img/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="//<?= $_SERVER["SERVER_NAME"] ?>/erp/public/img/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="//<?= $_SERVER["SERVER_NAME"] ?>/erp/public/img/favicon-16x16.png">
    <link rel="manifest" href="//<?= $_SERVER["SERVER_NAME"] ?>/erp/public/site.webmanifest">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@splidejs/splide@4.1.4/dist/css/splide.min.css">
    <script src="https://cdn.jsdelivr.net/npm/@splidejs/splide@4.1.4/dist/js/splide.min.js"></script>

    <!-- Pour la HeatMap de Clarity by Microsoft -->
    <!-- <script>
        (function(c, l, a, r, i, t, y) {
            c[a] = c[a] || function() {
                (c[a].q = c[a].q || []).push(arguments)
            };
            t = l.createElement(r);
            t.async = 1;
            t.src = "https://www.clarity.ms/tag/" + i;
            y = l.getElementsByTagName(r)[0];
            y.parentNode.insertBefore(t, y);
        })(window, document, "clarity", "script", "jzc1fz18xt");
    </script> -->
    <!-- CSS only -->
    <link rel="stylesheet" href="//<?= $_SERVER["SERVER_NAME"] ?>/erp/public/formation/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.3.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/2.0.5/css/dataTables.bootstrap5.css">
    <?php if (isset($_SESSION["utilisateur"]["formateur_id"])) { ?>
        <link href="//cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
        <style>
            .select2-container {
                z-index: 999999;
            }

            .img-profile {
                width: 150px;
                height: 150px;
            }

            .img-preview {
                width: 75px;
                height: 75px;
                cursor: pointer;
            }

            .img-selected {
                border: 2px solid green;
            }

            .bg-v1-primary {
                background-color: #f5f5f5 !important;
            }
        </style>
    <?php } ?>
    <link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.1/css/all.min.css" integrity="sha512-MV7K8+y+gLIBoVD59lQIYicR65iaqukzvf/nwasF0nqhPay5w/9lJmVM2hMDcnK1OnMGCdVK+iQrJ7lzPJQd1w==" crossorigin="anonymous" referrerpolicy="no-referrer">
    <link rel="stylesheet" href="//<?= $_SERVER["SERVER_NAME"] ?>/erp/public/formation/css/base.css?v=<?= uniqid() ?>">
    <link rel="stylesheet" href="//<?= $_SERVER["SERVER_NAME"] ?>/erp/public/formation/css/connexion.css?v=<?= uniqid() ?>">
    <link rel="stylesheet" href="//<?= $_SERVER["SERVER_NAME"] ?>/erp/public/formation/css/tables.css?v=<?= uniqid() ?>">
    <link rel="stylesheet" href="//<?= $_SERVER["SERVER_NAME"] ?>/erp/public/formation/css/evals.css?v=<?= uniqid() ?>">
    <link rel="stylesheet" href="//<?= $_SERVER["SERVER_NAME"] ?>/erp/public/formation/css/animations.css?v=<?= uniqid() ?>">
    <link rel="stylesheet" href="//<?= $_SERVER["SERVER_NAME"] ?>/erp/public/formation/css/bal.css?v=<?= uniqid() ?>">
    <link rel="stylesheet" href="//<?= $_SERVER["SERVER_NAME"] ?>/erp/public/formation/css/admin.css?v=<?= uniqid() ?>">
    <title>Plateforme pédagogique<?= $title ?></title>
</head>

<body>
    <div class="wrapper pb-5">
        <?php if (isset($_SESSION["utilisateur"]["stagiaire_id"])) { ?>
            <nav class="navbar navbar-expand-lg navbar-light border-bottom border-light sticky-top bg-v1-primary" id="navbar">
                <div class="container-fluid">
                    <a class="navbar-brand" href="//<?= $_SERVER["SERVER_NAME"] ?>/erp/public/formation">
                        <img src="//<?= $_SERVER["SERVER_NAME"] ?>/erp/public/formation/imgs/adrar_logo.svg" alt="Logo de l'ADRAR" id="logo_adrar">
                    </a>
                    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarText" aria-controls="navbarText" aria-expanded="false" aria-label="Toggle navigation">
                        <span class="navbar-toggler-icon"></span>
                    </button>
                    <div class="collapse navbar-collapse" id="navbarText">
                        <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                            <li class="nav-item">
                                <a class="nav-link" aria-current="page" href="//<?= $_SERVER["SERVER_NAME"] ?>/erp/public/formation">Accueil</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" aria-current="page" href="//<?= $_SERVER["SERVER_NAME"] ?>/erp/public/formation/modules.php">Modules</a>
                            </li>
                            <?php
                            if ($_SESSION["utilisateur"]["formateur_id"] !== -1) { ?>
                                <li class="nav-item">
                                    <a class="nav-link" href="//<?= $_SERVER["SERVER_NAME"] ?>/erp/public/formation/admin.php">Administration</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="//<?= $_SERVER["SERVER_NAME"] ?>/erp/public/formation/boite-aux-lettres.php">BAL</a>
                                </li>
                            <?php } elseif (false) { ?>
                            <?php } ?>
                            <li class="nav-item">
                                <a class="nav-link" href="//<?= $_SERVER["SERVER_NAME"] ?>/erp/public/formation/faq.php">FAQ</a>
                            </li>
                            <?php
                            $sql_evaluations = "SELECT evaluation_dd_name, evaluation_dd_link 
                                            FROM evaluations_dd 
                                            JOIN evaluations_sessions ON(id_dd_evaluation=evaluation_dd_id AND id_session=:id_session AND evaluation_session_active = 1)
                                            WHERE evaluation_dd_active = 1;";
                            $req_evaluations = $db->prepare($sql_evaluations);
                            $req_evaluations->bindValue(":id_session", filter_var($_SESSION['utilisateur']['id_session'], FILTER_VALIDATE_INT));
                            $req_evaluations->execute();
                            $evaluations = $req_evaluations->fetchAll(PDO::FETCH_ASSOC);
                            $req_evaluations->closeCursor();
                            if (!empty($evaluations)) { ?>
                                <li class="nav-item dropdown">
                                    <a class="nav-link dropdown-toggle" href="#" id="navbar_dropdown_modules" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                        Évaluations
                                    </a>
                                    <ul class="dropdown-menu" aria-labelledby="navbar_dropdown_modules">
                                        <?php foreach ($evaluations as $eval) { ?>
                                            <li><a class="dropdown-item" href="//<?= $_SERVER["SERVER_NAME"] ?>/erp/public/formation/modules/<?= $eval["evaluation_dd_link"] ?>/"><?= $eval["evaluation_dd_name"] ?></a></li>
                                        <?php } ?>
                                    </ul>
                                </li>
                            <?php }
                            $sql_quiz = "SELECT quiz_module  
                                    FROM quiz 
                                    JOIN quiz_sessions ON(quiz_id = id_quiz AND id_session=:id_session AND quiz_session_active = 1)
                                    GROUP BY quiz_module;";
                            $req_quiz = $db->prepare($sql_quiz);
                            $req_quiz->bindValue(':id_session', filter_var($_SESSION['utilisateur']['id_session'], FILTER_VALIDATE_INT));
                            $req_quiz->execute();
                            $quizs = $req_quiz->fetchAll(PDO::FETCH_ASSOC);
                            $req_quiz->closeCursor();
                            if (!empty($quizs)) { ?>
                                <li class="nav-item dropdown">
                                    <a class="nav-link dropdown-toggle" href="#" id="navbar_dropdown_quiz" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                        Quiz
                                    </a>
                                    <ul class="dropdown-menu" aria-labelledby="navbar_dropdown_quiz">
                                        <?php foreach ($quizs as $quiz) { ?>
                                            <li><a class="dropdown-item" href="//<?= $_SERVER["SERVER_NAME"] ?>/erp/public/formation/quiz?cours=<?= strtolower($quiz["quiz_module"]) ?>"><?= strtoupper($quiz["quiz_module"]) ?></a></li>
                                        <?php } ?>
                                    </ul>
                                </li>
                            <?php } ?>
                            <?php
                            if ($_SESSION["utilisateur"]["formateur_id"] > 0) { ?>
                                <li class="nav-item">
                                    <select name="form_filter_session" id="form_filter_session" class="form-select pe-5">
                                        <option value="-1">Tout le secteur</option>
                                        <option value="0" <?= (empty($_SESSION['filtres']['session_id']) ? " selected" : "") ?>>Toutes mes sessions</option>
                                        <?php $sessions = recupererSessions($_SESSION['utilisateur']['formateur_id']);
                                        if (!empty($sessions)) {
                                            foreach ($sessions as $session) { ?>
                                                <option value="<?= $session['session_id'] ?>" <?= (isset($_SESSION['filtres']['session_id']) && $_SESSION['filtres']['session_id'] == $session['session_id'] ? " selected" : "") ?>><?= $session['session_nom'] ?></option>
                                        <?php }
                                        } ?>
                                    </select>
                                </li>
                            <?php } ?>
                        </ul>
                        <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                            <?php
                            if ($_SESSION["utilisateur"]["stagiaire_id"] > 0) {
                                echo '<a href="#" onclick="loadNewNotifications(true);" class="text-decoration-none"><i class="fas fa-bell fa-lg text-grey position-relative"><span class="notifications-count"></span></i></a>&nbsp;';
                                $nom_prenom = ucwords($_SESSION["utilisateur"]["stagiaire_prenom"]) . " " . strtoupper($_SESSION["utilisateur"]["stagiaire_nom"]);
                            } elseif ($_SESSION["utilisateur"]["formateur_id"] > 0) {
                                $nom_prenom = ucwords($_SESSION["utilisateur"]["formateur_prenom"]) . " " . strtoupper($_SESSION["utilisateur"]["formateur_nom"]);
                            }
                            ?>

                            <?php if ($_SERVER['REMOTE_ADDR'] == "86.214.69.212") {
                                // https://speckyboy.com/notification-css-javascript/ 
                            ?>
                                <!-- <i id="line" class="line"><span id="notification" style="padding-left: 15px;" id="notification" class="float fas icon notification"></span></i> -->
                            <?php } ?>
                            <?php
                            if ($_SESSION["utilisateur"]["formateur_id"] > 0) { ?>
                                <li class="nav-item">
                                    <div class="nav-link form-check form-switch">
                                        <input class="form-check-input" type="checkbox" role="switch" id="form_edition_mode" onclick="toggleEditionMode();" <?= (isset($_SESSION['mode_edition']) && !empty($_SESSION['mode_edition']) ? 'checked' : '') ?>>
                                        <label class="form-check-label" for="form_edition_mode">Mode édition</label>
                                    </div>
                                </li>
                            <?php } ?>
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" id="navbar_dropdown_user" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <?= $nom_prenom ?>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbar_dropdown_user">
                                    <li><a class="dropdown-item" href="//<?= $_SERVER["SERVER_NAME"] ?>/erp/public/formation/account.php">Modifier mes informations</a></li>
                                    <?php
                                    if ($_SESSION["utilisateur"]["formateur_id"] > 0) { ?>
                                        <li><a role="button" data-bs-toggle="modal" data-bs-target="#modalTrainees" class="dropdown-item" onclick="loadTrainees();">Actions sur les stagiaires</a></li>
                                        <li><a class="dropdown-item" href="//<?= $_SERVER["SERVER_NAME"] ?>/erp/public/">Retour sur l'ERP</a></li>
                                    <?php } elseif ($_SESSION["utilisateur"]["stagiaire_id"] > 0) { ?>
                                        <li><a class="dropdown-item" href="//<?= $_SERVER["SERVER_NAME"] ?>/erp/public/formation/acquired.php">Consulter mes acquis</a></li>
                                    <?php } ?>
                                    <li><a class="dropdown-item" href="//<?= $_SERVER["SERVER_NAME"] ?>/erp/public/deconnexion.php?url=<?= urlencode($_SERVER['REQUEST_URI']) ?>">Se déconnecter</a></li>
                                </ul>
                            </li>
                        </ul>
                    </div>
                </div>
            </nav>
            <div id="notifications" class="hidden">
                <h3>Notifications</h3>
                <hr>
                <div></div>
            </div>
        <?php } ?>