<?php

include_once("../../src/m/connect.php");

$title = " | Contrôle administration";

if (array_key_exists("stagiaire_pseudo", $_SESSION["utilisateur"])) {
    header("Location: ../formation");
}

$sql_select_stagiaires = "SELECT i.stagiaire_id, stagiaire_nom, stagiaire_prenom, stagiaire_pseudo, evaluation_errors_max, stagiaire_evaluation_completed, stagiaire_evaluation_correction, stagiaire_evaluation_errors_found, evaluation_id, evaluation_dd_link
                    FROM stagiaires_evaluations ie 
                    JOIN stagiaires i ON (i.stagiaire_id = ie.id_stagiaire) 
                    JOIN evaluations e ON (e.evaluation_id = ie.id_evaluation) 
                    JOIN evaluations_dd edd ON (edd.evaluation_dd_id = e.id_evaluation_dd) 
                    ORDER BY evaluation_dd_id, evaluation_id;";
$req_select_stagiaires = $db->prepare($sql_select_stagiaires);
$req_select_stagiaires->execute();
$stagiaires = $req_select_stagiaires->fetchAll(PDO::FETCH_ASSOC);

$sql_select_tps = "SELECT evaluation_id, id_evaluation_dd 
                    FROM evaluations e
                    JOIN stagiaires_evaluations ie ON (e.evaluation_id = ie.id_evaluation 
                                                        AND stagiaire_evaluation_correction = 0 
                                                        AND stagiaire_evaluation_completed = 1
                                                        ) 
                    GROUP BY evaluation_id;";
$req_select_tps = $db->prepare($sql_select_tps);
$req_select_tps->execute();
$tps = $req_select_tps->fetchAll(PDO::FETCH_ASSOC);

$req_secteurs = $db->prepare("SELECT secteur_id, secteur_nom FROM secteurs ORDER BY secteur_nom;");
$req_secteurs->execute();
$secteurs = $req_secteurs->fetchAll(PDO::FETCH_ASSOC);

$arr = array(
    ["html-css" => 0],
    ["bootstrap" => 0],
);

