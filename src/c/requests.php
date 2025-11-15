<?php
include_once __DIR__ . '/../m/connect.php';

if (!empty($_POST["display_prompt_join_modal"])) {
    $sql_select_evaluation = "SELECT evaluation_id, evaluation_title, evaluation_goals, evaluation_synopsis 
                            FROM evaluations 
                            WHERE evaluation_id=:evaluation_id;";
    $req_select_evaluation = $db->prepare($sql_select_evaluation);
    $req_select_evaluation->bindParam(":evaluation_id", $_POST["evalId"]);
    $req_select_evaluation->execute();
    $modal = $req_select_evaluation->fetch(PDO::FETCH_ASSOC);
    $goals = "";
    $evaluation_goals = explode(";", $modal["evaluation_goals"]);
    foreach ($evaluation_goals as $goal) {
        $goals .= "- &nbsp;" . $goal . "<br>";
    }
    ob_start(); ?>
    <div class="modal-header">
        <h5 class="modal-title" id="modalJoinEvaluationTitle"><?= $modal["evaluation_title"] ?></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>
    <div class="modal-body">
        <h3>Compétences: </h3>
        <div class="container">
            <div class="row">
                <div class="col-6">
                    <?= $goals ?>
                </div>
                <div class="col-6">
                    <img class="svgs-sm" src="//<?= $_SERVER["SERVER_NAME"] ?>/erp/public/formation/imgs/join.svg" alt="Illustration pour l'intégration à l'évaluation">
                </div>
            </div>
        </div>
        <hr class="hrs">
        <h3>Marche à suivre:</h3>
        <p><?= $modal["evaluation_synopsis"] ?></p>
    </div>
    <div class="modal-footer">
        <button type="button" class="btn btn-warning" data-bs-dismiss="modal">Fermer</button>
        <button type="button" class="btn btn-success" data-bs-dismiss="modal" onclick="btnJoinEvaluation(<?= $modal['evaluation_id'] ?>);">Rejoindre</button>
    </div>
<?php
    die(ob_get_clean());
}

if (!empty($_POST["btn_join_evaluation"])) {
    $sql_insert_stagiaire = "INSERT INTO stagiaires_evaluations(stagiaire_evaluation_completed, id_stagiaire, id_evaluation) 
                        VALUES(0, :id_stagiaire, :id_evaluation);";
    $req_insert_stagiaire = $db->prepare($sql_insert_stagiaire);
    $req_insert_stagiaire->bindParam(":id_stagiaire", $_SESSION["utilisateur"]["stagiaire_id"]);
    $req_insert_stagiaire->bindParam(":id_evaluation", $_POST["evalId"]);
    if ($req_insert_stagiaire->execute()) {
        die("ok");
    }
    die("ko");
}

if (isset($_POST['form_filter_session'])) {
    $_SESSION['filtres']['session_id'] = filter_var($_POST['form_filter_session'], FILTER_VALIDATE_INT);
    die(json_encode(['success' => true, 'message' => "Filtre de session changé"]));
} elseif (!isset($_POST['form_filter_session']) && !isset($_SESSION['filtres']['session_id'])) {
    $_SESSION['filtres']['session_id'] = -1;
    die(json_encode(['success' => true, 'message' => "Session initialisée"]));
}

if (!empty($_POST["save_code"])) {
    $link = "../../public/formation/stagiaires/" . $_SESSION["utilisateur"]["stagiaire_pseudo"] . "/" . $_POST["module"];
    $file = $_POST["tp"] . "." . $_POST["extension"];
    if (!file_exists($link . "/" . $file)) {
        if (!mkdir($link, 0777, true)) {
            touch($link . "/" . $file);
        }
    }
    $eval = fopen($link . "/" . $file, "w") or die("ko");
    fwrite($eval, $_POST["code"]);
    fclose($eval);

    die("ok");
}

if (!empty($_POST["submit_evaluation"])) {
    $sql_update_stagiaire_evaluation = "UPDATE stagiaires_evaluations 
                                    SET stagiaire_evaluation_completed = 1 
                                    WHERE id_stagiaire=:id_stagiaire 
                                    AND id_evaluation=:id_evaluation;";
    $req_update_stagiaire_evaluation = $db->prepare($sql_update_stagiaire_evaluation);
    $req_update_stagiaire_evaluation->bindParam(":id_stagiaire", $_SESSION["utilisateur"]["stagiaire_id"]);
    $id_evaluation = substr($_POST["tp"], 2);
    $req_update_stagiaire_evaluation->bindParam(":id_evaluation", $id_evaluation);
    if ($req_update_stagiaire_evaluation->execute()) {
        die("ok");
    }
    die("ko");
}

if (!empty($_POST["load_informations_tp"])) {
    $sql_select_informations_tp = "SELECT evaluation_title, evaluation_synopsis
                                    FROM evaluations 
                                    WHERE evaluation_id=:evaluation_id;";
    $req_select_informations_tp = $db->prepare($sql_select_informations_tp);
    $req_select_informations_tp->bindParam(":evaluation_id", $_POST["tp"]);
    $req_select_informations_tp->execute();
    $informations_tp = $req_select_informations_tp->fetch(PDO::FETCH_ASSOC);

    die(json_encode(array(
        "title" => $informations_tp["evaluation_title"],
        "body" => $informations_tp["evaluation_synopsis"],
    )));
}

if (!empty($_POST["valid_stagiaire_correction"])) {
    $sql_update_correction = "UPDATE stagiaires_evaluations 
                                SET stagiaire_evaluation_correction = 1, 
                                stagiaire_evaluation_errors_found=:errors_found
                                WHERE id_stagiaire=:id_stagiaire 
                                AND id_evaluation=:id_evaluation;";
    $req_update_correction = $db->prepare($sql_update_correction);
    $req_update_correction->bindParam(":id_stagiaire", $_POST["id_stagiaire"]);
    $req_update_correction->bindParam(":errors_found", $_POST["errors_found"]);
    $req_update_correction->bindParam(":id_evaluation", $_POST["tp"]);
    if ($req_update_correction->execute()) {
        die("ok");
    }
    die("ko");
}

if (!empty($_POST["show_modal_manage_cours"])) {
    $sql = "SELECT * 
            FROM sessions 
            WHERE id_formateur=:id_formateur ";
    if (isset($_POST['id_session']) && $_POST['id_session'] > 0) {
        $sql .= " AND session_id=:id_session ";
    } elseif (isset($_POST['id_session']) && $_POST['id_session'] == "-1") { // Actives uniquement
        $sql .= " AND (session_date_fin>=NOW()) ";
    } else { // Toutes sessions confondues 
    }
    $sql .= " ORDER BY session_nom;";
    $req = $db->prepare($sql);
    $req->bindValue(':id_formateur', filter_var($_SESSION['utilisateur']['formateur_id'], FILTER_VALIDATE_INT));
    if (isset($_POST['id_session']) && $_POST['id_session'] > 0) {
        $req->bindValue(':id_session', filter_var($_POST['id_session'], FILTER_VALIDATE_INT));
    }
    $req->execute();
    $sessions = $req->fetchAll(PDO::FETCH_ASSOC);
    $req->closeCursor();

    ob_start(); ?>
    <div class="container-fluid">
        <?php if (!empty($sessions)) {
            foreach ($sessions as $session) {
                $sql_select_cours = "SELECT *
                                    FROM cours 
                                    JOIN cours_modules ON (cours_module_id = id_module) 
                                    JOIN sessions as s ON (s.session_id=:id_session) 
                                    LEFT JOIN cours_sessions ON (cours_id = id_cours AND cours_sessions.id_session=s.session_id) 
                                    WHERE 1 ";
                $sql_select_cours .= "  AND (cours_session_active=:cours_session_active ";
                if (isset($_POST['cours_actifs']) && $_POST['cours_actifs'] == "false") {
                    $sql_select_cours .= "      OR cours_session_active IS NULL ";
                }
                $sql_select_cours .= " ) ";
                $sql_count_cours = $sql_select_cours;
                if (!empty($_POST['search'])) {
                    $mots_cles = explode(" ", filter_var(trim(rtrim($_POST['search']), FILTER_SANITIZE_SPECIAL_CHARS)));
                    $conditions = array();
                    $conditions2 = array();
                    foreach ($mots_cles as $mot) {
                        $conditions[] = "cours_keywords LIKE '%" . $mot . "%'";
                        $conditions2[] = "cours_title LIKE '%" . $mot . "%'";
                    }
                    $sql_select_cours .= " AND " . implode(' AND ', $conditions);
                    $sql_select_cours .= " OR " . implode(' AND ', $conditions2);
                }
                $sql_select_cours .= "  ORDER BY cours_module_position, cours_position 
                                        -- LIMIT 10 -- OFFSET :offset
                                        ;";
                // var_dump($sql_select_cours);
                // var_dump(filter_var($_POST['cours_actifs'], FILTER_VALIDATE_BOOL));
                $req_select_cours = $db->prepare($sql_select_cours);
                $req_count_cours = $db->prepare($sql_count_cours);
                $req_select_cours->bindValue(':id_session', filter_var($session['session_id'], FILTER_VALIDATE_INT));
                $req_count_cours->bindValue(':id_session', filter_var($session['session_id'], FILTER_VALIDATE_INT));
                $req_select_cours->bindValue(':cours_session_active', filter_var($_POST['cours_actifs'], FILTER_VALIDATE_BOOL));
                $req_count_cours->bindValue(':cours_session_active', filter_var($_POST['cours_actifs'], FILTER_VALIDATE_BOOL));
                // $req_select_cours->bindValue(':offset', (isset($_POST['page']) ? filter_var($_POST['page'], FILTER_VALIDATE_INT) : 1) * 25);
                $req_count_cours->execute();
                $req_select_cours->execute();
                $cours = $req_select_cours->fetchAll(PDO::FETCH_ASSOC);
                $nbCours = sizeof($req_count_cours->fetchAll(PDO::FETCH_ASSOC));
                $req_select_cours->closeCursor(); ?>
                <div class="row">
                    <h2><?= $session['session_nom'] ?></h2><small>&nbsp;(<?= sizeof($cours) ?> cours trouvés sur <?= $nbCours ?>)</small>
                    <!-- <div class="col-12 d-flex">
                        <nav aria-label="Navigation session <?= $session['session_nom'] ?>">
                            <ul class="pagination">
                                <li class="page-item"><a class="page-link" href="#">Précédent</a></li>
                                <?php if (sizeof($cours) >= 1) for ($i = 1; $i <= $nbCours / 25; $i++) { ?>
                                    <li class="page-item"><a class="page-link" href="#"><?= $i ?></a></li>
                                <?php } ?>
                                <li class="page-item"><a class="page-link" href="#">Suivant</a></li>
                            </ul>
                        </nav>
                    </div> -->

                    <?php foreach ($cours as $cour) { ?>
                        <div class="col-3 pb-5" id="cours_<?= $cour['cours_id'] ?>_<?= $session['session_id'] ?>" onclick="<?= (!empty($_SESSION['mode_edition']) ? 'updateStatusCourse(' . $cour['cours_id'] . ',' . $session['session_id'] . ');' : 'alert(\'Mode édition désactivé !\');') ?>">
                            <span class="admin-manage-imgs" id="cours_<?= $cour['cours_id'] ?>">
                                <span class="<?= (empty($_SESSION['mode_edition']) ? "edition-off" : (!empty($cour['cours_session_active']) ? "cours-active" : "cours-inactive")) ?>">
                                    <?php if (file_exists("/erp/public/formation/imgs/" . $cour['cours_module_illustration'])) { ?>
                                        <img class="img-course" alt="Illustration <?= $cour["cours_module_libelle"] ?>" src="/erp/public/formation/imgs/<?= $cour['cours_module_illustration'] ?>" loading="lazy">
                                    <?php } else { ?>
                                        <img class="img-course" alt="Illustration non trouvée" src="/erp/public/formation/imgs/not_found_404.svg" loading="lazy">
                                    <?php } ?>
                                </span>
                            </span>
                            <p class="admin-manage-text"><strong>[<?= strtoupper($cour['cours_module_libelle']) ?>]</strong>&nbsp;<?= $cour['cours_title'] ?></p>
                        </div>
                    <?php } ?>
                </div>
            <?php }
        } else { ?>
            <div class="row">
                <p>Aucune session visible</p>
                <?php include_once("../../public/formation/imgs/under_construction.svg") ?>
            </div>
        <?php } ?>
    </div>
<?php
    die(ob_get_clean());
}