include_once("./header.php"); ?>
<div class="container-fluid mb-3">
    <div>
        <!-- Nav tabs -->
        <ul class="nav nav-tabs" id="subnavbar">
            <li class="nav-item">
                <a class="nav-link text-dark" data-bs-toggle="tab" href="#accueil" onclick="/*getListAccueil();*/">Accueil</a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-dark active" data-bs-toggle="tab" href="#cours" onclick="getListCourses();">Cours</a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-dark" data-bs-toggle="tab" href="#formateurs" onclick="getListTrainers();">Formateurs</a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-dark" data-bs-toggle="tab" href="#sessions" onclick="getListSessions();">Sessions</a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-dark" data-bs-toggle="tab" href="#stagiaires" onclick="getListTrainees();">Stagiaires</a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-dark" data-bs-toggle="tab" href="#exercices" onclick="getListExercises();">Exercices</a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-dark" data-bs-toggle="tab" href="#stages" onclick="getListInterships();">Stages</a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-dark" data-bs-toggle="tab" href="#faq" onclick="getListFaqs()">F.A.Q</a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-dark" data-bs-toggle="tab" href="#statistiques" onclick="getStats()">Statistiques</a>
            </li>
        </ul>

        <!-- Tab panes -->
        <div class="tab-content">
            <div class="tab-pane container-fluid" id="accueil">
                <p>Ajouter une gestion des carousels sur la page d'accueil</p>
            </div>
            <div class="tab-pane container-fluid active" id="cours">
                <div class="mt-2 mb-2">
                    <button role="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalAddCourse">Ajouter un cours</button>
                    <button role="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#modalManagecours" onclick="showModalManagecours();">Gérer les cours</button>
                </div>
                <div>
                    <table class="table table-bordered table-striped compact" style="margin: 0 auto;width:100%;" id="table-cours">
                        <thead>
                            <tr>
                                <th scope="col">Module</th>
                                <th scope="col">Titre</th>
                                <th scope="col">Synopsis</th>
                                <th scope="col">Mots-clés</th>
                                <th scope="col">Position</th>
                                <th scope="col">Auteur</th>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="tab-pane container-fluid fade" id="formateurs">
                <div class="mt-2 mb-2">
                    <button role="button" class="btn btn-info text-white" data-bs-toggle="modal" data-bs-target="#modalAddTrainer">Ajouter un·e formateur/trice</button>
                </div>
                <div>
                    <table class="table table-bordered table-striped compact" style="margin: 0 auto;width:100%;" id="table-formateurs">
                        <thead>
                            <tr>
                                <th scope="col">Nom</th>
                                <th scope="col">Prénom</th>
                                <th scope="col">Mail</th>
                                <th scope="col">Rôle</th>
                                <th scope="col">Liens</th>
                                <th scope="col">Tél.</th>
                                <th scope="col">Site</th>
                                <th scope="col">Secteur</th>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="tab-pane container-fluid fade" id="sessions">
                <div class="mt-2 mb-2">
                    <button role="button" class="btn btn-info text-white" data-bs-toggle="modal" data-bs-target="#modalAddSession">Ajouter une session</button>
                </div>
                <div>
                    <table class="table table-bordered table-striped compact" style="margin: 0 auto;width:100%;" id="table-sessions">
                        <thead>
                            <tr>
                                <th scope="col">Nom</th>
                                <th scope="col">Sigle</th>
                                <th scope="col">Date début</th>
                                <th scope="col">Date fin</th>
                                <th scope="col">Blason</th>
                                <th scope="col">Référent·e</th>
                                <th scope="col">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="tab-pane container-fluid fade" id="stagiaires">
                <div class="mt-2 mb-2">
                    <button role="button" class="btn btn-info text-white" data-bs-toggle="modal" data-bs-target="#modalAddTrainee">Ajouter un·e stagiaire</button>
                </div>
                <div>
                    <table class="table table-bordered table-striped compact" style="margin: 0 auto;width:100%;" id="table-stagiaires">
                        <thead>
                            <tr>
                                <th scope="col">Nom</th>
                                <th scope="col">Prénom</th>
                                <th scope="col">Email</th>
                                <th scope="col">Pseudo</th>
                                <th scope="col">Tél.</th>
                                <th scope="col">Date de naissance</th>
                                <th scope="col">Nom de la session</th>
                                <th scope="col">Nom de l'entreprise</th>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="tab-pane container-fluid fade" id="exercices">
                <div class="mt-2">
                    <table class="table table-bordered table-striped compact" style="margin: 0 auto;width:100%;" id="table-exercices">
                        <thead>
                            <tr>
                                <th scope="col">Exercice/TP</th>
                                <th scope="col">Prénom NOM</th>
                                <th scope="col">Fichier</th>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="tab-pane container-fluid fade" id="stages">
                <div class="mt-2 mb-2">
                    <button role="button" class="btn btn-info text-white" data-bs-toggle="modal" data-bs-target="#modalAddTutor">Ajouter un·e tuteur/trice</button>
                </div>
                <div>
                    <table class="table table-bordered table-striped compact" style="margin: 0 auto;width:100%;" id="table-stages">
                        <thead>
                            <tr>
                                <th scope="col">Nom entreprise</th>
                                <th scope="col">Adresse</th>
                                <th scope="col">Tuteur</th>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="tab-pane container-fluid fade" id="faq">
                <div class="mt-2 mb-2">
                    <button role="button" class="btn btn-info text-white" data-bs-toggle="modal" data-bs-target="#modalAddQandA">Ajouter une FAQ</button>
                </div>
                <div>
                    <table class="table table-bordered table-striped compact" style="margin: 0 auto;width:100%;" id="table-faqs">
                        <thead>
                            <tr>
                                <th scope="col">Thème</th>
                                <th scope="col">Titre</th>
                                <th scope="col">Contenu</th>
                                <th scope="col">Visibilité</th>
                                <th scope="col">Priorité</th>
                                <th scope="col">Secteur</th>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="tab-pane container-fluid fade" id="statistiques">
                <div class="row mt-2 mb-2">
                    <div class="col-4">
                        <div class="border border-top-0" style="border-top: 3px solid #FC7C1C !important; height: 300px;">
                            <div class="pt-3 px-3 h5">
                                Stage
                            </div>
                            <hr>
                            <div class="pb-3 px-3">
                                <div class="row" style="height: 10vh;">
                                    <div class="col-6">
                                        <div class="border border-primary h-100 p-2">
                                            <span><b id="ratio_valeur_stage_convention">XXX%</b> de conventions reçues</span>
                                            <p id="ratio_libelle_stage_convention"></p>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="border border-secondary h-100 p-2">
                                            <span><b id="ratio_valeur_stage_attestation">XXX%</b> d'attestations reçues</span>
                                            <p id="ratio_libelle_stage_attestation"></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="pb-3 px-3">
                                <div class="row" style="height: 10vh;">
                                    <div class="col-6">
                                        <div class="border border-primary h-100 p-2">
                                            <span><b id="ratio_valeur_stage_evaluation">XXX%</b> d'évaluations reçues</span>
                                            <p id="ratio_libelle_stage_evaluation"></p>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="border border-primary h-100 p-2">
                                            <span><b id="ratio_valeur_stage_presence">XXX%</b> de présence reçues</span>
                                            <p id="ratio_libelle_stage_presence"></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="border border-top-0" style="border-top: 3px solid #FC7C1C !important; height: 300px;">
                            <div class="pt-3 px-3 h5">
                                Session
                            </div>
                            <hr>
                            <div class="pb-3 px-3">
                                <div class="row" style="height: 10vh;">
                                    <div class="col-6">
                                        <div class="border border-primary h-100 p-2">
                                            <span><b id="ratio_valeur_session_reussite">XXX%</b> de réussite</span>
                                            <p id="ratio_libelle_session_reussite"></p>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="border border-secondary h-100 p-2">
                                            <span><b id="ratio_valeur_session_satisfaction">XXX%</b> de satisfaction</span>
                                            <p id="ratio_libelle_session_satisfaction"></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-8 offset-2">

            <!-- Ancien code par rapport aux évaluations de l'application -->
            <div class="modules mt-3">
                <details close>
                    <summary>
                        <span class="admin-titles">Données du module HTML/CSS</span>
                    </summary>
                    <?php foreach ($tps as $value) if ($value['id_evaluation_dd'] == 1) { ?>
                        <a class="btn btn-warning mb-2" href="./check.php?module=html-css&tp=<?= $value['evaluation_id'] ?>">Correction TP<?= $value ?></a>
                    <?php } ?>
                    <table class="table table-bordered table-striped table-responsive">
                        <thead>
                            <tr class="text-center">
                                <th>TP n°</th>
                                <th>(#ID) Prénom NOM</th>
                                <th>Voir</th>
                                <th>TP terminé</th>
                                <th>TP corrigé</th>
                                <th>Score obtenu/Score max</th>
                                <th>Note</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($stagiaires)) {
                                foreach ($stagiaires as $stagiaire) if ($stagiaire['evaluation_dd_link'] == "html-css") {
                                    $arr[0]["html-css"] += 1;
                            ?>
                                    <tr>
                                        <td class="text-center"><?= $stagiaire["evaluation_id"] ?></td>
                                        <td>(<?= $stagiaire["stagiaire_id"] ?>)&nbsp;<?= $stagiaire["stagiaire_nom"] ?> <?= $stagiaire["stagiaire_prenom"] ?></td>
                                        <td><a href="./achieved.php?module=html-css&tp=<?= $stagiaire["evaluation_id"] ?>&stagiaire_username=<?= $stagiaire["stagiaire_pseudo"] ?>&stagiaire_id=<?= $stagiaire["stagiaire_id"] ?>&correction=1" class="btn btn-info btn-sm">Voir</a></td>
                                        <td><?= (!empty($stagiaire["stagiaire_evaluation_completed"]) ? '<span class="circle completed" data-bs-toggle="tooltip" data-bs-placement="right" title="Évaluation terminée !"></span>' : '<span class="circle awaiting" data-bs-toggle="tooltip" data-bs-placement="right" title="Évaluation en cours !"></span>') ?></td>
                                        <td><?= (!empty($stagiaire["stagiaire_evaluation_correction"]) ? '<span class="circle completed" data-bs-toggle="tooltip" data-bs-placement="right" title="Évaluation corrigée !"></span>' : '<span class="circle awaiting" style="cursor: pointer;" onclick="validInternCorrection(' . $stagiaire["stagiaire_id"] . ', ' . $stagiaire["evaluation_id"] . ', \'plus\');" data-bs-toggle="tooltip" data-bs-placement="right" title="Évaluation non corrigée !"></span>') ?></td>
                                        <td class="text-center">
                                            <?php if (!empty($stagiaire["evaluation_errors_max"])) { ?>
                                                <?= (!empty($stagiaire["stagiaire_evaluation_errors_found"]) ? '<i class="fa-solid fa-circle-minus" style="cursor: pointer; float: left; padding-top: 2%; color: var(--col_base);" onclick="validInternCorrection(' . $stagiaire["stagiaire_id"] . ', ' . $stagiaire["evaluation_id"] . ', \'minus\');"></i>' : '') ?>
                                                <span id="errors_found_plus_one_<?= $stagiaire["evaluation_id"] ?>_<?= $stagiaire["stagiaire_id"] ?>" value="<?= intval($stagiaire["stagiaire_evaluation_errors_found"]) ?>"><?= $stagiaire["stagiaire_evaluation_errors_found"] ?></span>
                                                /
                                                <?= $stagiaire["evaluation_errors_max"] ?><?= ($stagiaire["stagiaire_evaluation_errors_found"] < $stagiaire["evaluation_errors_max"] ? '<i class="fa-solid fa-plus-circle" style="cursor: pointer; float: right; padding-top: 2%; color: var(--col_base);" onclick="validInternCorrection(' . $stagiaire["stagiaire_id"] . ', ' . $stagiaire["evaluation_id"] . ', \'plus\');"></i>' : '') ?>
                                            <?php } ?>
                                        </td>
                                        <td class="text-center"><?= (!empty($stagiaire["evaluation_errors_max"]) ? number_format(floatval($stagiaire["stagiaire_evaluation_errors_found"] / $stagiaire["evaluation_errors_max"]) * 100, 0) : "") ?></td>
                                    </tr>
                                <?php }
                            }
                            if (empty($arr[0]["html-css"]) || empty($stagiaires)) { ?>
                                <tr>
                                    <td class="text-center" colspan="7">Aucune donnée à afficher</td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </details>
            </div>

            <div class="modules mt-3">
                <details open>
                    <summary>
                        <span class="admin-titles">Données du module Bootstrap</span>
                    </summary>
                    <?php foreach ($tps as $value) if ($value['id_evaluation_dd'] == 3) { ?>
                        <a class="btn btn-warning mb-2" href="./check.php?module=bootstrap&tp=<?= $value['evaluation_id'] ?>">Correction TP<?= $value ?></a>
                    <?php } ?>
                    <table class="table table-bordered table-striped table-responsive">
                        <thead>
                            <tr class="text-center">
                                <th>TP n°</th>
                                <th>(#ID) Prénom NOM</th>
                                <th>Voir</th>
                                <th>TP terminé</th>
                                <th>TP corrigé</th>
                                <th>Score obtenu/Score max</th>
                                <th>Note</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($stagiaires)) {
                                foreach ($stagiaires as $stagiaire) if ($stagiaire['evaluation_dd_link'] == "bootstrap") {
                                    $arr[0]["bootstrap"] += 1;
                            ?>
                                    <tr>
                                        <td class="text-center"><?= $stagiaire["evaluation_id"] ?></td>
                                        <td>(<?= $stagiaire["stagiaire_id"] ?>)&nbsp;<?= $stagiaire["stagiaire_nom"] ?> <?= $stagiaire["stagiaire_prenom"] ?></td>
                                        <td><a href="achieved.php?module=bootstrap&tp=<?= $stagiaire["evaluation_id"] ?>&stagiaire_username=<?= $stagiaire["stagiaire_username"] ?>&stagiaire_id=<?= $stagiaire["stagiaire_id"] ?>&correction=1" class="btn btn-info btn-sm">Voir</a></td>
                                        <td><?= (!empty($stagiaire["stagiaire_evaluation_completed"]) ? '<span class="circle completed" data-bs-toggle="tooltip" data-bs-placement="right" title="Évaluation terminée !"></span>' : '<span class="circle awaiting" data-bs-toggle="tooltip" data-bs-placement="right" title="Évaluation en cours !"></span>') ?></td>
                                        <td><?= (!empty($stagiaire["stagiaire_evaluation_correction"]) ? '<span class="circle completed" data-bs-toggle="tooltip" data-bs-placement="right" title="Évaluation corrigée !"></span>' : '<span class="circle awaiting" data-bs-toggle="tooltip" data-bs-placement="right" title="Évaluation non corrigée !"></span>') ?></td>
                                        <td class="text-center"><?= $stagiaire["stagiaire_evaluation_errors_found"] ?>/<?= $stagiaire["evaluation_errors_max"] ?></td>
                                        <td class="text-center"><?= number_format(floatval($stagiaire["stagiaire_evaluation_errors_found"] / $stagiaire["evaluation_errors_max"]) * 100, 0) ?></td>
                                    </tr>
                                <?php }
                            }
                            if (empty($arr[0]["bootstrap"]) || empty($stagiaires)) { ?>
                                <tr>
                                    <td class="text-center" colspan="7">Aucune donnée à afficher</td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </details>
            </div>

        </div>
    </div>
</div>


<div class="modal modal-xl fade" id="modalAddTrainer" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="modalAddTrainerTitle" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalAddTrainerTitle">Ajouter un·e formateur/trice</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="#" method="post" id="form_add_trainer">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="form_formateurs_ajout_nom" class="form-label">NOM:<span class="required">*</span></label>
                            <input type="text" class="form-control" placeholder="NOM" name="form_formateurs_ajout_nom" id="form_formateurs_ajout_nom" required>
                        </div>
                        <div class="col-md-6">
                            <label for="form_formateurs_ajout_prenom" class="form-label">Prénom:<span class="required">*</span></label>
                            <input type="text" class="form-control" placeholder="Prénom" name="form_formateurs_ajout_prenom" id="form_formateurs_ajout_prenom" required>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <label for="form_formateurs_ajout_mail" class="form-label">Mail:<span class="required">*</span></label>
                            <input type="text" class="form-control" placeholder="Mail" name="form_formateurs_ajout_mail" id="form_formateurs_ajout_mail" required>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="form_formateurs_ajout_role" class="form-label">Rôle:<span class="required">*</span></label>
                            <input type="text" class="form-control" placeholder="Rôle" name="form_formateurs_ajout_role" id="form_formateurs_ajout_role" required>
                        </div>
                        <div class="col-md-6">
                            <label for="form_formateurs_ajout_liens" class="form-label">Liens:</label>
                            <input type="text" class="form-control" placeholder="Liens (à séparer par avec ;)" name="form_formateurs_ajout_liens" id="form_formateurs_ajout_liens">
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="form_formateurs_ajout_telephone" class="form-label">Téléphone:</label>
                            <input type="text" class="form-control" placeholder="Téléphone" name="form_formateurs_ajout_telephone" id="form_formateurs_ajout_telephone">
                        </div>
                        <div class="col-md-6">
                            <label for="form_formateurs_ajout_portable" class="form-label">Portable:</label>
                            <input type="text" class="form-control" placeholder="Portable" name="form_formateurs_ajout_portable" id="form_formateurs_ajout_portable">
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="form_formateurs_ajout_secteur" class="form-label">Secteur:<span class="required">*</span></label>
                            <select class="form-control" name="form_formateurs_ajout_secteur" id="form_formateurs_ajout_secteur" required>
                                <option value="0" selected disabled>Choisir un secteur</option>
                                <?php foreach (recupererSecteurs() as $secteur) { ?>
                                    <option value="<?= $secteur['secteur_id'] ?>"><?= $secteur['secteur_nom'] ?></option>
                                <?php } ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="form_formateurs_ajout_site" class="form-label">Site:<span class="required">*</span></label>
                            <select class="form-control" name="form_formateurs_ajout_site" id="form_formateurs_ajout_site" required>
                                <option value="0" selected disabled>Choisir un site</option>
                                <?php foreach (recupererSites() as $site) { ?>
                                    <option value="<?= $site['site_id'] ?>"><?= $site['site_libelle'] ?></option>
                                <?php } ?>
                            </select>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-warning" data-bs-dismiss="modal">Fermer</button>
                <button type="submit" class="btn btn-success" onclick="addTrainer();">Enregistrer</button>
            </div>
        </div>
    </div>
</div>

<div class="modal modal-xl fade" id="modalAddTrainee" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="modalAddTraineeTitle" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalAddTraineeTitle">Ajouter un·e stagiaire</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="#" method="post" id="form_add_trainee">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="form_stagiaires_ajout_nom" class="form-label">NOM:<span class="required">*</span></label>
                            <input type="text" class="form-control" placeholder="NOM" name="form_stagiaires_ajout_nom" id="form_stagiaires_ajout_nom" required>
                        </div>
                        <div class="col-md-6">
                            <label for="form_stagiaires_ajout_prenom" class="form-label">Prénom:<span class="required">*</span></label>
                            <input type="text" class="form-control" placeholder="Prénom" name="form_stagiaires_ajout_prenom" id="form_stagiaires_ajout_prenom" required>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <label for="form_stagiaires_ajout_email" class="form-label">Email:<span class="required">*</span></label>
                            <input type="text" class="form-control" placeholder="Email" name="form_stagiaires_ajout_email" id="form_stagiaires_ajout_email" required>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="form_stagiaires_ajout_dob" class="form-label">Date d'anniversaire:</label>
                            <input type="date" class="form-control" placeholder="Date d'anniversaire" name="form_stagiaires_ajout_dob" id="form_stagiaires_ajout_dob">
                        </div>
                        <div class="col-md-6">
                            <label for="form_stagiaires_ajout_session" class="form-label">Session:<span class="required">*</span></label>
                            <select class="form-control" name="form_stagiaires_ajout_session" id="form_stagiaires_ajout_session" required>
                                <option value="0" selected disabled>Choisir une session</option>
                                <?php foreach (recupererSessions() as $session) { ?>
                                    <option value="<?= $session['session_id'] ?>"><?= $session['session_nom'] ?></option>
                                <?php } ?>
                            </select>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-warning" data-bs-dismiss="modal">Fermer</button>
                <button type="submit" class="btn btn-success" onclick="addTrainee();">Enregistrer</button>
            </div>
        </div>
    </div>
</div>

<div class="modal modal-xl fade" id="modalAddSession" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="modalAddSessionTitle" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalAddSessionTitle">Ajouter une session</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="#" method="post" id="form_add_session">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="form_session_ajout_nom" class="form-label">Nom:<span class="required">*</span></label>
                            <input type="text" class="form-control" placeholder="Nom" name="form_session_ajout_nom" id="form_session_ajout_nom" required>
                        </div>
                        <div class="col-md-6 text-center">
                            <label for="preview-blason"><i>Preview</i> blason</label>
                            <div><img src="#" alt="Preview du blason de la session" id="preview-blason" style="max-height:20vh;width:auto;"></div>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="form_session_ajout_sigle" class="form-label">Sigle:<span class="required">*</span></label>
                            <input type="text" class="form-control" placeholder="Sigle" name="form_session_ajout_sigle" id="form_session_ajout_sigle" required>
                        </div>
                        <div class="col-md-6">
                            <label for="form_session_ajout_blason" class="form-label">Blason:</label>
                            <input type="file" class="form-control" placeholder="Blason" name="form_session_ajout_blason" id="form_session_ajout_blason">
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-3">
                            <label for="form_session_ajout_date_debut" class="form-label">Date de début:<span class="required">*</span></label>
                            <input type="date" class="form-control" placeholder="Date de début" name="form_session_ajout_date_debut" id="form_session_ajout_date_debut" required>
                        </div>
                        <div class="col-md-3">
                            <label for="form_session_ajout_date_fin" class="form-label">Date de fin:<span class="required">*</span></label>
                            <input type="date" class="form-control" placeholder="Date de fin" name="form_session_ajout_date_fin" id="form_session_ajout_date_fin" required>
                        </div>
                        <div class="col-md-6">
                            <label for="form_session_ajout_referent" class="form-label">Formateur référent:<span class="required">*</span></label>
                            <select class="form-control" name="form_session_ajout_referent" id="form_session_ajout_referent" required>
                                <option value="0" selected disabled>Choisir un·e référent·e</option>
                                <?php foreach (recupererFormateurs() as $formateur) { ?>
                                    <option value="<?= $formateur['formateur_id'] ?>"><?= $formateur['formateur_prenom'] . "&nbsp;" . $formateur['formateur_nom'] ?></option>
                                <?php } ?>
                            </select>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-warning" data-bs-dismiss="modal">Fermer</button>
                <button type="submit" class="btn btn-success" onclick="addSession();">Enregistrer</button>
            </div>
        </div>
    </div>
</div>

<div class="modal modal-xl fade" id="modalAddTutor" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="modalAddTutorTitle" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalAddTutorTitle">Ajouter un·e tuteur/trice</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="#" method="post" id="form_add_tutor">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="form_tuteur_ajout_nom" class="form-label">NOM:<span class="required">*</span></label>
                            <input type="text" class="form-control" placeholder="NOM" name="form_tuteur_ajout_nom" id="form_tuteur_ajout_nom" required>
                        </div>
                        <div class="col-md-6">
                            <label for="form_tuteur_ajout_prenom" class="form-label">Prénom:<span class="required">*</span></label>
                            <input type="text" class="form-control" placeholder="Prénom" name="form_tuteur_ajout_prenom" id="form_tuteur_ajout_prenom" required>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <label for="form_tuteur_ajout_email" class="form-label">Email:<span class="required">*</span></label>
                            <input type="text" class="form-control" placeholder="Email" name="form_tuteur_ajout_email" id="form_tuteur_ajout_email" required>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-3">
                            <label for="form_tuteur_ajout_adresse_rue" class="form-label">Rue:<span class="required">*</span></label>
                            <input type="text" class="form-control" placeholder="Rue" name="form_tuteur_ajout_adresse_rue" id="form_tuteur_ajout_adresse_rue">
                        </div>
                        <div class="col-md-3">
                            <label for="form_tuteur_ajout_adresse_cp" class="form-label">Code postal:<span class="required">*</span></label>
                            <input type="text" class="form-control" placeholder="Code postal" name="form_tuteur_ajout_adresse_cp" id="form_tuteur_ajout_adresse_cp">
                        </div>
                        <div class="col-md-3">
                            <label for="form_tuteur_ajout_adresse_ville" class="form-label">Ville:<span class="required">*</span></label>
                            <input type="text" class="form-control" placeholder="Ville" name="form_tuteur_ajout_adresse_ville" id="form_tuteur_ajout_adresse_ville">
                        </div>
                        <div class="col-md-3">
                            <label for="form_tuteur_ajout_adresse_pays" class="form-label">Pays:<span class="required">*</span></label>
                            <input type="text" class="form-control" placeholder="Pays" name="form_tuteur_ajout_adresse_pays" id="form_tuteur_ajout_adresse_pays">
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-warning" data-bs-dismiss="modal">Fermer</button>
                <button type="submit" class="btn btn-success" onclick="addTutor();">Enregistrer</button>
            </div>
        </div>
    </div>
</div>

<div class="modal modal-xl fade" id="modalAddCourse" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="modalAddCourseTitle" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalAddCourseTitle">Ajouter un cours</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="#" method="post">
                    <div class="mb-3">
                        <label for="form_cours_module" class="form-label">Nom du module:</label>
                        <input type="text" class="form-control" name="form_cours_module" id="form_cours_module" placeholder="html">
                    </div>
                    <div class="mb-3">
                        <label for="form_cours_title" class="form-label">Titre du cours:</label>
                        <input type="text" class="form-control" name="form_cours_title" id="form_cours_title" placeholder="HTML...">
                    </div>
                    <div class="mb-3">
                        <label for="form_cours_synopsis" class="form-label">Synopsis du cours:</label>
                        <textarea class="form-control" name="form_cours_synopsis" id="form_cours_synopsis" placeholder="Bref énoncé..." cols="" rows="5"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="form_cours_text" class="form-label">Enoncé du cours:</label>
                        <textarea class="form-control" name="form_cours_text" id="form_cours_text" placeholder="Énoncé..." cols="" rows="15"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="form_cours_keywords" class="form-label">Mots-clés du cours:</label>
                        <input type="text" class="form-control" name="form_cours_keywords" id="form_cours_keywords" placeholder="...">
                    </div>
                    <div class="mb-3">
                        <label for="form_cours_link" class="form-label">Lien de l'intégration du cours:</label>
                        <input type="text" class="form-control" name="form_cours_link" id="form_cours_link" placeholder="2PACX-...">
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="form_cours_active">
                        <label class="form-check-label" for="form_cours_active">
                            Cours visible ?
                        </label>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-warning" data-bs-dismiss="modal">Fermer</button>
                <button type="submit" class="btn btn-success" onclick="addCourse();">Enregistrer</button>
            </div>
        </div>
    </div>
</div>

<div class="modal modal-xl fade" id="modalManagecours" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="modalManagecoursTitle" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <div class="container">
                    <div class="row">
                        <h5 class="modal-title w-auto" id="modalManagecoursTitle">Gérer les cours</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        &nbsp;
                    </div>
                    <div class="row">
                        <div class="col-3">
                            <select name="form_session_cours" class="form-select" onchange="showModalManagecours();">
                                <option value="-1">Toutes mes sessions actives</option>
                                <option value="0">Toutes mes sessions</option>
                                <?php
                                $sql = "SELECT * 
                                        FROM sessions 
                                        WHERE id_formateur=:id_formateur
                                        ORDER BY session_nom;";
                                $req = $db->prepare($sql);
                                $req->bindValue(':id_formateur', filter_var($_SESSION['utilisateur']['formateur_id'], FILTER_VALIDATE_INT));
                                $req->execute();
                                $sessions_formateur = $req->fetchAll(PDO::FETCH_ASSOC);
                                foreach ($sessions_formateur as $session) { ?>
                                    <option value="<?= $session['session_id'] ?>"><?= $session['session_nom'] ?></option>
                                <?php } ?>
                            </select>
                        </div>
                        <div class="col-3">
                            <label for="form_active_cours">
                                <input type="checkbox" name="form_active_cours" id="form_active_cours" onchange="showModalManagecours();">
                                Cours non actifs / actifs
                            </label>
                        </div>
                        <div class="col">
                            <input type="text" name="form_search_cours" placeholder="Cours, mot-clé, ..." class="form-control" onkeyup="showModalManagecours();">
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-body">
            </div>
            <!-- <div class="modal-footer">
                <div class="d-flex">
                    <button type="button" class="btn btn-light">Précédent</button>
                    <button type="button" class="btn btn-light">Suivant</button>
                </div>
            </div> -->
        </div>
    </div>
</div>

<div class="modal modal-xl fade" id="modalAddQandA" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="modalAddQandATitle" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <div class="container">
                    <div class="row">
                        <h5 class="modal-title w-auto" id="modalQandATitle">Ajouter une FAQ</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        &nbsp;
                    </div>
                </div>
            </div>
            <div class="modal-body">
                <div class="row">
                    <form class="" action="//<?= $_SERVER["SERVER_NAME"] ?>/erp/src/c/requests.php" method="post" id="formAddFaq">
                        <div class="form-group">
                            <label for="form_faq_theme">Thème de la FAQ (25 caractères max)</label>
                            <input type="text" class="form-control" name="form_faq_theme" id="form_faq_theme" placeholder="Thème de la FAQ (laisser vide si `global`)">
                        </div>
                        <div class="form-group pt-2">
                            <label for="form_faq_title">Titre de la FAQ</label>
                            <input type="text" class="form-control" name="form_faq_title" id="form_faq_title" placeholder="Titre de la FAQ">
                        </div>
                        <div class="form-group pt-2 pb-2">
                            <label for="form_faq_content">Contenu de la FAQ</label>
                            <textarea class="form-control" name="form_faq_content" id="form_faq_content"></textarea>
                        </div>
                        <div class="form-group pb-2">
                            <label for="form_faq_secteur">Secteur</label>
                            <select class="form-select" name="form_faq_secteur" id="form_faq_secteur">
                                <option value="-1">Tous les secteurs</option>
                                <?php foreach ($secteurs as $secteur) { ?>
                                    <option value="<?= $secteur['secteur_id'] ?>"><?= $secteur['secteur_nom'] ?></option>
                                <?php } ?>
                            </select>
                        </div>
                    </form>
                </div>
            </div>
            <div class="modal-footer">
                <div class="d-flex">
                    <button type="button" class="btn btn-warning" data-bs-dismiss="modal">Fermer</button>&nbsp;
                    <button role="button" type="submit" class="btn btn-primary" form="formAddFaq">Envoyer</button>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal modal-xl fade" id="modalInternshipPeriod" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="modalInternshipPeriodTitle" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <div class="container">
                    <div class="row">
                        <h5 class="modal-title w-auto" id="modalInternshipPeriodTitle">Vérifier les périodes de stages de la session</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        &nbsp;
                    </div>
                </div>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-12">
                        <table class="table table-bordered" id="table-internships-periods">
                            <thead>
                                <tr>
                                    <th scope="col">Intitulé</th>
                                    <th scope="col">Date de début</th>
                                    <th scope="col">Date de fin</th>
                                </tr>
                            </thead>
                            <tbody>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <div class="d-flex">
                    <button type="button" class="btn btn-warning" data-bs-dismiss="modal">Fermer</button>&nbsp;
                    <button role="button" type="submit" class="btn btn-primary" form="formAddFaq">Envoyer</button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- <div class="modal modal-xl fade" id="modalAddQuiz" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="modalAddQuizTitle" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalAddQuizTitle">Ajouter un quiz</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="//<?= $_SERVER["SERVER_NAME"] ?>/erp/src/c/requests.php" method="post" id="form_add_quiz">
                    <div class="mb-3">
                        <label for="form_quiz_module" class="form-label">Nom du module:</label>
                        <input type="text" class="form-control" name="form_quiz_module" placeholder="html">
                    </div>
                    <div class="mb-3">
                        <label for="form_quiz_title" class="form-label">Titre du quiz:</label>
                        <input type="text" class="form-control" name="form_quiz_title" placeholder="HTML...">
                    </div>
                    <div class="mb-3">
                        <label for="form_quiz_keywords" class="form-label">Mots-clés du quiz:</label>
                        <input type="text" class="form-control" name="form_quiz_keywords" placeholder="...">
                    </div>
                    <div class="mb-3">
                        <label for="form_quiz_lien" class="form-label">Lien de l'intégration du quiz:</label>
                        <input type="text" class="form-control" name="form_quiz_lien" placeholder="<?= sha1(random_bytes(6)) ?>...">
                    </div>
                    <div class="mb-3">
                        <label for="form_quiz_difficulte" class="form-label">Difficulté du quiz:</label>
                        <select name="form_quiz_difficulte" id="form_quiz_difficulte">
                            <option value="0">Facile</option>
                            <option value="1">Modéré</option>
                            <option value="2">Difficile</option>
                            <option value="3">Extrême</option>
                        </select>
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="form_quiz_active">
                        <label class="form-check-label" for="form_quiz_active">
                            Cours visible ?
                        </label>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-warning" data-bs-dismiss="modal">Fermer</button>
                <button type="submit" class="btn btn-success" name="add_quiz" form="form_add_quiz" value="1">Enregistrer</button>
            </div>
        </div>
    </div>
</div>


<div class="modal modal-lg fade" id="modalManageQuiz" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="modalManageQuizTitle" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalManageQuizTitle">Gérer les quiz</h5>
                &nbsp;
                <select name="form_session_quiz" onchange="showModalManageQuiz();">
                    <option value="0">Toutes mes sessions</option>
                    <?php
                    $sql = "SELECT * 
                                FROM sessions 
                                WHERE id_formateur=:id_formateur
                                ORDER BY session_nom;";
                    $req = $db->prepare($sql);
                    $req->bindValue(':id_formateur', filter_var($_SESSION['utilisateur']['formateur_id'], FILTER_VALIDATE_INT));
                    $req->execute();
                    $sessions_formateur = $req->fetchAll(PDO::FETCH_ASSOC);
                    foreach ($sessions_formateur as $session) { ?>
                        <option value="<?= $session['session_id'] ?>"><?= $session['session_nom'] ?></option>
                    <?php } ?>
                </select>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
            </div>
        </div>
    </div>
</div> -->

<?php
include_once("./js.php"); ?>
<!-- TinyMCE CDN -->
<script src="https://cdn.tiny.cloud/1/a8r1baghsc0tvoi3mea8smbftcwaas0dv5duyputb14spmju/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
<script>
    tinymce.init({
        selector: 'textarea#form_faq_content',
        skin: 'bootstrap',
        plugins: 'lists, link, image, media',
        toolbar: 'h3 h4 h5 h6 bold italic strikethrough blockquote bullist numlist backcolor | link image media | removeformat help',
        // menubar: false,
        setup: (editor) => {
            // Apply the focus effect
            editor.on("init", () => {
                editor.getContainer().style.transition = "border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out";
            });
            editor.on("focus", () => {
                (editor.getContainer().style.boxShadow = "0 0 0 .2rem rgba(0, 123, 255, .25)"),
                (editor.getContainer().style.borderColor = "#80bdff");
            });
            editor.on("blur", () => {
                (editor.getContainer().style.boxShadow = ""),
                (editor.getContainer().style.borderColor = "");
            });
        },
    });
</script>
<script>
    sessionStorage.setItem("previous_uri", "<?= $_SERVER["REQUEST_URI"] ?>");

    getListCourses();

    function getListCourses() {
        $.ajax({
            url: "//<?= $_SERVER["SERVER_NAME"] ?>/erp/src/c/requests.php",
            method: "post",
            data: {
                get_list_courses: 1,
                id_session: document.querySelector('#form_filter_session').value
            },
            beforeSend: function() {
                $("#table-cours tbody").html('<tr><td colspan="6"><div class="spinner-border spinner-border-sm" role="status"><span class="sr-only">Chargement...</span></div>&nbsp;<span>Chargement...</span></td></tr>');
            },
            success: function(r) {
                $("#table-cours tbody").html(r);

                new DataTable('#table-cours', {
                    "language": {
                        "url": '//cdn.datatables.net/plug-ins/2.0.5/i18n/fr-FR.json',
                    },
                    "scrollX": true,
                    "autoWidth": false,
                    "info": false,
                    "JQueryUI": true,
                    "ordering": true,
                    "retrieve": true,
                    "scrollCollapse": true
                });
            }
        });
    }

    function getListTrainers() {
        $.ajax({
            url: "//<?= $_SERVER["SERVER_NAME"] ?>/erp/src/c/requests.php",
            method: "post",
            data: {
                get_list_trainers: 1,
                id_session: document.querySelector('#form_filter_session').value
            },
            beforeSend: function() {
                $("#table-formateurs tbody").html('<tr><td colspan="6"><div class="spinner-border spinner-border-sm" role="status"><span class="sr-only">Chargement...</span></div>&nbsp;<span>Chargement...</span></td></tr>');
            },
            success: function(r) {
                $("#table-formateurs tbody").html(r);

                new DataTable('#table-formateurs', {
                    "language": {
                        "url": '//cdn.datatables.net/plug-ins/2.0.5/i18n/fr-FR.json',
                    },
                    "scrollX": true,
                    "autoWidth": false,
                    "info": false,
                    "JQueryUI": true,
                    "ordering": true,
                    "retrieve": true,
                    "scrollCollapse": true
                });
            }
        });
    }

    function getListSessions() {
        $.ajax({
            url: "//<?= $_SERVER["SERVER_NAME"] ?>/erp/src/c/requests.php",
            method: "post",
            data: {
                get_list_sessions: 1,
                id_session: document.querySelector('#form_filter_session').value
            },
            beforeSend: function() {
                $("#table-sessions tbody").html('<tr><td colspan="6"><div class="spinner-border spinner-border-sm" role="status"><span class="sr-only">Chargement...</span></div>&nbsp;<span>Chargement...</span></td></tr>');
            },
            success: function(r) {
                $("#table-sessions tbody").html(r);

                new DataTable('#table-sessions', {
                    "language": {
                        "url": '//cdn.datatables.net/plug-ins/2.0.5/i18n/fr-FR.json',
                    },
                    "scrollX": true,
                    "autoWidth": false,
                    "info": false,
                    "JQueryUI": true,
                    "ordering": true,
                    "retrieve": true,
                    "scrollCollapse": true
                });
            }
        });
    }

    function getListTrainees() {
        $.ajax({
            url: "//<?= $_SERVER["SERVER_NAME"] ?>/erp/src/c/requests.php",
            method: "post",
            data: {
                get_list_trainees: 1,
                id_session: document.querySelector('#form_filter_session').value
            },
            beforeSend: function() {
                $("#table-stagiaires tbody").html('<tr><td colspan="6"><div class="spinner-border spinner-border-sm" role="status"><span class="sr-only">Chargement...</span></div>&nbsp;<span>Chargement...</span></td></tr>');
            },
            success: function(r) {
                $("#table-stagiaires tbody").html(r);

                new DataTable('#table-stagiaires', {
                    "language": {
                        "url": '//cdn.datatables.net/plug-ins/2.0.5/i18n/fr-FR.json',
                    },
                    "scrollX": true,
                    "autoWidth": false,
                    "info": false,
                    "JQueryUI": true,
                    "ordering": true,
                    "retrieve": true,
                    "scrollCollapse": true
                });
            }
        });
    }

    function getListExercises() {
        $.ajax({
            url: "//<?= $_SERVER["SERVER_NAME"] ?>/erp/src/c/requests.php",
            method: "post",
            data: {
                get_list_exercises: 1,
                id_session: document.querySelector('#form_filter_session').value
            },
            beforeSend: function() {
                $("#table-exercices tbody").html('<tr><td colspan="3"><div class="spinner-border spinner-border-sm" role="status"><span class="sr-only">Chargement...</span></div>&nbsp;<span>Chargement...</span></td></tr>');
            },
            success: function(r) {
                $("#table-exercices tbody").html(r);

                new DataTable('#table-exercices', {
                    "language": {
                        "url": '//cdn.datatables.net/plug-ins/2.0.5/i18n/fr-FR.json',
                    },
                    "scrollX": true,
                    "autoWidth": false,
                    "info": false,
                    "JQueryUI": true,
                    "ordering": true,
                    "retrieve": true,
                    "scrollCollapse": true
                });
            }
        });
    }

    function getListInterships() {
        $.ajax({
            url: "//<?= $_SERVER["SERVER_NAME"] ?>/erp/src/c/requests.php",
            method: "post",
            data: {
                get_list_internships: 1,
                id_session: document.querySelector('#form_filter_session').value
            },
            beforeSend: function() {
                $("#table-stages tbody").html('<tr><td colspan="6"><div class="spinner-border spinner-border-sm" role="status"><span class="sr-only">Chargement...</span></div>&nbsp;<span>Chargement...</span></td></tr>');
            },
            success: function(r) {
                $("#table-stages tbody").html(r);

                new DataTable('#table-stages', {
                    "language": {
                        "url": '//cdn.datatables.net/plug-ins/2.0.5/i18n/fr-FR.json',
                    },
                    "scrollX": true,
                    "autoWidth": false,
                    "info": false,
                    "JQueryUI": true,
                    "ordering": true,
                    "retrieve": true,
                    "scrollCollapse": true
                });
            }
        });
    }

    function getListIntershipsPeriods(id_session) {
        $.ajax({
            url: "//<?= $_SERVER["SERVER_NAME"] ?>/erp/src/c/requests.php",
            method: "post",
            data: {
                get_list_internships_periods: 1,
                id_session: id_session
            },
            beforeSend: function() {
                $("#table-internships-periods tbody").html('<tr><td colspan="3"><div class="spinner-border spinner-border-sm" role="status"><span class="sr-only">Chargement...</span></div>&nbsp;<span>Chargement...</span></td></tr>');
            },
            success: function(r) {
                $("#table-internships-periods tbody").html(r);

                new DataTable('#table-internships-periods', {
                    "language": {
                        "url": '//cdn.datatables.net/plug-ins/2.0.5/i18n/fr-FR.json',
                    },
                    "scrollX": true,
                    "autoWidth": false,
                    "info": false,
                    "JQueryUI": true,
                    "ordering": true,
                    "retrieve": true,
                    "scrollCollapse": true
                });
            }
        });
    }

    function getListFaqs() {
        $.ajax({
            url: "//<?= $_SERVER["SERVER_NAME"] ?>/erp/src/c/requests.php",
            method: "post",
            data: {
                get_list_faqs: 1,
                id_session: document.querySelector('#form_filter_session').value
            },
            beforeSend: function() {
                $("#table-faqs tbody").html('<tr><td colspan="6"><div class="spinner-border spinner-border-sm" role="status"><span class="sr-only">Chargement...</span></div>&nbsp;<span>Chargement...</span></td></tr>');
            },
            success: function(r) {
                $("#table-faqs tbody").html(r);

                new DataTable('#table-faqs', {
                    "language": {
                        "url": '//cdn.datatables.net/plug-ins/2.0.5/i18n/fr-FR.json',
                    },
                    "scrollX": true,
                    "autoWidth": false,
                    "info": false,
                    "JQueryUI": true,
                    "ordering": true,
                    "retrieve": true,
                    "scrollCollapse": true
                });
            }
        });
    }

    let filtre = document.getElementById('form_filter_session');
    filtre.onchange = () => {
        getStats();
    };

    function getStats() {
        getStatsConvention();
        getStatsAttestation();
        getStatsEvaluation();
        getStatsPresence();
        getStatsReussite();
        getStatsSatisfaction();
    }

    function getStatsConvention() {
        $.ajax({
            url: "//<?= $_SERVER["SERVER_NAME"] ?>/erp/src/c/requests.php",
            method: "post",
            dataType: "json",
            data: {
                get_stats_convention: 1,
                id_session: document.querySelector('#form_filter_session').value
            },
            beforeSend: function() {
                $('#ratio_valeur_stage_convention').text("XXX%");
                $('#ratio_valeur_stage_convention').html('<div class="spinner-border spinner-border-sm" role="status"><span class="sr-only">Chargement...</span></div>');
                $('#ratio_valeur_stage_convention').removeClass("col-good");
                $('#ratio_valeur_stage_convention').removeClass("col-medium");
                $('#ratio_valeur_stage_convention').removeClass("col-bad");
                $('#ratio_libelle_stage_convention').text("");
            },
            success: function(r) {
                $('#ratio_valeur_stage_convention').text(r.value);
                $('#ratio_valeur_stage_convention').addClass(r.color);
                $('#ratio_libelle_stage_convention').text(r.label);
            }
        });
    }

    function getStatsAttestation() {
        $.ajax({
            url: "//<?= $_SERVER["SERVER_NAME"] ?>/erp/src/c/requests.php",
            method: "post",
            dataType: "json",
            data: {
                get_stats_attestation: 1,
                id_session: document.querySelector('#form_filter_session').value
            },
            beforeSend: function() {
                $('#ratio_valeur_stage_attestation').text("XXX%");
                $('#ratio_valeur_stage_attestation').html('<div class="spinner-border spinner-border-sm" role="status"><span class="sr-only">Chargement...</span></div>');
                $('#ratio_valeur_stage_attestation').removeClass("col-good");
                $('#ratio_valeur_stage_attestation').removeClass("col-medium");
                $('#ratio_valeur_stage_attestation').removeClass("col-bad");
                $('#ratio_libelle_stage_attestation').text("");
            },
            success: function(r) {
                $('#ratio_valeur_stage_attestation').text(r.value);
                $('#ratio_valeur_stage_attestation').addClass(r.color);
                $('#ratio_libelle_stage_attestation').text(r.label);
            }
        });
    }

    function getStatsEvaluation() {
        $.ajax({
            url: "//<?= $_SERVER["SERVER_NAME"] ?>/erp/src/c/requests.php",
            method: "post",
            dataType: "json",
            data: {
                get_stats_evaluation: 1,
                id_session: document.querySelector('#form_filter_session').value
            },
            beforeSend: function() {
                $('#ratio_valeur_stage_evaluation').text("XXX%");
                $('#ratio_valeur_stage_evaluation').html('<div class="spinner-border spinner-border-sm" role="status"><span class="sr-only">Chargement...</span></div>');
                $('#ratio_valeur_stage_evaluation').removeClass("col-good");
                $('#ratio_valeur_stage_evaluation').removeClass("col-medium");
                $('#ratio_valeur_stage_evaluation').removeClass("col-bad");
                $('#ratio_libelle_stage_evaluation').text("");
            },
            success: function(r) {
                $('#ratio_valeur_stage_evaluation').text(r.value);
                $('#ratio_valeur_stage_evaluation').addClass(r.color);
                $('#ratio_libelle_stage_evaluation').text(r.label);
            }
        });
    }

    function getStatsPresence() {
        $.ajax({
            url: "//<?= $_SERVER["SERVER_NAME"] ?>/erp/src/c/requests.php",
            method: "post",
            dataType: "json",
            data: {
                get_stats_presence: 1,
                id_session: document.querySelector('#form_filter_session').value
            },
            beforeSend: function() {
                $('#ratio_valeur_stage_presence').text("XXX%");
                $('#ratio_valeur_stage_presence').html('<div class="spinner-border spinner-border-sm" role="status"><span class="sr-only">Chargement...</span></div>');
                $('#ratio_valeur_stage_presence').removeClass("col-good");
                $('#ratio_valeur_stage_presence').removeClass("col-medium");
                $('#ratio_valeur_stage_presence').removeClass("col-bad");
                $('#ratio_libelle_stage_presence').text("");
            },
            success: function(r) {
                $('#ratio_valeur_stage_presence').text(r.value);
                $('#ratio_valeur_stage_presence').addClass(r.color);
                $('#ratio_libelle_stage_presence').text(r.label);
            }
        });
    }

    function getStatsReussite() {
        $.ajax({
            url: "//<?= $_SERVER["SERVER_NAME"] ?>/erp/src/c/requests.php",
            method: "post",
            dataType: "json",
            data: {
                get_stats_reussite: 1,
                id_session: document.querySelector('#form_filter_session').value
            },
            beforeSend: function() {
                $('#ratio_valeur_session_reussite').text("XXX%");
                $('#ratio_valeur_session_reussite').html('<div class="spinner-border spinner-border-sm" role="status"><span class="sr-only">Chargement...</span></div>');
                $('#ratio_valeur_session_reussite').removeClass("col-good");
                $('#ratio_valeur_session_reussite').removeClass("col-medium");
                $('#ratio_valeur_session_reussite').removeClass("col-bad");
                $('#ratio_libelle_session_reussite').text("");
            },
            success: function(r) {
                $('#ratio_valeur_session_reussite').text(r.value);
                $('#ratio_valeur_session_reussite').addClass(r.color);
                $('#ratio_libelle_session_reussite').text(r.label);
            }
        });
    }

    function getStatsSatisfaction() {
        $.ajax({
            url: "//<?= $_SERVER["SERVER_NAME"] ?>/erp/src/c/requests.php",
            method: "post",
            dataType: "json",
            data: {
                get_stats_satisfaction: 1,
                id_session: document.querySelector('#form_filter_session').value
            },
            beforeSend: function() {
                $('#ratio_valeur_session_satisfaction').text("XXX");
                $('#ratio_valeur_session_satisfaction').html('<div class="spinner-border spinner-border-sm" role="status"><span class="sr-only">Chargement...</span></div>');
                $('#ratio_valeur_session_satisfaction').removeClass("col-good");
                $('#ratio_valeur_session_satisfaction').removeClass("col-medium");
                $('#ratio_valeur_session_satisfaction').removeClass("col-bad");
                $('#ratio_libelle_session_satisfaction').text("");
            },
            success: function(r) {
                $('#ratio_valeur_session_satisfaction').text(r.value);
                $('#ratio_valeur_session_satisfaction').addClass(r.color);
                $('#ratio_libelle_session_satisfaction').text(r.label);
            }
        });
    }

    function validInternCorrection(id_stagiaire, tp, operation) {
        var errors_found = $("#errors_found_plus_one_" + tp + "_" + id_stagiaire).attr("value");
        if (operation === 'minus') errors_found--;
        if (operation === 'plus') errors_found++;

        $.ajax({
            url: "//<?= $_SERVER["SERVER_NAME"] ?>/erp/src/c/requests.php",
            method: "post",
            data: {
                valid_stagiaire_correction: 1,
                tp: tp,
                errors_found: errors_found,
                id_stagiaire: id_stagiaire
            },
            success: function(r) {
                $("#errors_found_plus_one_" + tp + "_" + id_stagiaire).attr("value", errors_found);
                $("#errors_found_plus_one_" + tp + "_" + id_stagiaire).text(errors_found);
            }
        });
    }

    function showModalManagecours() {
        // if(document.querySelector('input[name="form_search_cours"]').value !== "") {
        $.ajax({
            url: "//<?= $_SERVER["SERVER_NAME"] ?>/erp/src/c/requests.php",
            method: "post",
            data: {
                show_modal_manage_cours: 1,
                cours_actifs: document.querySelector('input[name="form_active_cours"]').checked,
                id_session: document.querySelector('select[name="form_session_cours"]').value,
                search: document.querySelector('input[name="form_search_cours"]').value
            },
            success: function(r) {
                $("#modalManagecours .modal-content .modal-body").html(r);
            }
        });
        // } else {
        //     $("#modalManagecours .modal-content .modal-body").html("<p>Effectuez une recherche (mot-clé ou titre)...</p>");
        // }
    }

    function showModalManageQuiz() {
        $.ajax({
            url: "//<?= $_SERVER["SERVER_NAME"] ?>/erp/src/c/requests.php",
            method: "post",
            data: {
                show_modal_manage_quiz: 1,
                id_session: document.querySelector('select[name="form_session_quiz"]').value
            },
            success: function(r) {
                $("#modalManageQuiz .modal-content .modal-body").html(r);
            }
        });
    }

    function updateStatusCourse(cours_id, id_session) {
        var cours_active = ($("#cours_" + cours_id + "_" + id_session + " span > span").hasClass("cours-active") ? 0 : 1);

        $.ajax({
            url: "//<?= $_SERVER["SERVER_NAME"] ?>/erp/src/c/requests.php",
            method: "post",
            data: {
                update_status_cours: 1,
                cours_id: cours_id,
                id_session: id_session,
                cours_active: cours_active
            },
            success: function(r) {
                if (r == "ok") {
                    $("#cours_" + cours_id + "_" + id_session + " span > span").toggleClass("cours-active cours-inactive");
                }
            }
        });
    }

    function updateStatusQuiz(quiz_id, id_session) {
        var quiz_active = ($("#quiz_" + quiz_id + "_" + id_session + " span > span").hasClass("quiz-active") ? 0 : 1);

        $.ajax({
            url: "//<?= $_SERVER["SERVER_NAME"] ?>/erp/src/c/requests.php",
            method: "post",
            data: {
                update_status_quiz: 1,
                quiz_id: quiz_id,
                id_session: id_session,
                quiz_active: quiz_active
            },
            success: function(r) {
                if (r == "ok") {
                    $("#quiz_" + quiz_id + "_" + id_session + " span > span").toggleClass("quiz-active quiz-inactive");
                }
            }
        });
    }

    let uploadBlason = document.getElementById('form_session_ajout_blason');
    let previewBlason = document.getElementById('preview-blason');
    uploadBlason.onchange = evt => {
        const [file] = uploadBlason.files
        if (file) {
            previewBlason.src = URL.createObjectURL(file)
        }
    };

    function addSession() {
        let donnees = new FormData();
        donnees.append("form_session_ajout", 1);
        donnees.append("form_session_ajout_nom", $("#form_session_ajout_nom").val());
        donnees.append("form_session_ajout_sigle", $("#form_session_ajout_sigle").val());
        donnees.append("form_session_ajout_date_debut", $("#form_session_ajout_date_debut").val());
        donnees.append("form_session_ajout_date_fin", $("#form_session_ajout_date_fin").val());
        donnees.append("form_session_ajout_blason", document.getElementById('form_session_ajout_blason').files[0]);
        donnees.append("id_formateur", $("#form_session_ajout_referent").val());

        $.ajax({
            url: "//" + SERVER_NAME + "/erp/src/c/c_requetes.php",
            method: "post",
            contentType: false,
            processData: false,
            data: donnees,
            success: function(r) {
                document.getElementById("form_add_session").reset();
                $("#modalAddSession").modal("hide");
            }
        });
    }

    function addTutor() {
        $.ajax({
            url: "//" + SERVER_NAME + "/erp/src/c/c_requetes.php",
            method: "post",
            data: {
                form_tuteur_ajout: 1,
                form_tuteur_ajout_nom: $("#form_tuteur_ajout_nom").val(),
                form_tuteur_ajout_prenom: $("#form_tuteur_ajout_prenom").val(),
                form_tuteur_ajout_email: $("#form_tuteur_ajout_email").val(),
                form_tuteur_ajout_adresse_rue: $("#form_tuteur_ajout_adresse_rue").val(),
                form_tuteur_ajout_adresse_cp: $("#form_tuteur_ajout_adresse_cp").val(),
                form_tuteur_ajout_adresse_ville: $("#form_tuteur_ajout_adresse_ville").val(),
                form_tuteur_ajout_adresse_pays: $("#form_tuteur_ajout_adresse_pays").val()
            },
            success: function(r) {
                $("#modalAddTutor #form_add_tutor").reset();
                $("#modalAddTutor").modal("hide");
            }
        });
    }

    function addTrainer() {
        $.ajax({
            url: "//" + SERVER_NAME + "/erp/src/c/c_requetes.php",
            method: "post",
            data: {
                form_formateurs_ajout: 1,
                form_formateurs_ajout_nom: $("#form_formateurs_ajout_nom").val(),
                form_formateurs_ajout_prenom: $("#form_formateurs_ajout_prenom").val(),
                form_formateurs_ajout_mail: $("#form_formateurs_ajout_mail").val(),
                form_formateurs_ajout_role: $("#form_formateurs_ajout_role").val(),
                form_formateurs_ajout_liens: $("#form_formateurs_ajout_liens").val(),
                form_formateurs_ajout_telephone: $("#form_formateurs_ajout_telephone").val(),
                form_formateurs_ajout_portable: $("#form_formateurs_ajout_portable").val(),
                id_secteur: $("#form_formateurs_ajout_secteur").val(),
                id_site: $("#form_formateurs_ajout_site").val(),
            },
            success: function(r) {
                $("#modalAddTrainer #form_add_trainer").reset();
                $("#modalAddTrainer").modal("hide");
            }
        });
    }

    function addTrainee() {
        $.ajax({
            url: "//" + SERVER_NAME + "/erp/src/c/c_requetes.php",
            method: "post",
            data: {
                form_stagiaires_ajout: 1,
                form_stagiaires_ajout_nom: $("#form_stagiaires_ajout_nom").val(),
                form_stagiaires_ajout_prenom: $("#form_stagiaires_ajout_prenom").val(),
                form_stagiaires_ajout_email: $("#form_stagiaires_ajout_email").val(),
                form_stagiaires_ajout_dob: $("#form_stagiaires_ajout_dob").val(),
                id_session: $("#form_stagiaires_ajout_session").val(),
            },
            success: function(r) {
                $("#modalAddTrainee #form_add_trainee").reset();
                $("#modalAddTrainee").modal("hide");
            }
        });
    }

    function addCourse() {
        $.ajax({
            url: "//" + SERVER_NAME + "/erp/src/c/requests.php",
            method: "post",
            data: {
                add_cours: 1,
                form_cours_module: $("#form_cours_module").val(),
                form_cours_title: $("#form_cours_title").val(),
                form_cours_synopsis: $("#form_cours_synopsis").val(),
                form_cours_text: $("#form_cours_text").val(),
                form_cours_keywords: $("#form_cours_keywords").val(),
                form_cours_link: $("#form_cours_link").val(),
                form_cours_active: $("#form_cours_active").is(":checked"),
            },
            success: function(r) {
                $("#modalCourse .modal-content").html("");
                $("#modalCourse").modal("hide");
            }
        });
    }
</script>
<?php
include_once("./footer.php"); ?>