if (!empty($_POST["show_modal_manage_quiz"])) {
    $sql = "SELECT * 
            FROM sessions 
            WHERE id_formateur=:id_formateur ";
    if (!empty($_POST['id_session'])) {
        $sql .= " AND session_id=:id_session ";
    }
    $sql .= " ORDER BY nom_session;";
    $req = $db->prepare($sql);
    $req->bindValue(':id_formateur', filter_var($_SESSION['utilisateur']['formateur_id'], FILTER_VALIDATE_INT));
    if (!empty($_POST['id_session'])) {
        $req->bindValue(':id_session', filter_var($_POST['id_session'], FILTER_VALIDATE_INT));
    }
    $req->execute();
    $sessions = $req->fetchAll(PDO::FETCH_ASSOC);
    $req->closeCursor();

    ob_start(); ?>
    <div class="container-fluid">
        <?php if (!empty($sessions)) {
            foreach ($sessions as $session) {
                $sql_select_quiz = "SELECT *
                                    FROM quiz 
                                    LEFT JOIN quiz_sessions ON (quiz_id = id_quiz AND id_session=:id_session) 
                                    ORDER BY quiz_module;";
                $req_select_quiz = $db->prepare($sql_select_quiz);
                $req_select_quiz->bindValue(':id_session', filter_var($session['session_id'], FILTER_VALIDATE_INT));
                $req_select_quiz->execute();
                $quiz = $req_select_quiz->fetchAll(PDO::FETCH_ASSOC);
                $req_select_quiz->closeCursor(); ?>
                <div class="row">
                    <h2><?= $session['nom_session'] ?></h2>
                    <?php foreach ($quiz as $value) {
                        $difficulte = "Facile";
                        if ($value['quiz_difficulte'] == 3) {
                            $difficulte = "Extrême";
                        } elseif ($value['quiz_difficulte'] == 2) {
                            $difficulte = "Difficile";
                        } elseif ($value['quiz_difficulte'] == 1) {
                            $difficulte = "Modéré";
                        } ?>
                        <div class="col-3" id="quiz_<?= $value['quiz_id'] ?>_<?= $session['session_id'] ?>" onclick="updateStatusQuiz(<?= $value['quiz_id'] ?>, <?= $session['session_id'] ?>);">
                            <span class="admin-manage-imgs" id="quiz_<?= $value['quiz_id'] ?>">
                                <span class="<?= (!empty($value['quiz_session_active']) ? "quiz-active" : "quiz-inactive") ?>"><?php include("../../public/formation/imgs/video_game_night.svg") ?></span>
                            </span>
                            <p class="admin-manage-text"><strong>[<?= strtoupper($value['quiz_module']) ?>]</strong>&nbsp;<?= $difficulte ?></p>
                        </div>
                    <?php } ?>
                </div>
            <?php }
        } else { ?>
            <div class="row">
                <p>Aucune session assignée</p>
                <?php include_once("../../public/formation/imgs/under_construction.svg") ?>
            </div>
        <?php } ?>
    </div>
<?php
    die(ob_get_clean());
}

if (!empty($_POST["add_cours"])) { // TODO supprimer la colonne et intégrer la donnée de la table `cours_modules`
    $req = $db->prepare("INSERT INTO cours(cours_title, cours_synopsis, cours_text, cours_keywords, cours_link, cours_category, id_formateur)
                        VALUES(:cours_title, :cours_synopsis, :cours_text, :cours_keywords, :cours_link, :cours_category, :id_formateur) 
                        RETURNING cours_id;");
    $req->bindValue(':cours_title', filter_var($_POST['form_cours_title'], FILTER_SANITIZE_SPECIAL_CHARS));
    $req->bindValue(':cours_synopsis', filter_var($_POST['form_cours_synopsis'], FILTER_SANITIZE_SPECIAL_CHARS));
    $req->bindValue(':cours_text', filter_var($_POST['form_cours_text'], FILTER_SANITIZE_SPECIAL_CHARS));
    $req->bindValue(':cours_keywords', filter_var($_POST['form_cours_keywords'], FILTER_SANITIZE_SPECIAL_CHARS));
    $req->bindValue(':cours_link', filter_var($_POST['form_cours_link'], FILTER_SANITIZE_SPECIAL_CHARS));
    $req->bindValue(':cours_category', filter_var(strtolower($_POST['form_cours_module']), FILTER_SANITIZE_SPECIAL_CHARS));
    $req->bindValue(':id_formateur', filter_var($_SESSION['utilisateur']['formateur_id'], FILTER_VALIDATE_INT));
    if ($req->execute() && !empty($_POST['form_cours_active'])) {
        $id_cours = $req->fetch(PDO::FETCH_ASSOC)['cours_id'];
        $req = $db->prepare("SELECT session_id FROM sessions WHERE id_formateur=:id_formateur;");
        $req->bindValue(':id_formateur', filter_var($_SESSION['utilisateur']['formateur_id'], FILTER_VALIDATE_INT));
        $req->execute();
        $sessions = $req->fetchAll(PDO::FETCH_COLUMN);
        foreach ($sessions as $session) {
            $req = $db->prepare("REPLACE INTO cours_sessions(id_session, id_cours, cours_session_active)
                                VALUES(:id_session, :id_cours, :cours_session_active);");
            $req->bindValue(':id_session', filter_var($session, FILTER_VALIDATE_INT));
            $req->bindValue(':id_cours', filter_var($id_cours, FILTER_VALIDATE_INT));
            $req->bindValue(':cours_session_active', filter_var($_POST['form_cours_active'], FILTER_VALIDATE_BOOL));
            $req->execute();
        }
    }
    $req->closeCursor();
    die("ok");
}

if (!empty($_POST["add_quiz"])) {
    $req = $db->prepare("INSERT INTO quiz(quiz_module, quiz_lien, quiz_difficulte, id_formateur)
                        VALUES(:quiz_module, :quiz_lien, :quiz_difficulte, :id_formateur) 
                        RETURNING quiz_id;");
    $req->bindValue(':quiz_module', filter_var($_POST['form_quiz_module'], FILTER_SANITIZE_SPECIAL_CHARS));
    $req->bindValue(':quiz_lien', filter_var($_POST['form_quiz_lien'], FILTER_SANITIZE_SPECIAL_CHARS));
    $req->bindValue(':quiz_difficulte', filter_var($_POST['form_quiz_difficulte'], FILTER_VALIDATE_INT));
    $req->bindValue(':id_formateur', filter_var($_SESSION['utilisateur']['formateur_id'], FILTER_VALIDATE_INT));
    if ($req->execute() && !empty($_POST['form_quiz_active'])) {
        $id_quiz = $req->fetch(PDO::FETCH_ASSOC)['quiz_id'];
        $req = $db->prepare("SELECT session_id FROM sessions WHERE id_formateur=:id_formateur;");
        $req->bindValue(':id_formateur', filter_var($_SESSION['utilisateur']['formateur_id'], FILTER_VALIDATE_INT));
        $req->execute();
        $sessions = $req->fetchAll(PDO::FETCH_COLUMN);
        foreach ($sessions as $session) {
            $req = $db->prepare("REPLACE INTO quiz_sessions(id_session, id_quiz, quiz_session_active)
                                VALUES(:id_session, :id_quiz, :quiz_session_active);");
            $req->bindValue(':id_session', filter_var($session, FILTER_VALIDATE_INT));
            $req->bindValue(':id_quiz', filter_var($id_quiz, FILTER_VALIDATE_INT));
            $req->bindValue(':quiz_session_active', filter_var($_POST['form_quiz_active'], FILTER_VALIDATE_BOOL));
            $req->execute();
        }
    }
    $req->closeCursor();
    header("Location: ../../public/formation/admin.php");
}

if (!empty($_POST["update_status_cours"])) {
    $sql_update_cours = "REPLACE INTO cours_sessions(id_session, id_cours, cours_session_active) 
                        VALUES(:id_session, :id_cours, :cours_session_active);";
    $req_update_cours = $db->prepare($sql_update_cours);
    $req_update_cours->bindParam(":cours_session_active", $_POST["cours_active"]);
    $req_update_cours->bindParam(":id_cours", $_POST["cours_id"]);
    $req_update_cours->bindParam(":id_session", $_POST["id_session"]);
    if ($req_update_cours->execute()) {
        $req_select_stagiaires_session = $db->prepare("SELECT stagiaire_id 
                                                       FROM stagiaires 
                                                       WHERE id_session=:id_session;");
        $req_select_stagiaires_session->bindValue(":id_session", filter_var($_POST['id_session'], FILTER_VALIDATE_INT));
        $req_select_stagiaires_session->execute();
        $ids_stagiaires = $req_select_stagiaires_session->fetchAll(PDO::FETCH_COLUMN);
        $req_select_stagiaires_session->closeCursor();

        $req_select_cours = $db->prepare("SELECT cours_title, cours_module_libelle, cours_link
                                        FROM cours 
                                        JOIN cours_modules ON (cours_module_id = id_module) 
                                        WHERE cours_id=:id_cours;");
        $req_select_cours->bindValue(":id_cours", filter_var($_POST["cours_id"], FILTER_VALIDATE_INT));
        $req_select_cours->execute();
        $cours = $req_select_cours->fetch(PDO::FETCH_ASSOC);
        $req_select_cours->closeCursor();
        if ($_POST['cours_active'] == 1) {
            foreach ($ids_stagiaires as $id_stagiaire) {
                $req = $db->prepare("INSERT INTO notifications(notification_titre, notification_lien, notification_date, id_stagiaire) 
                                    VALUES(:notification_titre, :notification_lien, NOW(), :id_stagiaire);");
                $req->bindValue(':notification_titre', "[" . strtoupper($cours['cours_module_libelle']) . "] Nouveau cours disponible: " . $cours['cours_title']);
                $req->bindValue(':notification_lien', LIEN_FORMATION . "embed.php?slide=" . $cours['cours_link']);
                $req->bindValue(':id_stagiaire', $id_stagiaire);
                $req->execute();
                $req->closeCursor();
            }
        } else {
            foreach ($ids_stagiaires as $id_stagiaire) {
                $req = $db->prepare("DELETE FROM notifications 
                                    WHERE notification_lien=:notification_lien 
                                    AND id_stagiaire=:id_stagiaire;");
                $req->bindValue(':notification_lien', LIEN_FORMATION . "embed.php?slide=" . $cours['cours_link']);
                $req->bindValue(':id_stagiaire', $id_stagiaire);
                $req->execute();
                $req->closeCursor();
            }
        }
        die("ok");
    }
    die("ko");
}

if (!empty($_POST["update_status_quiz"])) {
    $sql_update_quiz = "REPLACE INTO quiz_sessions(id_session, id_quiz, quiz_session_active) 
                        VALUES(:id_session, :id_quiz, :quiz_session_active);";
    $req_update_quiz = $db->prepare($sql_update_quiz);
    $req_update_quiz->bindParam(":id_session", $_POST["id_session"]);
    $req_update_quiz->bindParam(":id_quiz", $_POST["quiz_id"]);
    $req_update_quiz->bindParam(":quiz_session_active", $_POST["quiz_active"]);
    if ($req_update_quiz->execute()) {
        $req_select_stagiaires_session = $db->prepare("SELECT stagiaire_id 
                                                       FROM stagiaires 
                                                       WHERE id_session=:id_session;");
        $req_select_stagiaires_session->bindValue(":id_session", filter_var($_POST['id_session'], FILTER_VALIDATE_INT));
        $req_select_stagiaires_session->execute();
        $ids_stagiaires = $req_select_stagiaires_session->fetchAll(PDO::FETCH_COLUMN);
        $req_select_stagiaires_session->closeCursor();

        $req_select_quiz = $db->prepare("SELECT quiz_module, quiz_lien, quiz_difficulte
                                        FROM quiz 
                                        WHERE quiz_id=:id_quiz;");
        $req_select_quiz->bindValue(":id_quiz", filter_var($_POST["quiz_id"], FILTER_VALIDATE_INT));
        $req_select_quiz->execute();
        $quiz = $req_select_quiz->fetch(PDO::FETCH_ASSOC);
        $req_select_quiz->closeCursor();
        if ($_POST['quiz_active'] == "1") {
            foreach ($ids_stagiaires as $id_stagiaire) {
                $req = $db->prepare("INSERT INTO notifications(notification_titre, notification_lien, notification_date, id_stagiaire) 
                                    VALUES(:notification_titre, :notification_lien, NOW(), :id_stagiaire);");
                $req->bindValue(':notification_titre', "[" . strtoupper($quiz['quiz_module']) . "] Nouveau quiz disponible: " . $quiz['quiz_module'] . " - " . $quiz['quiz_difficulte'] . "/3");
                $req->bindValue(':notification_lien', "https://quizizz.com/join?gc=" . $quiz['quiz_lien']);
                $req->bindValue(':id_stagiaire', $id_stagiaire);
                $req->execute();
                $req->closeCursor();
            }
        } else {
            foreach ($ids_stagiaires as $id_stagiaire) {
                $req = $db->prepare("DELETE FROM notifications 
                                    WHERE notification_lien=:notification_lien 
                                    AND id_stagiaire=:id_stagiaire;");
                $req->bindValue(':notification_lien', "https://quizizz.com/join?gc=" . $quiz['quiz_lien']);
                $req->bindValue(':id_stagiaire', $id_stagiaire);
                $req->execute();
                $req->closeCursor();
            }
        }
        die("ok");
    }
    die("ko");
}

if (!empty($_POST["fetch_quiz_data"])) {
    $sql_select_quiz = "SELECT * 
                        FROM quiz 
                        LEFT JOIN quiz_sessions ON (quiz_id = id_quiz AND id_session=:id_session) 
                        WHERE quiz_id=:quiz_id;";
    $req_select_quiz = $db->prepare($sql_select_quiz);
    $req_select_quiz->bindValue(":quiz_id", filter_var($_POST['quiz_id'], FILTER_VALIDATE_INT));
    $req_select_quiz->bindValue(":id_session", filter_var($_SESSION['utilisateur']['id_session'], FILTER_VALIDATE_INT));
    $req_select_quiz->execute();
    $quiz = $req_select_quiz->fetch(PDO::FETCH_ASSOC);

    ob_start(); ?>
    <div class="modal-header">
        <h5 class="modal-title" id="modalDisplayQuizTitle">Quiz</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>
    <div class="modal-body">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <h6><?= strtoupper($quiz['quiz_module']) ?></h6>
                </div>
            </div>
            <div class="row">
                <div style="width:100%;display:flex;flex-direction:column;gap:8px;min-height:635px;"><iframe src="https://quizizz.com/join?gc=<?= $quiz['quiz_lien'] ?>" title="Test - Quizizz" style="flex:1;" frameBorder="0" allowfullscreen></iframe></div>
            </div>
        </div>
    </div>
<?php
    die(ob_get_clean());
}

if (!empty($_POST["form_ressource_add"])) {
    $req = $db->prepare("SELECT cours_id FROM cours WHERE cours_link=:cours_link;");
    $req->bindValue(':cours_link', filter_var($_POST['form_ressource_cours_id'], FILTER_SANITIZE_SPECIAL_CHARS));
    $req->execute();
    $id_cours = $req->fetch(PDO::FETCH_COLUMN);

    $ressource_lien = NULL;
    if (isset($_POST['form_ressource_lien']) && !empty($_POST['form_ressource_lien'])) {
        $ressource_lien = filter_var($_POST['form_ressource_lien'], FILTER_SANITIZE_SPECIAL_CHARS);
    }
    $archive = NULL;
    $archive_lien = NULL;
    if (isset($_FILES['form_ressource_file']) && !empty($_FILES['form_ressource_file'])) {
        $extensions_ok = array("gz", "tar", "zip", "rar");
        $extension = substr(strrchr($_FILES['form_ressource_file']['name'], '.'), 1);
        if (in_array($extension, $extensions_ok)) {
            $pseudo_formateur = strtolower(substr($_SESSION['utilisateur']['formateur_prenom'], 0, 1) . $_SESSION['utilisateur']['formateur_nom']);
            $lien = "../v/formateurs/" . $pseudo_formateur;
            $archive = uniqid("_") . "_ressource_cours_" . $id_cours . "." . $extension;
            $archive_lien = sha1($archive);
            if (!is_dir($lien)) {
                mkdir($lien, 0777, true);
            }
            move_uploaded_file($_FILES['form_ressource_file']['tmp_name'], $lien . "/" . $archive);
        }
    }
    $req = $db->prepare("INSERT INTO cours_ressources(id_cours, cours_ressource_type, cours_ressource_titre, cours_ressource_resume, cours_ressource_lien, cours_ressource_archive, cours_ressource_archive_lien) 
                        VALUES(:id_cours, :cours_ressource_type, :cours_ressource_titre, :cours_ressource_resume, :cours_ressource_lien, :cours_ressource_archive, :cours_ressource_archive_lien);");
    $req->bindValue(':id_cours', filter_var($id_cours, FILTER_VALIDATE_INT));
    $req->bindValue(':cours_ressource_type', filter_var($_POST['form_ressource_type'], FILTER_SANITIZE_SPECIAL_CHARS));
    $req->bindValue(':cours_ressource_titre', filter_var($_POST['form_ressource_titre'], FILTER_SANITIZE_SPECIAL_CHARS));
    $req->bindValue(':cours_ressource_resume', filter_var($_POST['form_ressource_synopsis'], FILTER_SANITIZE_SPECIAL_CHARS));
    $req->bindValue(':cours_ressource_lien', $ressource_lien);
    $req->bindValue(':cours_ressource_archive', $archive);
    $req->bindValue(':cours_ressource_archive_lien', $archive_lien);
    if ($req->execute()) {
        $req_select_stagiaires_session = $db->prepare("SELECT s.stagiaire_id 
                                                       FROM stagiaires s
                                                       JOIN cours_sessions c ON (s.id_session = c.id_session AND id_cours=:id_cours) 
                                                       WHERE s.id_session=:id_session;");
        $req_select_stagiaires_session->bindValue(":id_session", filter_var($_SESSION['utilisateur']['id_session'], FILTER_VALIDATE_INT));
        $req_select_stagiaires_session->bindValue(":id_cours", filter_var($id_cours, FILTER_VALIDATE_INT));
        $req_select_stagiaires_session->execute();
        $ids_stagiaires = $req_select_stagiaires_session->fetchAll(PDO::FETCH_COLUMN);
        $req_select_stagiaires_session->closeCursor();

        $req_select_cours = $db->prepare("SELECT cours_title, cours_module_libelle, cours_link
                                        FROM cours 
                                        JOIN cours_modules ON (cours_module_id = id_module) 
                                        WHERE cours_id=:id_cours;");
        $req_select_cours->bindValue(":id_cours", filter_var($id_cours, FILTER_VALIDATE_INT));
        $req_select_cours->execute();
        $cours = $req_select_cours->fetch(PDO::FETCH_ASSOC);
        $req_select_cours->closeCursor();
        foreach ($ids_stagiaires as $id_stagiaire) {
            $req = $db->prepare("INSERT INTO notifications(notification_titre, notification_lien, notification_date, id_stagiaire) 
                                VALUES(:notification_titre, :notification_lien, NOW(), :id_stagiaire);");
            $req->bindValue(':notification_titre', "[" . strtoupper($cours['cours_module_libelle']) . "] Nouvelle ressource disponible: " . $cours['cours_title'] . " - " . $cours['cours_module_libelle']);
            $req->bindValue(':notification_lien', $cours['cours_link']);
            $req->bindValue(':id_stagiaire', $id_stagiaire);
            $req->execute();
            $req->closeCursor();
        }
    }
    header('Location: /erp/public/formation/embed.php?slide=' . $_POST['form_ressource_cours_id']);
}

if (!empty($_POST["load_new_notifications"])) {
    $notifications = "";
    $notifications_count = 0;
    if ($_SESSION['utilisateur']['stagiaire_id'] > 0) {
        $req = $db->prepare("SELECT notification_titre, notification_date, notification_lien 
                            FROM notifications 
                            WHERE id_stagiaire=:id_stagiaire 
                            ORDER BY notification_date DESC;");
        $req->bindValue(":id_stagiaire", filter_var($_SESSION['utilisateur']['stagiaire_id'], FILTER_VALIDATE_INT));
        $req->execute();
        $result = $req->fetchAll(PDO::FETCH_ASSOC);
        if (!empty($result)) {
            $notifications .= "<button onclick=\"deleteNotification();\"><i class=\"fa-solid fa-trash text-grey\"></i></button>";
            foreach ($result as $notification) {
                $notifications .= "<a onclick=\"deleteNotification('" . $notification['notification_lien'] . "');\"" . (substr($notification['notification_lien'], 0, 5) == "https" ? 'target="_blank"' : '') . " href=\"" . $notification['notification_lien'] . "\"><span>" . $notification['notification_titre'] . "</span><em class=\"d-block\">Le " . date_format(new DateTimeImmutable($notification['notification_date']), "d/m/y à H:i:s") . "</em></a>";
            }
        } else {
            $notifications = "Aucune notification pour le moment";
        }
        $notifications_count = $req->rowCount();
        $req->closeCursor();
    }

    die(json_encode(array(
        "notifications" => $notifications,
        "notifications_count" => $notifications_count
    )));
}

if (isset($_POST['delete_notification'])) {
    $sql = "DELETE FROM notifications 
            WHERE id_stagiaire=:id_stagiaire";
    if (isset($_POST['lien_notification']) && !empty($_POST['lien_notification'])) {
        $sql .= " AND notification_lien=:notification_lien";
    }
    $sql .= ";";
    $req = $db->prepare($sql);
    $req->bindValue(":id_stagiaire", filter_var($_SESSION['utilisateur']['stagiaire_id'], FILTER_VALIDATE_INT));
    if (isset($_POST['lien_notification']) && !empty($_POST['lien_notification'])) {
        $req->bindValue(":notification_lien", filter_var($_POST['lien_notification'], FILTER_SANITIZE_SPECIAL_CHARS));
    }
    die(json_encode($req->execute()));
}

if (isset($_POST['send_tp']) && isset($_FILES['fichier']) && !empty($_FILES['fichier'])) {
    $tp_id = filter_var($_POST['tp_id'], FILTER_VALIDATE_INT);
    $lien_ressource_rendue = filter_var("ressource_" . $tp_id . "_" . $_FILES['fichier']['name'], FILTER_SANITIZE_SPECIAL_CHARS);
    if (!is_dir("../../public/formation/stagiaires/" . $_SESSION['utilisateur']['stagiaire_pseudo'] . "/tps/")) {
        mkdir("../../public/formation/stagiaires/" . $_SESSION['utilisateur']['stagiaire_pseudo'] . "/tps/", 0777, true);
    }
    try {
        $fileMoved = move_uploaded_file($_FILES['fichier']['tmp_name'], "../../public/formation/stagiaires/" . $_SESSION['utilisateur']['stagiaire_pseudo'] . "/tps/" . $lien_ressource_rendue);
    } catch (Exception $e) {
        die(json_encode(array("success" => false, "message" => $e->getMessage())));
    }

    if ($fileMoved) {
        $req = $db->prepare("REPLACE INTO stagiaires_ressources(id_stagiaire, id_ressource, stagiaire_ressource_rendue_lien) 
                            VALUES(:id_stagiaire, :id_ressource, :lien_ressource_rendue);");
        $req->bindValue(":id_stagiaire", filter_var($_SESSION['utilisateur']['stagiaire_id'], FILTER_VALIDATE_INT));
        $req->bindValue(":id_ressource", $tp_id);
        $req->bindValue(":lien_ressource_rendue", $lien_ressource_rendue);
        die(json_encode($req->execute()));
    }
    die(json_encode(array("success" => false, "message" => "Une erreur s'est produite, veuillez réessayer...")));
}

if (isset($_POST['get_modules']) && !empty($_POST['get_modules'])) {
    $success = true;

    if ($_SESSION['utilisateur']['formateur_id'] > 0) {
        $sql = "SELECT formateur_id FROM formateurs ";
        if (!empty($_SESSION['utilisateur']['id_secteur'])) {
            $sql .= " WHERE id_secteur=:id_secteur";
        }
        $sql .= ";";
        $req = $db->prepare($sql);
        if (!empty($_SESSION['utilisateur']['id_secteur'])) {
            $req->bindValue(':id_secteur', filter_var($_SESSION['utilisateur']['id_secteur'], FILTER_VALIDATE_INT));
        }
        $req->execute();
        $ids_formateurs = $req->fetchAll(PDO::FETCH_COLUMN);
        $req->closeCursor();

        $sql = "SELECT * 
                FROM cours 
                JOIN formateurs ON (formateurs.formateur_id = cours.id_formateur) 
                JOIN cours_modules ON (cours_module_id = id_module) ";
        if (isset($_SESSION['filtres']['session_id']) && !empty($_SESSION['filtres']['session_id']) && $_SESSION['filtres']['session_id'] > 0) {
            $sql .= " JOIN cours_sessions ON (cours_id = id_cours AND id_session=:id_session AND cours_session_active = 1)  ";
        }
        $sql .= " WHERE formateurs.formateur_id IN(" . implode(", ", $ids_formateurs) . ") ";
        if (isset($_POST['recherche']) && !empty($_POST['recherche'])) {
            $mots_cles = explode(" ", filter_var(trim($_POST['recherche'], FILTER_SANITIZE_SPECIAL_CHARS)));
            $conditions = array();
            foreach ($mots_cles as $mot) {
                $conditions[] = "cours_keywords LIKE '%" . $mot . "%'";
            }
            $sql .= " AND " . implode(' AND ', $conditions);
        }
        $sql .= " GROUP BY id_module 
        ORDER BY cours_module_position;";
        $req = $db->prepare($sql);
        if (isset($_SESSION['filtres']['session_id']) && !empty($_SESSION['filtres']['session_id']) && $_SESSION['filtres']['session_id'] > 0) {
            $req->bindValue(":id_session", $_SESSION['filtres']['session_id'], PDO::PARAM_INT);
        }
        $req->execute();
        $modules = $req->fetchAll(PDO::FETCH_ASSOC);
        $req->closeCursor();
    } else {
        $sql = "SELECT * 
                FROM cours 
                JOIN cours_sessions ON (cours_id = id_cours AND id_session=:id_session AND cours_session_active = 1) 
                JOIN cours_modules ON (cours_module_id = id_module) 
                WHERE 1 ";
        if (isset($_POST['recherche']) && !empty($_POST['recherche'])) {
            $mots_cles = explode(" ", filter_var(trim($_POST['recherche'], FILTER_SANITIZE_SPECIAL_CHARS)));
            $conditions = array();
            foreach ($mots_cles as $mot) {
                $conditions[] = "cours_keywords LIKE '%" . $mot . "%'";
            }
            $sql .= " AND " . implode(' AND ', $conditions);
        }
        $sql .= " GROUP BY id_module  
                ORDER BY cours_module_position;";
        $req = $db->prepare($sql);
        $req->bindValue(':id_session', filter_var($_SESSION['utilisateur']['id_session'], FILTER_VALIDATE_INT));
        $req->execute();
        $modules = $req->fetchAll(PDO::FETCH_ASSOC);
        $req->closeCursor();
    }

    $liste_modules = "";
    if ($_SESSION['utilisateur']['formateur_id'] > 0 && !empty($_SESSION['mode_edition'])) {
        $liste_modules .= '<div class="col-xs-1 col-md-3 col-lg-3 mb-3">
            <div class="card">
                <span class="card-img-top" alt="Illustration d\'un nouveau module">
                    <label for="form_add_module">Choisir l\'image
                        <input type="file" id="form_add_module">
                    </label>
                </span>
                <div class="card-body" style="padding-bottom: 0.6rem;">
                    <p class="card-title h5 text-decoration-underline"><input type="text" placeholder="Ajoutez le nom du module"></p>
                </div>
            </div>
        </div>';
    }
    if (!empty($modules)) {
        foreach ($modules as $module) {
            $liste_modules .= '<div class="col-xs-1 col-md-3 col-lg-3 mb-3">
                <a href="//' . $_SERVER["SERVER_NAME"] . '/erp/public/formation/cours.php?cours=' . $module['cours_module_uuid'] . (isset($_POST['recherche']) && !empty($_POST['recherche']) ? '&q=' . $_POST['recherche'] : '') . '" class="text-black">
                    <div class="card">
                        <span class="card-img-top">
                            <img class="img-course" alt="Illustration ' . $module["cours_module_libelle"] . '" src="/erp/public/formation/imgs/' . (file_exists('/var/www/html/erp/public/formation/imgs/' . $module['cours_module_illustration']) ? $module['cours_module_illustration'] : 'no_data.svg') . '" loading="lazy" >
                        </span>
                        <div class="card-body">
                            <p class="card-title h5 text-decoration-underline">' . strtoupper($module['cours_module_libelle']) . '</p>
                        </div>
                    </div>
                </a>
            </div>';
        }
    } else {
        $liste_modules = "<p>Vous n'avez pas de cours dans votre secteur</p>";
    }

    die(json_encode(array(
        'success' => $success,
        'modules' => $liste_modules
    )));
}

if (isset($_POST['get_list_courses']) && !empty($_POST['get_list_courses'])) {
    $tbody = "";
    $sql = "SELECT * 
            FROM cours 
            INNER JOIN cours_modules ON (cours_module_id = id_module) 
            INNER JOIN formateurs ON (formateur_id = id_formateur) 
            ORDER BY cours_module_position, cours_position;";
    $req = $db->prepare($sql);
    $req->execute();
    $cours = $req->fetchAll(PDO::FETCH_ASSOC);
    if (!empty($cours)) {
        foreach ($cours as $unCours) {
            $tbody .= '<tr>';
            $tbody .= ' <th scope="row">' . $unCours['cours_module_libelle'] . '</th>';
            $tbody .= ' <td>' . $unCours['cours_title'] . '</td>';
            $tbody .= ' <td>' . $unCours['cours_synopsis'] . '</td>';
            $tbody .= ' <td>' . $unCours['cours_keywords'] . '</td>';
            $tbody .= ' <td>' . $unCours['cours_position'] . '</td>';
            $tbody .= ' <td>' . $unCours['formateur_nom'] . "&nbsp;" . $unCours['formateur_prenom'] . '</td>';
            $tbody .= '</tr>';
        }
    } else {
        $tbody = '<tr>';
        $tbody .= ' <th scope="row" colspan="6">Aucun cours n\'a été trouvé !</th>';
        $tbody .= ' <td class="d-none"></td>';
        $tbody .= ' <td class="d-none"></td>';
        $tbody .= ' <td class="d-none"></td>';
        $tbody .= ' <td class="d-none"></td>';
        $tbody .= ' <td class="d-none"></td>';
        $tbody .= '</tr>';
    }
    die($tbody);
}

if (isset($_POST['get_list_trainers']) && !empty($_POST['get_list_trainers'])) {
    $tbody = "";
    $sql = "SELECT * 
            FROM formateurs  
            INNER JOIN sites ON (site_id = id_site) 
            INNER JOIN secteurs ON (secteur_id = id_secteur) 
            ORDER BY formateur_nom, formateur_prenom;";
    $req = $db->prepare($sql);
    $req->execute();
    $formateurs = $req->fetchAll(PDO::FETCH_ASSOC);
    if (!empty($formateurs)) {
        foreach ($formateurs as $unFormateur) {
            $tbody .= '<tr>';
            $tbody .= ' <th scope="row">' . $unFormateur['formateur_nom'] . '</th>';
            $tbody .= ' <td>' . $unFormateur['formateur_prenom'] . '</td>';
            $tbody .= ' <td>' . $unFormateur['formateur_mail'] . '</td>';
            $tbody .= ' <td>' . $unFormateur['carte_formateur_role'] . '</td>';
            $tbody .= ' <td>' . $unFormateur['carte_formateur_liens'] . '</td>';
            $tbody .= ' <td>' . $unFormateur['carte_formateur_tel'] . '</td>';
            $tbody .= ' <td>' . $unFormateur['site_libelle'] . '</td>';
            $tbody .= ' <td>' . $unFormateur['secteur_nom'] . '</td>';
            $tbody .= '</tr>';
        }
    } else {
        $tbody = '<tr>';
        $tbody .= ' <th scope="row" colspan="8">Aucun formateur n\'a été trouvé !</th>';
        $tbody .= ' <td class="d-none"></td>';
        $tbody .= ' <td class="d-none"></td>';
        $tbody .= ' <td class="d-none"></td>';
        $tbody .= ' <td class="d-none"></td>';
        $tbody .= ' <td class="d-none"></td>';
        $tbody .= ' <td class="d-none"></td>';
        $tbody .= ' <td class="d-none"></td>';
        $tbody .= '</tr>';
    }
    die($tbody);
}

if (isset($_POST['get_list_sessions']) && !empty($_POST['get_list_sessions'])) {
    $tbody = "";
    $sql = "SELECT * 
            FROM sessions 
            INNER JOIN formateurs ON (formateur_id=id_formateur) ";
    if (isset($_POST['id_session']) && !empty($_POST['id_session']) && $_POST['id_session'] == "-1") {
        $sql .= "INNER JOIN secteurs ON (secteur_id = id_secteur) ";
    } elseif (isset($_POST['id_session']) && empty($_POST['id_session'])) {
        $sql .= " WHERE formateur_id=:id_formateur ";
    } elseif (isset($_POST['id_session']) && !empty($_POST['id_session']) && $_POST['id_session'] > 0) {
        $sql .= " WHERE session_id=:id_session ";
    }

    $sql .= "ORDER BY session_date_debut DESC, session_nom;";
    $req = $db->prepare($sql);
    if (isset($_POST['id_session']) && empty($_POST['id_session'])) {
        $req->bindValue(':id_formateur', filter_var($_SESSION['utilisateur']['formateur_id'], FILTER_VALIDATE_INT), PDO::PARAM_INT);
    } elseif (isset($_POST['id_session']) && !empty($_POST['id_session']) && $_POST['id_session'] > 0) {
        $req->bindValue(':id_session', filter_var($_POST['id_session'], FILTER_VALIDATE_INT), PDO::PARAM_INT);
    }
    $req->execute();
    $sessions = $req->fetchAll(PDO::FETCH_ASSOC);
    if (!empty($sessions)) {
        foreach ($sessions as $uneSession) {
            $tbody .= '<tr>';
            $tbody .= ' <th scope="row">' . $uneSession['session_nom'] . '</th>';
            $tbody .= ' <td>' . $uneSession['session_sigle'] . '</td>';
            $tbody .= ' <td>' . date('d/m/Y', strtotime($uneSession['session_date_debut'])) . '</td>';
            $tbody .= ' <td>' . date('d/m/Y', strtotime($uneSession['session_date_fin'])) . '</td>';
            $tbody .= ' <td class="text-center"><img style="height:15vh;width:auto;" src="./imgs/blasons/' . (!empty($uneSession['session_blason']) ? $uneSession['session_blason'] : "#") . '" alt="Blason de la session ' . $uneSession['session_nom'] . '"></td>';
            $tbody .= ' <td>' . $uneSession['formateur_nom'] . "&nbsp;" . $uneSession['formateur_prenom'] . '</td>';
            $tbody .= ' <td class="text-center"><button role="button" class="btn btn-info text-white" data-bs-toggle="modal" data-bs-target="#modalInternshipPeriod" onclick="getListIntershipsPeriods(' . $uneSession['session_id'] . ');">Période de stage</button></td>';
            $tbody .= '</tr>';
        }
    } else {
        $tbody = '<tr>';
        $tbody .= ' <th scope="row" colspan="7">Aucune session n\'a été trouvée !</th>';
        $tbody .= ' <td class="d-none"></td>';
        $tbody .= ' <td class="d-none"></td>';
        $tbody .= ' <td class="d-none"></td>';
        $tbody .= ' <td class="d-none"></td>';
        $tbody .= ' <td class="d-none"></td>';
        $tbody .= ' <td class="d-none"></td>';
        $tbody .= '</tr>';
    }
    die($tbody);
}

if (isset($_POST['get_list_trainees']) && !empty($_POST['get_list_trainees'])) {
    $tbody = "";
    $sql = "SELECT * 
            FROM stagiaires ";
    if (isset($_POST['id_session']) && !empty($_POST['id_session']) && $_POST['id_session'] > 0) {
        $sql .= " INNER JOIN sessions ON (session_id=id_session AND session_id=:id_session) ";
    } elseif (isset($_POST['id_session']) && empty($_POST['id_session'])) {
        $sql .= " INNER JOIN sessions ON (session_id=id_session) ";
        $sql .= " INNER JOIN formateurs ON (formateur_id = id_formateur) ";
    } elseif (isset($_POST['id_session']) && !empty($_POST['id_session']) && $_POST['id_session'] == "-1") {
        $sql .= " INNER JOIN sessions ON (session_id = id_session) ";
        $sql .= " INNER JOIN formateurs ON (formateur_id = id_formateur) ";
        $sql .= "INNER JOIN secteurs ON (secteur_id = id_secteur) ";
    }
    $sql .= " 
            LEFT JOIN stages ON (stage_id = id_stage) 
            WHERE stagiaire_actif = 1 
            ORDER BY stagiaire_nom, stagiaire_prenom;";
    $req = $db->prepare($sql);
    if (isset($_POST['id_session']) && !empty($_POST['id_session']) && $_POST['id_session'] > 0) {
        $req->bindValue(':id_session', filter_var($_POST['id_session'], FILTER_VALIDATE_INT), PDO::PARAM_INT);
    }
    $req->execute();
    $stagiaires = $req->fetchAll(PDO::FETCH_ASSOC);
    if (!empty($stagiaires)) {
        foreach ($stagiaires as $unStagiaire) {
            $tbody .= '<tr>';
            $tbody .= ' <th scope="row">' . $unStagiaire['stagiaire_nom'] . '</th>';
            $tbody .= ' <td>' . $unStagiaire['stagiaire_prenom'] . '</td>';
            $tbody .= ' <td>' . $unStagiaire['stagiaire_mail'] . '</td>';
            $tbody .= ' <td>' . $unStagiaire['stagiaire_pseudo'] . '</td>';
            $tbody .= ' <td>' . $unStagiaire['stagiaire_tel'] . '</td>';
            $tbody .= ' <td>' . date('d/m/Y', strtotime($unStagiaire['stagiaire_date_naissance'])) . '</td>';
            $tbody .= ' <td><a href="#sessions">' . $unStagiaire['session_nom'] . '</a></td>';
            $tbody .= ' <td><a href="#stages">' . $unStagiaire['stage_nom'] . '</a></td>';
            $tbody .= '</tr>';
        }
    } else {
        $tbody = '<tr>';
        $tbody .= ' <th scope="row" colspan="8">Aucun·e stagiaire n\'a été trouvé·e !</th>';
        $tbody .= ' <td class="d-none"></td>';
        $tbody .= ' <td class="d-none"></td>';
        $tbody .= ' <td class="d-none"></td>';
        $tbody .= ' <td class="d-none"></td>';
        $tbody .= ' <td class="d-none"></td>';
        $tbody .= ' <td class="d-none"></td>';
        $tbody .= ' <td class="d-none"></td>';
        $tbody .= '</tr>';
    }
    die($tbody);
}

if (isset($_POST['get_list_exercises']) && !empty($_POST['get_list_exercises'])) {
    $tbody = "";
    $sql = "SELECT cours_module_libelle, cours_title, cours_ressource_titre, stagiaire_prenom, stagiaire_nom, stagiaire_pseudo, stagiaire_ressource_rendue_lien, session_nom 
            FROM stagiaires 
            INNER JOIN sessions ON (session_id=id_session) ";
    if (isset($_POST['id_session']) && empty($_POST['id_session'])) {
        $sql .= " INNER JOIN formateurs ON (formateur_id = id_formateur) ";
    } elseif (isset($_POST['id_session']) && !empty($_POST['id_session']) && $_POST['id_session'] == "-1") {
        $sql .= " INNER JOIN formateurs ON (formateur_id = id_formateur) ";
        $sql .= "INNER JOIN secteurs ON (secteur_id = id_secteur) ";
    }
    $sql .= " JOIN cours_sessions ON (cours_sessions.id_session = session_id) 
            JOIN cours_ressources ON (cours_ressources.id_cours = cours_sessions.id_cours) 
            JOIN cours ON (cours.cours_id = cours_ressources.id_cours) 
            JOIN cours_modules ON (cours_modules.cours_module_id = cours.id_module) 
            LEFT JOIN stagiaires_ressources ON (cours_ressource_id = id_ressource AND stagiaire_id = id_stagiaire) 
            WHERE stagiaire_actif = 1 ";
    if (isset($_POST['id_session']) && !empty($_POST['id_session']) && $_POST['id_session'] > 0) {
        $sql .= " AND stagiaires.id_session=:id_session ";
    }
    $sql .= " ORDER BY session_nom, stagiaire_nom, stagiaire_prenom;";
    $req = $db->prepare($sql);
    if (isset($_POST['id_session']) && !empty($_POST['id_session']) && $_POST['id_session'] > 0) {
        $req->bindValue(':id_session', filter_var($_POST['id_session'], FILTER_VALIDATE_INT), PDO::PARAM_INT);
    }
    $req->execute();
    $stagiaires = $req->fetchAll(PDO::FETCH_ASSOC);
    if (!empty($stagiaires)) {
        foreach ($stagiaires as $unStagiaire) {
            $tbody .= '<tr>';
            $tbody .= ' <td scope="row">[' . $unStagiaire['cours_module_libelle'] . "&nbsp;/&nbsp;" . $unStagiaire['cours_title'] . "]&nbsp;" . $unStagiaire['cours_ressource_titre'] . '</td>';
            $tbody .= ' <td>[' . $unStagiaire['session_nom'] . ']&nbsp;' . $unStagiaire['stagiaire_prenom'] . "&nbsp;" . $unStagiaire['stagiaire_nom'] . '</td>';
            $tbody .= ' <td>' . (isset($unStagiaire['stagiaire_ressource_rendue_lien']) ? '<a href="stagiaires/' . $unStagiaire['stagiaire_pseudo'] . "/tps/" . $unStagiaire['stagiaire_ressource_rendue_lien'] . '" target="_blank">' . $unStagiaire['stagiaire_ressource_rendue_lien'] : 'Non rendu') . '</td>';
            $tbody .= '</tr>';
        }
    } else {
        $tbody = '<tr>';
        $tbody .= ' <th scope="row" colspan="3">Aucun exercice/TP n\'a été trouvé !</th>';
        $tbody .= ' <td class="d-none"></td>';
        $tbody .= ' <td class="d-none"></td>';
        $tbody .= '</tr>';
    }
    die($tbody);
}

if (isset($_POST['get_list_internships']) && !empty($_POST['get_list_internships'])) {
    $tbody = "";
    $sql = "SELECT * 
            FROM stages  
            ORDER BY stage_nom;";
    $req = $db->prepare($sql);
    $req->execute();
    $stages = $req->fetchAll(PDO::FETCH_ASSOC);
    if (!empty($stages)) {
        foreach ($stages as $unStage) {
            $tbody .= '<tr>';
            $tbody .= ' <th scope="row">' . $unStage['stage_nom'] . '</th>';
            $tbody .= ' <td>' . $unStage['stage_adresse_rue'] . "&nbsp;,&nbsp;" . $unStage['stage_adresse_cp'] . "&nbsp;-&nbsp;" . $unStage['stage_adresse_ville'] . "&nbsp;(" . $unStage['stage_adresse_pays'] . ")" . '</td>';
            $tbody .= ' <td>' . $unStage['stage_nom_tuteur'] . "&nbsp;" . $unStage['stage_prenom_tuteur'] . "&nbsp;(<a href='mailto:" . $unStage['stage_mail_tuteur'] . "'>Contact</a>)" . '</td>';
            $tbody .= '</tr>';
        }
    } else {
        $tbody = '<tr>';
        $tbody .= ' <th scope="row" colspan="3">Aucun stage n\'a été trouvé !</th>';
        $tbody .= ' <td class="d-none"></td>';
        $tbody .= ' <td class="d-none"></td>';
        $tbody .= ' <td class="d-none"></td>';
        $tbody .= '</tr>';
    }
    die($tbody);
}

if (isset($_POST['get_list_internships_periods']) && !empty($_POST['get_list_internships_periods'])) {
    $tbody = "";
    $sql = "SELECT session_id, session_stage 
            FROM sessions 
            WHERE session_id=:id_session;";
    $req = $db->prepare($sql);
    $req->bindValue(':id_session', filter_var($_POST['id_session'], FILTER_VALIDATE_INT), PDO::PARAM_INT);
    $req->execute();
    $session = $req->fetch(PDO::FETCH_ASSOC);
    if (!empty($session)) {
        $stages = json_decode($session['session_stage'], true);
        if (!empty($stages)) {
            foreach ($stages['stages'] as $unStage) {
                $tbody .= '<tr>';
                $tbody .= ' <td scope="row">' . $unStage['libelle'] . '</td>';
                $tbody .= ' <td>' . $unStage['date_debut'] . '</td>';
                $tbody .= ' <td>' . $unStage['date_fin'] . '</td>';
                $tbody .= '</tr>';
            }
        } else {
            $tbody = '<tr>';
            $tbody .= ' <th scope="row" colspan="3">Aucune période de stage n\'a été trouvée !</th>';
            $tbody .= ' <td class="d-none"></td>';
            $tbody .= ' <td class="d-none"></td>';
            $tbody .= '</tr>';
        }
    } else {
        $tbody = '<tr>';
        $tbody .= ' <th scope="row" colspan="3">Aucune session n\'a été trouvée !</th>';
        $tbody .= ' <td class="d-none"></td>';
        $tbody .= ' <td class="d-none"></td>';
        $tbody .= '</tr>';
    }
    die($tbody);
}

if (isset($_POST['get_list_faqs']) && !empty($_POST['get_list_faqs'])) {
    $tbody = "";
    $sql = "SELECT * 
            FROM faqs 
            LEFT JOIN secteurs ON (secteur_id = id_secteur) 
            ORDER BY faq_theme, faq_title;";
    $req = $db->prepare($sql);
    $req->execute();
    $faqs = $req->fetchAll(PDO::FETCH_ASSOC);
    if (!empty($faqs)) {
        foreach ($faqs as $uneFaq) {
            $tbody .= '<tr>';
            $tbody .= ' <th scope="row">' . $uneFaq['faq_theme'] . '</th>';
            $tbody .= ' <td>' . $uneFaq['faq_title'] . '</td>';
            $tbody .= ' <td>' . $uneFaq['faq_content'] . '</td>';
            $tbody .= ' <td>' . $uneFaq['faq_visible'] . '</td>';
            $tbody .= ' <td>' . $uneFaq['faq_priority'] . '</td>';
            $tbody .= ' <td>' . $uneFaq['secteur_nom'] . '</td>';
            $tbody .= '</tr>';
        }
    } else {
        $tbody = '<tr>';
        $tbody .= ' <th scope="row" colspan="6">Aucune F.A.Q n\'a été trouvée !</th>';
        $tbody .= ' <td class="d-none"></td>';
        $tbody .= ' <td class="d-none"></td>';
        $tbody .= ' <td class="d-none"></td>';
        $tbody .= ' <td class="d-none"></td>';
        $tbody .= ' <td class="d-none"></td>';
        $tbody .= '</tr>';
    }
    die($tbody);
}

if (isset($_POST['get_stats_convention']) && !empty($_POST['get_stats_convention'])) {
    if (isset($_POST['id_session'])) {
        $_SESSION['filtres']['session_id'] = filter_var($_POST['id_session'], FILTER_VALIDATE_INT);
    } else {
        $_SESSION['filtres']['session_id'] = -1;
    }

    $label = "";
    $sql = "SELECT stagiaire_convention_recue 
            FROM stagiaires sta 
            JOIN sessions s ON(s.session_id = sta.id_session) ";
    if (isset($_POST['id_session']) && $_POST['id_session'] == -1) {
        $sql .= " 
            JOIN formateurs f ON(f.formateur_id = s.id_formateur) ";
    }
    $sql .= " WHERE 1 
              AND sta.id_stage IS NOT NULL ";
    if (isset($_POST['id_session']) && $_POST['id_session'] == -1) {
        $sql .= " AND f.id_secteur=:id_secteur ";
        $sql .= " AND s.session_stage_date_debut <= NOW() ";
        $label = "sur le secteur";
    } elseif (isset($_POST['id_session']) && $_POST['id_session'] == 0) {
        $sql .= " AND s.id_formateur=:id_formateur ";
        $sql .= " AND s.session_stage_date_debut <= NOW() ";
        $label = "sur les sessions référées par moi";
    } elseif (isset($_POST['id_session']) && $_POST['id_session'] > 0) {
        $sql .= " AND sta.id_session=:id_session ";
        $label = "pour la session";
    }
    $sql .= " AND sta.stagiaire_actif=1 ";
    $req = $db->prepare($sql);
    if (isset($_POST['id_session']) && $_POST['id_session'] == -1) {
        $req->bindValue(":id_secteur", filter_var($_SESSION['utilisateur']['id_secteur'], FILTER_VALIDATE_INT));
    } elseif (isset($_POST['id_session']) && $_POST['id_session'] == 0) {
        $req->bindValue(":id_formateur", filter_var($_SESSION['utilisateur']['formateur_id'], FILTER_VALIDATE_INT));
    } elseif (isset($_POST['id_session']) && $_POST['id_session'] > 0) {
        $req->bindValue(":id_session", filter_var($_POST['id_session'], FILTER_VALIDATE_INT));
    }
    $req->execute();
    $somme = 0;
    foreach ($req->fetchAll(PDO::FETCH_COLUMN) as $value) {
        $somme += $value;
    }

    $total = $req->rowCount();
    $color = "";
    $value = 0;
    $success = true;
    if (!empty($total)) {
        $value = ($somme / $total) * 100;
    } else {
        $success = false;
    }

    if ($value <= 30) {
        $color = "col-bad";
    } elseif ($value <= 70) {
        $color = "col-medium";
    } else {
        $color = "col-good";
    }

    die(json_encode(array(
        "success" => $success,
        "value" => (!empty($success) ? ($value == 0 ? 0 : ($value == 100 ? 100 : number_format($value, 2, ","))) . "%" : "Aucune donnée"),
        "color" => $color,
        "label" => $label
    )));
} elseif (isset($_POST['get_stats_attestation']) && !empty($_POST['get_stats_attestation'])) {
    if (isset($_POST['id_session'])) {
        $_SESSION['filtres']['session_id'] = filter_var($_POST['id_session'], FILTER_VALIDATE_INT);
    } else {
        $_SESSION['filtres']['session_id'] = -1;
    }

    $label = "";
    $sql = "SELECT stagiaire_attestation_recue 
            FROM stagiaires sta 
            JOIN sessions s ON(s.session_id = sta.id_session AND s.session_stage_date_debut <= NOW()) ";
    if (isset($_POST['id_session']) && $_POST['id_session'] == -1) {
        $sql .= " 
            JOIN formateurs f ON(f.formateur_id = s.id_formateur) ";
    }
    $sql .= " WHERE 1 
              AND sta.id_stage IS NOT NULL ";
    if (isset($_POST['id_session']) && $_POST['id_session'] == -1) {
        $sql .= " AND f.id_secteur=:id_secteur ";
        $sql .= " AND s.session_stage_date_fin <= NOW() ";
        $label = "sur le secteur";
    } elseif (isset($_POST['id_session']) && $_POST['id_session'] == 0) {
        $sql .= " AND s.id_formateur=:id_formateur ";
        $sql .= " AND s.session_stage_date_fin <= NOW() ";
        $label = "sur les sessions référées par moi";
    } elseif (isset($_POST['id_session']) && $_POST['id_session'] > 0) {
        $sql .= " AND sta.id_session=:id_session ";
        $label = "pour la session";
    }
    $sql .= " AND sta.stagiaire_actif=1 ";
    $req = $db->prepare($sql);
    if (isset($_POST['id_session']) && $_POST['id_session'] == -1) {
        $req->bindValue(":id_secteur", filter_var($_SESSION['utilisateur']['id_secteur'], FILTER_VALIDATE_INT));
    } elseif (isset($_POST['id_session']) && $_POST['id_session'] == 0) {
        $req->bindValue(":id_formateur", filter_var($_SESSION['utilisateur']['formateur_id'], FILTER_VALIDATE_INT));
    } elseif (isset($_POST['id_session']) && $_POST['id_session'] > 0) {
        $req->bindValue(":id_session", filter_var($_POST['id_session'], FILTER_VALIDATE_INT));
    }
    $req->execute();
    $somme = 0;
    foreach ($req->fetchAll(PDO::FETCH_COLUMN) as $value) {
        $somme += $value;
    }

    $total = $req->rowCount();
    $color = "";
    $value = 0;
    $success = true;
    if (!empty($total)) {
        $value = ($somme / $total) * 100;
    } else {
        $success = false;
    }

    if ($value <= 30) {
        $color = "col-bad";
    } elseif ($value <= 70) {
        $color = "col-medium";
    } else {
        $color = "col-good";
    }

    die(json_encode(array(
        "success" => $success,
        "value" => (!empty($success) ? ($value == 0 ? 0 : ($value == 100 ? 100 : number_format($value, 2, ","))) . "%" : "Aucune donnée"),
        "color" => $color,
        "label" => $label
    )));
} elseif (isset($_POST['get_stats_evaluation']) && !empty($_POST['get_stats_evaluation'])) {
    if (isset($_POST['id_session'])) {
        $_SESSION['filtres']['session_id'] = filter_var($_POST['id_session'], FILTER_VALIDATE_INT);
    } else {
        $_SESSION['filtres']['session_id'] = -1;
    }

    $label = "";
    $sql = "SELECT stagiaire_evaluation_recue 
            FROM stagiaires sta 
            JOIN sessions s ON(s.session_id = sta.id_session AND s.session_stage_date_debut <= NOW()) ";
    if (isset($_POST['id_session']) && $_POST['id_session'] == -1) {
        $sql .= " 
            JOIN formateurs f ON(f.formateur_id = s.id_formateur) ";
    }
    $sql .= " WHERE 1 
              AND sta.id_stage IS NOT NULL ";
    if (isset($_POST['id_session']) && $_POST['id_session'] == -1) {
        $sql .= " AND f.id_secteur=:id_secteur ";
        $sql .= " AND s.session_stage_date_fin <= NOW() ";
        $label = "sur le secteur";
    } elseif (isset($_POST['id_session']) && $_POST['id_session'] == 0) {
        $sql .= " AND s.id_formateur=:id_formateur ";
        $sql .= " AND s.session_stage_date_fin <= NOW() ";
        $label = "sur les sessions référées par moi";
    } elseif (isset($_POST['id_session']) && $_POST['id_session'] > 0) {
        $sql .= " AND sta.id_session=:id_session ";
        $label = "pour la session";
    }
    $sql .= " AND sta.stagiaire_actif =1 ";
    $req = $db->prepare($sql);
    if (isset($_POST['id_session']) && $_POST['id_session'] == -1) {
        $req->bindValue(":id_secteur", filter_var($_SESSION['utilisateur']['id_secteur'], FILTER_VALIDATE_INT));
    } elseif (isset($_POST['id_session']) && $_POST['id_session'] == 0) {
        $req->bindValue(":id_formateur", filter_var($_SESSION['utilisateur']['formateur_id'], FILTER_VALIDATE_INT));
    } elseif (isset($_POST['id_session']) && $_POST['id_session'] > 0) {
        $req->bindValue(":id_session", filter_var($_POST['id_session'], FILTER_VALIDATE_INT));
    }
    $req->execute();
    $somme = 0;
    foreach ($req->fetchAll(PDO::FETCH_COLUMN) as $value) {
        $somme += $value;
    }

    $total = $req->rowCount();
    $color = "";
    $value = 0;
    $success = true;
    if (!empty($total)) {
        $value = ($somme / $total) * 100;
    } else {
        $success = false;
    }

    if ($value <= 30) {
        $color = "col-bad";
    } elseif ($value <= 70) {
        $color = "col-medium";
    } else {
        $color = "col-good";
    }

    die(json_encode(array(
        "success" => $success,
        "value" => (!empty($success) ? ($value == 0 ? 0 : ($value == 100 ? 100 : number_format($value, 2, ","))) . "%" : "Aucune donnée"),
        "color" => $color,
        "label" => $label
    )));
} elseif (isset($_POST['get_stats_presence']) && !empty($_POST['get_stats_presence'])) {
    if (isset($_POST['id_session'])) {
        $_SESSION['filtres']['session_id'] = filter_var($_POST['id_session'], FILTER_VALIDATE_INT);
    } else {
        $_SESSION['filtres']['session_id'] = -1;
    }

    $label = "";
    $sql = "SELECT stagiaire_horaires_recues_1, stagiaire_horaires_recues_2, stagiaire_horaires_recues_3 
            FROM stagiaires sta 
            JOIN sessions s ON(s.session_id = sta.id_session AND s.session_stage_date_debut <= NOW()) ";
    if (isset($_POST['id_session']) && $_POST['id_session'] == -1) {
        $sql .= " 
            JOIN formateurs f ON(f.formateur_id = s.id_formateur) ";
    }
    $sql .= " WHERE 1 
              AND sta.id_stage IS NOT NULL ";
    if (isset($_POST['id_session']) && $_POST['id_session'] == -1) {
        $sql .= " AND f.id_secteur=:id_secteur ";
        $sql .= " AND s.session_stage_date_fin <= NOW() ";
        $label = "sur le secteur";
    } elseif (isset($_POST['id_session']) && $_POST['id_session'] == 0) {
        $sql .= " AND s.id_formateur=:id_formateur ";
        $sql .= " AND s.session_stage_date_fin <= NOW() ";
        $label = "sur les sessions référées par moi";
    } elseif (isset($_POST['id_session']) && $_POST['id_session'] > 0) {
        $sql .= " AND sta.id_session=:id_session ";
        $label = "pour la session";
    }
    $sql .= " AND sta.stagiaire_actif=1 ";
    $req = $db->prepare($sql);
    if (isset($_POST['id_session']) && $_POST['id_session'] == -1) {
        $req->bindValue(":id_secteur", filter_var($_SESSION['utilisateur']['id_secteur'], FILTER_VALIDATE_INT));
    } elseif (isset($_POST['id_session']) && $_POST['id_session'] == 0) {
        $req->bindValue(":id_formateur", filter_var($_SESSION['utilisateur']['formateur_id'], FILTER_VALIDATE_INT));
    } elseif (isset($_POST['id_session']) && $_POST['id_session'] > 0) {
        $req->bindValue(":id_session", filter_var($_POST['id_session'], FILTER_VALIDATE_INT));
    }
    $req->execute();
    $somme = 0;
    foreach ($req->fetchAll(PDO::FETCH_ASSOC) as $value) {
        $somme += $value['stagiaire_horaires_recues_1'];
        $somme += $value['stagiaire_horaires_recues_2'];
        $somme += $value['stagiaire_horaires_recues_3'];
    }
    $somme = $somme / 3;

    $total = $req->rowCount();
    $color = "";
    $value = 0;
    $success = true;
    if (!empty($total)) {
        $value = ($somme / $total) * 100;
    } else {
        $success = false;
    }

    if ($value <= 30) {
        $color = "col-bad";
    } elseif ($value <= 70) {
        $color = "col-medium";
    } else {
        $color = "col-good";
    }

    die(json_encode(array(
        "success" => $success,
        "value" => (!empty($success) ? ($value == 0 ? 0 : ($value == 100 ? 100 : number_format($value, 2, ","))) . "%" : "Aucune donnée"),
        "color" => $color,
        "label" => $label
    )));
} elseif (isset($_POST['get_stats_reussite']) && !empty($_POST['get_stats_reussite'])) {
    if (isset($_POST['id_session'])) {
        $_SESSION['filtres']['session_id'] = filter_var($_POST['id_session'], FILTER_VALIDATE_INT);
    } else {
        $_SESSION['filtres']['session_id'] = -1;
    }

    $label = "";
    $sql = "SELECT stagiaire_diplome
            FROM stagiaires sta 
            JOIN sessions s ON(s.session_id = sta.id_session AND s.session_date_fin < NOW()) ";
    if (isset($_POST['id_session']) && $_POST['id_session'] == -1) {
        $sql .= " 
            JOIN formateurs f ON(f.formateur_id = s.id_formateur) ";
    }
    $sql .= " WHERE 1 
              AND sta.stagiaire_diplome IS NOT NULL ";
    if (isset($_POST['id_session']) && $_POST['id_session'] == -1) {
        $sql .= " AND f.id_secteur=:id_secteur ";
        $label = "sur le secteur";
    } elseif (isset($_POST['id_session']) && $_POST['id_session'] == 0) {
        $sql .= " AND s.id_formateur=:id_formateur ";
        $label = "sur les sessions référées par moi";
    } elseif (isset($_POST['id_session']) && $_POST['id_session'] > 0) {
        $sql .= " AND sta.id_session=:id_session ";
        $label = "pour la session";
    }
    $sql .= " AND sta.stagiaire_actif = 1 ";
    $req = $db->prepare($sql);
    if (isset($_POST['id_session']) && $_POST['id_session'] == -1) {
        $req->bindValue(":id_secteur", filter_var($_SESSION['utilisateur']['id_secteur'], FILTER_VALIDATE_INT));
    } elseif (isset($_POST['id_session']) && $_POST['id_session'] == 0) {
        $req->bindValue(":id_formateur", filter_var($_SESSION['utilisateur']['formateur_id'], FILTER_VALIDATE_INT));
    } elseif (isset($_POST['id_session']) && $_POST['id_session'] > 0) {
        $req->bindValue(":id_session", filter_var($_POST['id_session'], FILTER_VALIDATE_INT));
    }
    $req->execute();

    $somme = 0;
    $sommeFaussee = 0;
    $total = 0;
    foreach ($req->fetchAll(PDO::FETCH_ASSOC) as $value) {
        if ($value['stagiaire_diplome'] == 1) {
            $somme += 1;
        }
        if ($value['stagiaire_diplome'] <> -4) {
            $total += 1;
        }
        if (!empty($value['stagiaire_diplome']) && ($value['stagiaire_diplome'] == 1 || $value['stagiaire_diplome'] == -1 || $value['stagiaire_diplome'] == -2 || $value['stagiaire_diplome'] == -3)) {
            $sommeFaussee += 1;
        }
    }

    $color = "";
    $value = 0;
    $colorBis = "";
    $valueFausee = 0;
    $success = true;
    if (!empty($total)) {
        $value = ($somme / $total) * 100;
        $valueFausee = ($sommeFaussee / $total) * 100;
    } else {
        $success = false;
    }

    if ($value <= 30) {
        $color = "col-bad";
    } elseif ($value <= 70) {
        $color = "col-medium";
    } else {
        $color = "col-good";
    }

    if ($valueFausee <= 30) {
        $colorBis = "col-bad";
    } elseif ($valueFausee <= 70) {
        $colorBis = "col-medium";
    } else {
        $colorBis = "col-good";
    }

    die(json_encode(array(
        "success" => $success,
        "value" => (!empty($success) ? ($value == 0 ? 0 : ($value == 100 ? 100 : number_format($value, 2, ","))) . "%" : "Aucune donnée"),
        "color" => $color,
        "label" => $label
    )));
} elseif (isset($_POST['get_stats_satisfaction']) && !empty($_POST['get_stats_satisfaction'])) {
    if (isset($_POST['id_session'])) {
        $_SESSION['filtres']['session_id'] = filter_var($_POST['id_session'], FILTER_VALIDATE_INT);
    } else {
        $_SESSION['filtres']['session_id'] = -1;
    }

    $label = "";
    $sql = "SELECT stagiaire_questionnaire_note 
            FROM stagiaires sta 
            JOIN stagiaires_questionnaires sq ON(sta.stagiaire_id = sq.id_stagiaire) 
            JOIN sessions s ON(s.session_id = sta.id_session) ";
    if (isset($_POST['id_session']) && $_POST['id_session'] == -1) {
        $sql .= " 
            JOIN formateurs f ON(f.formateur_id = s.id_formateur) ";
    }
    $sql .= " WHERE 1 ";
    if (isset($_POST['id_session']) && $_POST['id_session'] == -1) {
        $sql .= " AND f.id_secteur=:id_secteur ";
        $label = "sur le secteur";
    } elseif (isset($_POST['id_session']) && $_POST['id_session'] == 0) {
        $sql .= " AND s.id_formateur=:id_formateur ";
        $label = "sur les sessions référées par moi";
    } elseif (isset($_POST['id_session']) && $_POST['id_session'] > 0) {
        $sql .= " AND sta.id_session=:id_session ";
        $label = "pour la session";
    }
    $sql .= " AND sta.stagiaire_actif =1 ";
    $req = $db->prepare($sql);
    if (isset($_POST['id_session']) && $_POST['id_session'] == -1) {
        $req->bindValue(":id_secteur", filter_var($_SESSION['utilisateur']['id_secteur'], FILTER_VALIDATE_INT));
    } elseif (isset($_POST['id_session']) && $_POST['id_session'] == 0) {
        $req->bindValue(":id_formateur", filter_var($_SESSION['utilisateur']['formateur_id'], FILTER_VALIDATE_INT));
    } elseif (isset($_POST['id_session']) && $_POST['id_session'] > 0) {
        $req->bindValue(":id_session", filter_var($_POST['id_session'], FILTER_VALIDATE_INT));
    }
    $req->execute();
    $somme = 0;
    foreach ($req->fetchAll(PDO::FETCH_COLUMN) as $value) {
        $somme += $value;
    }

    $total = $req->rowCount();
    $color = "";
    $value = 0;
    $success = true;
    if (!empty($total)) {
        $value = ($somme / $total);
    } else {
        $success = false;
    }

    if ($value <= 2.5) {
        $color = "col-bad";
    } elseif ($value <= 3.5) {
        $color = "col-medium";
    } else {
        $color = "col-good";
    }

    die(json_encode(array(
        "success" => $success,
        "value" => (!empty($success) ? ($value == 0 ? 0 : ($value == 5 ? 5 : number_format($value, 2, ","))) . "/5" : "Aucune donnée"),
        "color" => $color,
        "label" => $label
    )));
}

if (isset($_POST['get_courses']) &&  !empty($_POST['get_courses'])) {
    $success = true;

    if ($_SESSION['utilisateur']['formateur_id'] > 0) {
        $sql_select_cours = "SELECT cours_id, cours_title, cours_synopsis, cours_link, cours_module_libelle, cours_module_illustration, cours_ressource_id, formateur_nom, formateur_prenom 
                            FROM cours c 
                            INNER JOIN cours_modules cm ON cm.cours_module_id = c.id_module
                            INNER JOIN formateurs f ON (f.formateur_id = c.id_formateur) 
                            LEFT JOIN cours_ressources cr ON cr.id_cours = c.cours_id  
                            WHERE 1 ";
        if (isset($_POST['recherche']) && !empty($_POST['recherche'])) {
            $mots_cles = explode(" ", filter_var(trim($_POST['recherche'], FILTER_SANITIZE_SPECIAL_CHARS)));
            $conditions = array();
            foreach ($mots_cles as $mot) {
                $conditions[] = "cours_keywords LIKE '%" . $mot . "%'";
            }
            $sql_select_cours .= " AND " . implode(' AND ', $conditions);
        }
        $sql_select_cours .= " AND cours_module_uuid=:cours_uuid
                                GROUP BY cours_id
                                ORDER BY cours_position;";
        $req_select_cours = $db->prepare($sql_select_cours);
    } else {
        $sql_select_cours = "SELECT cours_id, cours_title, cours_synopsis, cours_link, cours_module_libelle, cours_module_illustration, cours_ressource_id, formateur_nom, formateur_prenom 
                            FROM cours c 
                            INNER JOIN cours_modules cm ON cm.cours_module_id = c.id_module
                            INNER JOIN formateurs f ON (f.formateur_id = c.id_formateur) ";
        if (isset($_SESSION["utilisateur"]["id_session"]) && !empty($_SESSION["utilisateur"]["id_session"])) {
            $sql_select_cours .= " INNER JOIN cours_sessions cs ON (c.cours_id = cs.id_cours AND cs.id_session=:id_session) ";
        } else {
            $sql_select_cours .= " INNER JOIN cours_sessions cs ON c.cours_id = cs.id_cours ";
        }
        $sql_select_cours .= "  LEFT JOIN cours_ressources cr ON cr.id_cours = c.cours_id  
                                WHERE cours_session_active = 1
                                AND cours_module_uuid=:cours_uuid ";
        if (isset($_POST['recherche']) && !empty($_POST['recherche'])) {
            $mots_cles = explode(" ", filter_var(trim($_POST['recherche'], FILTER_SANITIZE_SPECIAL_CHARS)));
            $conditions = array();
            foreach ($mots_cles as $mot) {
                $conditions[] = "cours_keywords LIKE '%" . $mot . "%'";
            }
            $sql_select_cours .= " AND " . implode(' AND ', $conditions);
        }
        if (!isset($_SESSION["utilisateur"]["id_session"]) || empty($_SESSION["utilisateur"]["id_session"])) {
            $sql_select_cours .= " GROUP BY cours_id ";
        }
        $sql_select_cours .= " GROUP BY cours_id ";
        $sql_select_cours .= " ORDER BY cours_position;";
        $req_select_cours = $db->prepare($sql_select_cours);
        if (isset($_SESSION["utilisateur"]["id_session"]) && !empty($_SESSION["utilisateur"]["id_session"])) {
            $req_select_cours->bindParam(":id_session", $_SESSION["utilisateur"]["id_session"]);
        }
    }
    $req_select_cours->bindParam(":cours_uuid", $_POST["module"]);
    $req_select_cours->execute();
    $cours = $req_select_cours->fetchAll(PDO::FETCH_ASSOC);

    $liste_cours = "";
    if ($_SESSION['utilisateur']['formateur_id'] > 0 && !empty($_SESSION['mode_edition'])) {
        $liste_cours .= '<div class="col-xs-1 col-lg-3 mb-3">
            <div class="card">
                <span class="card-img-top" alt="Illustration a ajouter">
                    <input type="file" class="hidden" name="add_course_img" id="add_course_img">
                    ' . @file_get_contents("../../public/formation/imgs/add.svg") . '
                </span>
                <div class="card-body">
                    <h5 class="card-title text-decoration-underline"><input type="text" placeholder="Nom du cours..."></h5>
                    <p class="card-text"><textarea placeholder="Synopsis du cours..."></textarea></p>
                </div>
            </div>
        </div>';
    }
    if (!empty($cours)) {
        foreach ($cours as $cours) {
            $liste_cours .= '<div class="col-xs-1 col-lg-3 mb-3">
                    <a title="Cours fait par ' . ucwords($cours['formateur_prenom']) . " " . strtoupper($cours['formateur_nom']) . '" href="embed.php?slide=' . $cours['cours_link'] . '" class="text-decoration-none text-black">
                        <div class="card">
                            <span class="card-img-top">
                                <img class="img-course" alt="Illustration ' . $cours["cours_module_libelle"] . '" src="/erp/public/formation/imgs/' . (file_exists('/var/www/html/erp/public/formation/imgs/' . $cours['cours_module_illustration']) ? $cours['cours_module_illustration'] : "no_data.svg") . '" loading="lazy" >
                                ' . (isset($cours["cours_ressource_id"]) && !empty($cours["cours_ressource_id"]) ? '<i class="fa-solid fa-book text-black" style="color: black;position: absolute;top: 10px;right: 10px;"></i>' : '') . '
                            </span>
                            <div class="card-body">
                                <h5 class="card-title text-decoration-underline">' . $cours["cours_title"] . '</h5>
                                <p class="card-text">' . $cours["cours_synopsis"] . '</p>
                            </div>
                        </div>
                    </a>
                </div>';
        }
    } else {
        $liste_cours = '<div class="col-3 offset-4 mt-5 text-center">
                    ' . @file_get_contents("../../public/formation/imgs/no_data.svg") . '
                            <h3 class="mt-3">Aucun cours trouvé...</h3>
                        </div>';
    }

    die(json_encode(array(
        'success' => $success,
        'cours' => $liste_cours
    )));
}

if (isset($_POST['put_user_informations']) && !empty($_POST['put_user_informations'])) {
    // TODO

    die(json_encode(array(
        'success' => true
    )));
}

if (isset($_POST['toggle_edition_mode']) && !empty($_POST['toggle_edition_mode'])) {
    $_SESSION['mode_edition'] = !$_SESSION['mode_edition'];

    die(json_encode(array(
        'success' => $_SESSION['mode_edition']
    )));
}

if (isset($_POST['load_trainees']) && !empty($_POST['load_trainees'])) {
    $req = $db->prepare('SELECT stagiaire_id, stagiaire_pseudo, stagiaire_nom, stagiaire_prenom, DATE(session_date_fin) <= DATE(NOW()) + INTERVAL 30 DAY AS stagiaire_bientot_partie 
                        FROM stagiaires s 
                        JOIN sessions ss ON (ss.session_id = s.id_session) 
                        WHERE stagiaire_actif IS TRUE 
                        AND session_date_fin > NOW() 
                        AND id_formateur=:id_formateur;');
    $req->bindValue(':id_formateur', $_SESSION['utilisateur']['formateur_id']);
    $req->execute();

    die(json_encode(array(
        'trainees' => $req->fetchAll(PDO::FETCH_ASSOC),
        'nb_trainees' => $req->rowCount()
    )));
}

if (isset($_POST['reset_pass_trainee']) && !empty($_POST['reset_pass_trainee'])) {
    $req = $db->prepare('UPDATE stagiaires 
                        SET stagiaire_mdp = stagiaire_mdp_save
                        WHERE stagiaire_pseudo=:pseudo_stagiaire;');
    $req->bindValue(':pseudo_stagiaire', $_POST['username_trainee']);

    die(json_encode(array(
        'success' => $req->execute()
    )));
}

if (isset($_POST['connect_as_trainee']) && !empty($_POST['connect_as_trainee'])) {
    die(json_encode(connexionUtilisateur($_POST['username_trainee'], null)));
}

if (
    isset($_POST['check_email_stagiaire']) && !empty($_POST['check_email_stagiaire'])
    && isset($_POST['form_mail_stagiaire']) && !empty($_POST['form_mail_stagiaire'])
) {
    $disponible = true;
    $message = "";
    $req = $db->prepare("SELECT stagiaire_id FROM stagiaires WHERE stagiaire_mail=:mail_stagiaire;");
    $req->bindValue(':mail_stagiaire', $_POST['form_mail_stagiaire'], PDO::PARAM_INT);
    $req->execute();
    if (!empty($req->fetch())) {
        $disponible = false;
        $message = "Cette adresse email n'est pas disponible.";
    } elseif (!filter_var($_POST['form_mail_stagiaire'], FILTER_VALIDATE_EMAIL)) {
        $disponible = false;
        $message = "Cette adresse email ne correspond pas à un email.";
    }
    die(json_encode(array(
        "disponible" => $disponible,
        "message" => $message
    )));
}

if (
    isset($_POST['form_update_trainee']) && !empty($_POST['form_update_trainee'])
    && isset($_POST['form_nom_stagiaire']) && !empty($_POST['form_nom_stagiaire'])
    && isset($_POST['form_prenom_stagiaire']) && !empty($_POST['form_prenom_stagiaire'])
) {
    $sql = "UPDATE stagiaires 
            SET 
                stagiaire_nom=:nom_stagiaire, 
                stagiaire_prenom=:prenom_stagiaire ";
    if (isset($_POST['form_mail_stagiaire']) && !empty($_POST['form_mail_stagiaire'])) {
        $sql .= " , stagiaire_mail=:mail_stagiaire ";
    }
    if (isset($_POST['form_mdp_stagiaire']) && !empty($_POST['form_mdp_stagiaire'])) {
        $sql .= " , stagiaire_mdp=:mdp_stagiaire ";
    }
    if (isset($_POST['form_avatar_stagiaire']) && !empty($_POST['form_avatar_stagiaire'])) {
        $sql .= " , id_avatar=:id_avatar ";
    }
    $sql .= " WHERE stagiaire_id=:id_stagiaire;";
    $req = $db->prepare($sql);
    $req->bindValue(':nom_stagiaire', strtoupper($_POST['form_nom_stagiaire']));
    $req->bindValue(':prenom_stagiaire', ucwords($_POST['form_prenom_stagiaire']));
    if (isset($_POST['form_mail_stagiaire']) && !empty($_POST['form_mail_stagiaire'])) {
        $req->bindValue(':mail_stagiaire', filter_var($_POST['form_mail_stagiaire'], FILTER_VALIDATE_EMAIL));
    }
    if (isset($_POST['form_mdp_stagiaire']) && !empty($_POST['form_mdp_stagiaire'])) {
        $req->bindValue(':mdp_stagiaire', password_hash($_POST['form_mdp_stagiaire'], PASSWORD_BCRYPT));
        $req->bindValue(':id_stagiaire', filter_var($_SESSION['utilisateur']['stagiaire_id'], FILTER_VALIDATE_INT));
        if (isset($_POST['form_avatar_stagiaire']) && !empty($_POST['form_avatar_stagiaire'])) {
            $req->bindValue(':id_avatar', filter_var($_POST['form_avatar_stagiaire'], FILTER_VALIDATE_INT));
        }
        $req->execute();
        header("Location: /erp/public/deconnexion.php?type=info&message=" . urlencode("Vous devez vous reconnecter suite aux changements"));
        exit;
    }
    if (isset($_POST['form_avatar_stagiaire']) && !empty($_POST['form_avatar_stagiaire'])) {
        $req->bindValue(':id_avatar', filter_var($_POST['form_avatar_stagiaire'], FILTER_VALIDATE_INT));
    }
    $req->bindValue(':id_stagiaire', filter_var($_SESSION['utilisateur']['stagiaire_id'], FILTER_VALIDATE_INT));
    $req->execute();

    header("Location: ../../public/formation/account.php");
    exit;
}

if (
    isset($_POST['form_update_trainer']) && !empty($_POST['form_update_trainer'])
    && isset($_POST['form_nom_formateur']) && !empty($_POST['form_nom_formateur'])
    && isset($_POST['form_prenom_formateur']) && !empty($_POST['form_prenom_formateur'])
) {
    if (!empty($_POST['form_signature_formateur'])) {
        $uniqid = uniqid();
        $fp = fopen("../v/formateurs/signature_" . $uniqid . ".png", "wb");
        fwrite($fp, base64_decode(explode(',', $_POST['form_signature_formateur'])[1]));
        fclose($fp);
    }
    $sql = "UPDATE formateurs 
            SET 
                formateur_nom=:nom_formateur, 
                formateur_prenom=:prenom_formateur ";
    if (isset($_POST['form_mdp_formateur']) && !empty($_POST['form_mdp_formateur'])) {
        $sql .= " , formateur_mdp=:mdp_formateur ";
    }
    if (!empty($_POST['form_signature_formateur'])) {
        $sql .= ", formateur_signature=:signature_formateur ";
    }
    if (isset($_POST['form_avatar_formateur']) && !empty($_POST['form_avatar_formateur'])) {
        $sql .= " , id_avatar=:id_avatar ";
    }
    $sql .= " WHERE formateur_id=:id_formateur;";
    $req = $db->prepare($sql);
    $req->bindValue(':nom_formateur', strtoupper($_POST['form_nom_formateur']));
    $req->bindValue(':prenom_formateur', ucwords($_POST['form_prenom_formateur']));
    if (isset($_POST['form_mdp_formateur']) && !empty($_POST['form_mdp_formateur'])) {
        $req->bindValue(':mdp_formateur', password_hash($_POST['form_mdp_formateur'], PASSWORD_BCRYPT));
        $req->bindValue(':id_formateur', filter_var($_SESSION['utilisateur']['formateur_id'], FILTER_VALIDATE_INT));
        $req->execute();
        header("Location: /erp/public/deconnexion.php?type=info&message=" . urlencode("Vous devez vous reconnecter suite aux changements"));
        exit;
    }
    if (!empty($_POST['form_signature_formateur'])) {
        $req->bindValue(":signature_formateur", 'signature_' . $uniqid . '.png');
    }
    if (isset($_POST['form_avatar_formateur']) && !empty($_POST['form_avatar_formateur'])) {
        $req->bindValue(':id_avatar', filter_var($_POST['form_avatar_formateur'], FILTER_VALIDATE_INT));
    }
    $req->bindValue(':id_formateur', filter_var($_SESSION['utilisateur']['formateur_id'], FILTER_VALIDATE_INT));
    $req->execute();

    header("Location: ../../public/formation/account.php");
    exit;
}

if (isset($_POST['set_module_position']) && isset($_SESSION['utilisateur']['formateur_id']) && $_SESSION['utilisateur']['formateur_id'] > 0) {
    $sql = "UPDATE cours_modules, cours_modules AS cm2
            SET cours_modules.cours_module_position = cm2.cours_module_position, cm2.cours_module_position = cours_modules.cours_module_position
            WHERE cours_modules.cours_module_position=:new AND cm2.cours_module_position=:old;";
    $req = $db->prepare($sql);
    $req->bindParam(':new', $_POST['new']);
    $req->bindParam(':old', $_POST['old']);
    if ($req->execute()) {
        die(json_encode(array(
            'success' => true,
            'message' => "Module déplacé !"
        )));
    }
    die(json_encode(array(
        'success' => false,
        'message' => "Une erreur s'est produite..."
    )));
}

if (isset($_POST['set_cours_position']) && isset($_SESSION['utilisateur']['formateur_id']) && $_SESSION['utilisateur']['formateur_id'] > 0) {
    $sql = "UPDATE cours, cours AS c2
            SET cours.cours_position = c2.cours_position, c2.cours_position = cours.cours_position
            WHERE cours.cours_position=:new AND c2.cours_position=:old 
            AND cours.id_module = (SELECT cours_module_id 
                                    FROM cours_modules 
                                    WHERE cours_module_uuid=:module_uuid)
            AND c2.id_module = (SELECT cours_module_id 
                                    FROM cours_modules 
                                    WHERE cours_module_uuid=:module_uuid);";
    $req = $db->prepare($sql);
    $req->bindParam(':new', $_POST['new']);
    $req->bindParam(':old', $_POST['old']);
    $req->bindParam(':module_uuid', $_POST['module']);
    if ($req->execute()) {
        die(json_encode(array(
            'success' => true,
            'message' => "Cours déplacé !"
        )));
    }
    die(json_encode(array(
        'success' => false,
        'message' => "Une erreur s'est produite..."
    )));
}

if (isset($_POST['form_faq_theme']) && isset($_POST['form_faq_title']) && !empty($_POST['form_faq_title']) && isset($_POST['form_faq_content']) && !empty($_POST['form_faq_content']) && isset($_POST['form_faq_secteur']) && isset($_SESSION['utilisateur']['formateur_id']) && $_SESSION['utilisateur']['formateur_id'] > 0) {
    $sql = "INSERT INTO faqs(faq_theme, faq_title, faq_content, faq_priority, id_secteur) 
            VALUES(:theme, :title, :content, :priority, :secteur)";
    $req = $db->prepare($sql);
    $req->bindValue(":theme", (!empty($_POST['form_faq_secteur']) && $_POST['form_faq_secteur'] < 0 ? "global" : strtolower($_POST['form_faq_theme'])));
    $req->bindValue(":title", ucfirst($_POST['form_faq_title']));
    $req->bindValue(":content", htmlentities($_POST['form_faq_content']));
    $req->bindValue(":secteur", (!empty($_POST['form_faq_secteur']) && $_POST['form_faq_secteur'] < 0 ? NULL : $_POST['form_faq_secteur']));
    $req->bindValue(":priority", (!empty($_POST['form_faq_secteur']) && $_POST['form_faq_secteur'] < 0 ? 1 : 0));
    if ($req->execute()) {
        header("Location: /erp/public/formation/admin.php");
        exit;
    }
    header("Location: /erp/public/formation/admin.php");
    exit;
}
if (isset($_POST['show_messages']) && isset($_POST['show_messages'])) {
    if (isset($_POST['id_user']) && !empty($_POST['id_user'])) {
        $sql = "SELECT stagiaire_nom, stagiaire_prenom 
            FROM stagiaires 
            WHERE stagiaire_id=:id_stagiaire;";
        $req = $db->prepare($sql);
        $req->bindValue(":id_stagiaire", filter_var($_POST["id_user"], FILTER_VALIDATE_INT));
        $req->execute();
        $stagiaire = $req->fetch(PDO::FETCH_ASSOC);

        if (isset($_SESSION["utilisateur"]["stagiaire_id"]) && $_SESSION["utilisateur"]["stagiaire_id"] > 0) {
            $sql = "SELECT message_id, message_content, message_date, id_send_formateur, id_send_stagiaire, id_stagiaire, id_formateur 
                    FROM messages 
                    WHERE id_send_stagiaire=:id_stagiaire 
                    AND id_stagiaire=:id_user 
                    
                    UNION 
                    
                    SELECT message_id, message_content, message_date, id_send_formateur, id_send_stagiaire, id_stagiaire, id_formateur 
                    FROM messages 
                    WHERE id_stagiaire=:id_stagiaire 
                    AND id_send_stagiaire=:id_user 
                    
                    ORDER BY message_date;";
            $req = $db->prepare($sql);
            $req->bindValue(":id_stagiaire", filter_var($_SESSION["utilisateur"]["stagiaire_id"], FILTER_VALIDATE_INT));
            $req->bindValue(":id_user", filter_var($_POST["id_user"], FILTER_VALIDATE_INT));
            $req->execute();
            $messages = '';
            $liste = $req->fetchAll(PDO::FETCH_ASSOC);
        } elseif (isset($_SESSION["utilisateur"]["formateur_id"]) && $_SESSION["utilisateur"]["formateur_id"] > 0) {
            $sql = "SELECT message_id, message_content, message_date, id_send_formateur, id_send_stagiaire, id_stagiaire, id_formateur 
                    FROM messages 
                    WHERE id_send_stagiaire=:id_user 
                    AND id_formateur=:id_formateur 
                    
                    UNION 
                    
                    SELECT message_id, message_content, message_date, id_send_formateur, id_send_stagiaire, id_stagiaire, id_formateur 
                    FROM messages 
                    WHERE id_stagiaire=:id_user 
                    AND id_send_formateur=:id_formateur 
                    
                    ORDER BY message_date;";
            $req = $db->prepare($sql);
            $req->bindValue(":id_formateur", filter_var($_SESSION["utilisateur"]["formateur_id"], FILTER_VALIDATE_INT));
            $req->bindValue(":id_user", filter_var($_POST["id_user"], FILTER_VALIDATE_INT));
            $req->execute();
            $messages = '';
            $liste = $req->fetchAll(PDO::FETCH_ASSOC);
        }
        // var_dump($sql, $_SESSION["utilisateur"]["formateur_id"], $_POST['id_user']);
        // die;

        if (!empty($liste)) {
            foreach ($liste as $message) {
                $messages .= '<div>';
                $messages .= '  <p class="msg ' . ($message['id_send_formateur'] == $_SESSION["utilisateur"]["formateur_id"] || $message['id_send_stagiaire'] == $_SESSION["utilisateur"]["stagiaire_id"] ? "msg-sender ms-auto" : "msg-receiver me-auto") . '">' . $message['message_content'] . '</p>';
                $messages .= '</div>';
            }
        } else {
            $messages = '<iframe src="https://lottie.host/embed/42d43b29-1cd3-43c6-b2d9-77c963210dd0/ahpeOAtfF5.json"></iframe>';
        }

        die(json_encode(array(
            "success" => true,
            "user" => $stagiaire['stagiaire_prenom'] . "&nbsp;" . $stagiaire['stagiaire_nom'],
            "messages" => $messages
        )));
    } elseif (isset($_POST['id_session']) && !empty($_POST['id_session'])) {
        $sql = "SELECT session_nom 
                FROM sessions 
                WHERE session_id=:id_session;";
        $req = $db->prepare($sql);
        $req->bindValue(":id_session", filter_var($_POST["id_session"], FILTER_VALIDATE_INT));
        $req->execute();
        $session = $req->fetch(PDO::FETCH_ASSOC);

        $sql = "SELECT message_id, message_content, message_date, id_send_formateur, id_send_stagiaire, id_stagiaire, id_formateur 
                FROM messages 
                WHERE (id_send_formateur IS NOT NULL OR id_send_stagiaire IS NOT NULL)
                AND id_session=:id_session 
                ORDER BY message_date;";
        $req = $db->prepare($sql);
        $req->bindValue(":id_session", filter_var($_POST["id_session"], FILTER_VALIDATE_INT));
        $req->execute();
        $messages = '';
        $liste = $req->fetchAll(PDO::FETCH_ASSOC);
        if (!empty($liste)) {
            foreach ($liste as $message) {
                $messages .= '<div>';
                $messages .= '  <p class="msg ' . ($message['id_send_formateur'] == $_SESSION["utilisateur"]["formateur_id"] || $message['id_send_stagiaire'] == $_SESSION["utilisateur"]["stagiaire_id"] ? "msg-sender ms-auto" : "msg-receiver me-auto") . '">' . $message['message_content'] . '</p>';
                $messages .= '</div>';
            }
        } else {
            $messages = '<div class="d-flex justify-content-center"><script src="https://unpkg.com/@dotlottie/player-component@latest/dist/dotlottie-player.mjs" type="module"></script> 
                            <dotlottie-player src="https://lottie.host/42d43b29-1cd3-43c6-b2d9-77c963210dd0/ahpeOAtfF5.json" background="transparent" speed="1" style="width: 300px;" loop autoplay></dotlottie-player>
                        </div>';
        }

        die(json_encode(array(
            "success" => true,
            "session" => $session['session_nom'],
            "messages" => $messages
        )));
    }
}

if (isset($_POST['send_message']) && isset($_POST['send_message'])) {
    if (isset($_SESSION["utilisateur"]["formateur_id"]) && $_SESSION["utilisateur"]["formateur_id"] > 0) {
        $sender = "id_send_formateur";
    } elseif (isset($_SESSION["utilisateur"]["stagiaire_id"]) && $_SESSION["utilisateur"]["stagiaire_id"] > 0) {
        $sender = "id_send_stagiaire";
    }
    if (isset($_POST['id_session']) && !empty($_POST['id_session'])) {
        $receiver = "id_session";
    } elseif (isset($_POST['id_user']) && !empty($_POST['id_user'])) {
        $receiver = "id_stagiaire";
    }

    $sql = "INSERT INTO messages(message_content, message_date, " . $sender . ", " . $receiver . ") 
            VALUES(:message_content, NOW(), :" . $sender . ", :" . $receiver . ");";
    $req = $db->prepare($sql);
    $req->bindValue(":message_content", filter_var($_POST["message_content"], FILTER_SANITIZE_FULL_SPECIAL_CHARS));
    if (isset($_SESSION["utilisateur"]["formateur_id"]) && $_SESSION["utilisateur"]["formateur_id"] > 0) {
        $req->bindValue(":id_send_formateur", filter_var($_SESSION["utilisateur"]["formateur_id"], FILTER_VALIDATE_INT));
    } elseif (isset($_SESSION["utilisateur"]["stagiaire_id"]) && $_SESSION["utilisateur"]["stagiaire_id"] > 0) {
        $req->bindValue(":id_send_stagiaire", filter_var($_SESSION["utilisateur"]["stagiaire_id"], FILTER_VALIDATE_INT));
    }
    if (isset($_POST['id_session']) && !empty($_POST['id_session'])) {
        $req->bindValue(":id_session", filter_var($_POST["id_session"], FILTER_VALIDATE_INT));
    } elseif (isset($_POST['id_user']) && !empty($_POST['id_user'])) {
        $req->bindValue(":id_stagiaire", filter_var($_POST["id_user"], FILTER_VALIDATE_INT));
    }
    $req->execute();
    exit;
}

if (isset($_POST['maj_eval']) && !empty($_POST['maj_eval'])) {
    $sql = "REPLACE INTO stagiaires_acquisitions(id_acquis, id_stagiaire, ids_formateurs, acquisition_niveau) 
            VALUES(:id_acquis, :id_stagiaire, :ids_formateurs, :niveau);";
    $req = $db->prepare($sql);
    $req->bindValue(":id_acquis", filter_var($_POST['id_acquis'], FILTER_VALIDATE_INT));
    $req->bindValue(":id_stagiaire", filter_var($_POST['id_stagiaire'], FILTER_VALIDATE_INT));
    $req->bindValue(":ids_formateurs", htmlspecialchars(strip_tags($_POST['formateurs'])));
    $req->bindValue(":niveau", strtoupper(filter_var($_POST['niveau'], FILTER_SANITIZE_SPECIAL_CHARS)));
    $req->execute();

    exit;
}

if (isset($_POST['search_trainers']) && !empty($_POST['search_trainers'])) {
    $sql = "SELECT formateur_id AS id, CONCAT(formateur_nom, ' ', formateur_prenom) AS value
            FROM formateurs 
            ORDER BY formateur_nom, formateur_prenom;";
    $req = $db->prepare($sql);
    $req->execute();

    die(json_encode(array(
        "success" => true,
        "formateurs" => $req->fetchAll(PDO::FETCH_ASSOC)
    )));
}
?>