<?php
include_once("../../src/m/connect.php");

$title = " | Mon profil";
$form = "";

if (isset($_SESSION['utilisateur']['formateur_id']) && $_SESSION['utilisateur']['formateur_id'] > 0) {
    $sql = "SELECT formateur_nom, formateur_prenom, formateur_mail, formateur_signature, carte_formateur_tel, avatar_id, avatar_nom, avatar_lien
            FROM formateurs 
            JOIN avatars ON (avatar_id = id_avatar)
            WHERE formateur_id=:id_formateur;";
    $req = $db->prepare($sql);
    $req->bindValue(':id_formateur', $_SESSION['utilisateur']['formateur_id']);
    $req->execute();
    $formateur = $req->fetch(PDO::FETCH_ASSOC);
    $sql = "SELECT avatar_id, avatar_nom, avatar_lien
            FROM avatars 
            ORDER BY avatar_nom;";
    $req = $db->prepare($sql);
    $req->execute();
    $avatars = "";
    foreach ($req->fetchAll(PDO::FETCH_ASSOC) as $avatar) {
        $avatars .= '<img onclick="changerAvatar(this);"  data-id="' . $avatar['avatar_id'] . '" class="img-fluid rounded me-2 mb-5 img-preview' . ($avatar['avatar_id'] == $formateur['avatar_id'] ? ' img-selected' : '') . '" src="//' . $_SERVER["SERVER_NAME"] . '/erp/public/formation/imgs/avatars/' . $avatar['avatar_lien'] . '" alt="Avatar de type ' . $avatar['avatar_nom'] . '" >';
    }
    $form = '
        <div class="container">
            <div class="row">
                <form action="//' . $_SERVER["SERVER_NAME"] . '/erp/src/c/requests.php" enctype="multipart/form-data" method="post">
                    <div class="row">
                        <div class="col-12 text-center">
                            <input type="hidden" name="form_avatar_formateur" value="' . $formateur['avatar_id'] . '">
                            <img class="img-fluid rounded m-5 img-profile" src="//' . $_SERVER["SERVER_NAME"] . '/erp/public/formation/imgs/avatars/' . $formateur['avatar_lien'] . '" alt="Avatar de type ' . $formateur['avatar_nom'] . '" >
                        </div>
                        <div class="col-12 text-center">
                        ' . $avatars . '
                        </div>
                    </div>
                    <div class="row">
                        <div class="col">
                            <div class="form-group">
                                <label for="form_nom_formateur">NOM<span class="required">*</span></label>
                                <input type="text" class="form-control" name="form_nom_formateur" id="form_nom_formateur" placeholder="Nom utilisateur" value="' . $formateur['formateur_nom'] . '">
                            </div>
                        </div>
                        <div class="col">
                            <div class="form-group">
                                <label for="form_prenom_formateur">Prénom<span class="required">*</span></label>
                                <input type="text" class="form-control" name="form_prenom_formateur" id="form_prenom_formateur" placeholder="Prénom d\'utilisateur" value="' . $formateur['formateur_prenom'] . '">
                            </div>
                        </div>
                        <div class="col">
                            <label for="form_email_formateur">Email<span class="required">*</span></label>
                            <div class="form-group">
                                <div class="input-group">
                                    <input type="text" class="form-control" name="form_email_formateur" id="form_email_formateur" autocomplete="off" disabled placeholder="Prénom d\'utilisateur" value="' . explode('@adrar-', $formateur['formateur_mail'])[0] . '">
                                    <input type="text" class="form-control" disabled value="@' . explode('@', $formateur['formateur_mail'])[1] . '">
                                </div>
                            </div>
                        </div>
                        <div class="col">
                            <div class="form-group">
                                <label for="form_mdp_formateur">Mot de passe</label>
                                <input type="password" autocomplete="new-password" class="form-control" name="form_mdp_formateur" id="form_mdp_formateur" placeholder="Mot de passe utilisateur">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-4">
                            <div class="form-group">
                                <label for="form_signature_formateur">Signature:</label>
                                <input type="hidden" name="form_signature_formateur">
                                <div>
                                    <canvas class="box-signature" id="signature-pad" width="400" height="200" style="touch-action: none;"></canvas>
                                </div>
                            </div>
                            <span>
                                <button id="approve-signature" type="button">Approuver</button>
                                <button id="clear-signature" type="button">Effacer</button>
                            </span>
                        </div>
                        <div class="col-4">
                            <div class="form-group">
                                <label>Signature actuelle:</label>
                                <div>
                                    ' . (isset($formateur['formateur_signature']) && !empty($formateur['formateur_signature']) ? '<img class="box-signature" src="../../src/v/formateurs/' . $formateur['formateur_signature'] . '" alt="Signature du formateur" width="250" height="125">' : '<p>Pas de signature</p>') . '   
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row pt-2">
                        <div class="col-12 text-end">
                            <input type="submit" class="btn btn-dark" name="form_update_trainer" >
                        </div>
                    </div>
                </form>
            </div>
        </div>
    ';
} elseif (isset($_SESSION['utilisateur']['stagiaire_id']) && $_SESSION['utilisateur']['stagiaire_id'] > 0) {
    $informations_session = "";
    $sql = "SELECT * 
            FROM sessions 
            WHERE session_id=:id_session;";
    $req = $db->prepare($sql);
    $req->bindValue(':id_session', $_SESSION['utilisateur']['id_session'], PDO::PARAM_INT);
    $req->execute();
    $session = $req->fetch(PDO::FETCH_ASSOC);
    $stages = json_decode($session['session_stage'], true);
    $dates_stages = '<ul>';
    if (!empty($stages['stages'])) {
        foreach ($stages['stages'] as $stage) {
            $dates_stages .= '<li>' . $stage['libelle'] . '</li>';
            $dates_stages .= '  <ul>';
            $dates_stages .= '      <li>Début: ' . date('d/m/Y', strtotime($stage['date_debut'])) . '</li>';
            $dates_stages .= '      <li>Fin: ' . date('d/m/Y', strtotime($stage['date_fin'])) . '</li>';
            $dates_stages .= '  </ul>';
        }
    } else {
        $dates_stages .= '<li>Pas de dates définies</li>';
    }
    $dates_stages .= '</ul>';
    $informations_session = '<div class="row">
                                <div class="col-12">
                                    <h2>Informations de votre session</h2>
                                </div>
                                <div class="col-6">
                                    <p><b>Nom de la session:</b>&nbsp;' . $session['session_nom'] . '</p>
                                    <p><b>Sigle de la session:</b>&nbsp;' . $session['session_sigle'] . '</p>
                                    <p><b>Vos dates clés:</b>
                                        <ul>
                                            <li>Formation commencée le ' . date('d/m/Y', strtotime($session['session_date_debut'])) . '</li>
                                            <li>Formation se terminant le ' . date('d/m/Y', strtotime($session['session_date_fin'])) . '</li>
                                            <li>Stages</li>
                                            ' . $dates_stages . '
                                        </ul>
                                    </p>
                                </div>
                                <div class="col-6 text-center">
                                    <img src="./imgs/blasons/' . $session['session_blason'] . '" alt="Blason de la session" style="max-height:50vh;width:auto;"/>
                                    <p><b>Blason</b></p>
                                </div>
                            </div>';


    $sql = "SELECT sq.*, q.questionnaire_question_resume
            FROM stagiaires_questionnaires sq 
            INNER JOIN questionnaires q ON (q.questionnaire_id = sq.id_questionnaire)  
            WHERE id_stagiaire=:id_stagiaire;";
    $req = $db->prepare($sql);
    $req->bindValue(":id_stagiaire", filter_var($_SESSION['utilisateur']['stagiaire_id'], FILTER_VALIDATE_INT));
    $req->execute();
    $questionnaire_stagiaire = $req->fetchAll(PDO::FETCH_ASSOC);

    $questionnaire_stagiaire_remplis = "";
    if (!empty($questionnaire_stagiaire)) {
        $questionnaire_stagiaire_remplis .= '<div class="row my-4">';
        foreach ($questionnaire_stagiaire as $satisfaction) {
            $questionnaire_stagiaire_remplis .= '<div class="col-xs-1 col-md-3 col-lg-3 text-center">';
            $questionnaire_stagiaire_remplis .= '   <div title="' . (!empty($satisfaction['stagiaire_questionnaire_reponse']) ? $satisfaction['stagiaire_questionnaire_reponse'] : "Aucun commentaire laissé.") . '">';
            $img = "";
            $alt = "Introuvable";
            if ($satisfaction['stagiaire_questionnaire_note'] == 5.00) {
                $img = "tres_satisfait.png";
                $alt = "Très satisfait";
            } elseif ($satisfaction['stagiaire_questionnaire_note'] == 4.00) {
                $img = "satisfait.png";
                $alt = "Satisfait";
            } elseif ($satisfaction['stagiaire_questionnaire_note'] == 3.00) {
                $img = "neutre.png";
                $alt = "Neutre";
            } elseif ($satisfaction['stagiaire_questionnaire_note'] == 2.00) {
                $img = "insatisfait.png";
                $alt = "Insatisfait";
            } elseif ($satisfaction['stagiaire_questionnaire_note'] == 1.00) {
                $img = "tres_insatisfait.png";
                $alt = "Très insatisfait";
            }
            $questionnaire_stagiaire_remplis .= '       <img src="./imgs/' . $img . '" alt="' . $alt . '">';
            $questionnaire_stagiaire_remplis .= '       <p>' . $satisfaction['questionnaire_question_resume'] . '</p>';
            $questionnaire_stagiaire_remplis .= '   </div>';
            $questionnaire_stagiaire_remplis .= '</div>';
        }
        $questionnaire_stagiaire_remplis .= '</div>';
    }

    $sql = "SELECT stagiaire_nom, stagiaire_prenom, stagiaire_mail, stagiaire_pseudo, stagiaire_tel, stagiaire_date_naissance, avatar_id, avatar_nom, avatar_lien 
            FROM stagiaires 
            JOIN avatars ON (avatar_id = id_avatar)
            WHERE stagiaire_id=:id_stagiaire;";
    $req = $db->prepare($sql);
    $req->bindValue(':id_stagiaire', $_SESSION['utilisateur']['stagiaire_id']);
    $req->execute();
    $stagiaire = $req->fetch(PDO::FETCH_ASSOC);
    $sql = "SELECT avatar_id, avatar_nom, avatar_lien
            FROM avatars 
            ORDER BY avatar_nom;";
    $req = $db->prepare($sql);
    $req->execute();
    $avatars = "";
    foreach ($req->fetchAll(PDO::FETCH_ASSOC) as $avatar) {
        $avatars .= '<img onclick="changerAvatar(this);"  data-id="' . $avatar['avatar_id'] . '" class="img-fluid rounded me-2 mb-5 img-preview' . ($avatar['avatar_id'] == $stagiaire['avatar_id'] ? ' img-selected' : '') . '" src="//' . $_SERVER["SERVER_NAME"] . '/erp/public/formation/imgs/avatars/' . $avatar['avatar_lien'] . '" alt="Avatar de type ' . $avatar['avatar_nom'] . '" >';
    }
    $form = '
        <div class="container">
            <div class="row">
                <form action="//' . $_SERVER["SERVER_NAME"] . '/erp/src/c/requests.php" enctype="multipart/form-data" method="post">
                    <div class="row">
                        <div class="col-12 text-center">
                            <input type="hidden" name="form_avatar_stagiaire" value="' . $stagiaire['avatar_id'] . '">
                            <img class="img-fluid rounded m-5 img-profile" src="//' . $_SERVER["SERVER_NAME"] . '/erp/public/formation/imgs/avatars/' . $stagiaire['avatar_lien'] . '" alt="Avatar de type ' . $stagiaire['avatar_nom'] . '" >
                        </div>
                        <div class="col-12 text-center">
                        ' . $avatars . '
                        </div>
                    </div>
                    <div class="row">
                        <div class="col">
                            <div class="form-group">
                                <label for="form_nom_stagiaire">NOM<span class="required">*</span></label>
                                <input type="text" class="form-control" name="form_nom_stagiaire" id="form_nom_stagiaire" placeholder="Nom utilisateur" required value="' . $stagiaire['stagiaire_nom'] . '">
                            </div>
                        </div>
                        <div class="col">
                            <div class="form-group">
                                <label for="form_prenom_stagiaire">Prénom<span class="required">*</span></label>
                                <input type="text" class="form-control" name="form_prenom_stagiaire" id="form_prenom_stagiaire" placeholder="Prénom d\'utilisateur" required value="' . $stagiaire['stagiaire_prenom'] . '">
                            </div>
                        </div>
                        <div class="col">
                            <div class="form-group">
                                <label for="form_pseudo_stagiaire">Pseudo<span class="required">*</span></label>
                                <input type="text" class="form-control" name="form_pseudo_stagiaire" id="form_pseudo_stagiaire" autocomplete="off" placeholder="Pseudo unique d\'utilisateur" disabled required value="' . $stagiaire['stagiaire_pseudo'] . '">
                            </div>
                        </div>
                        <div class="col">
                            <div class="form-group">
                                <label for="form_mail_stagiaire">Mail<span class="required">*</span></label>
                                <input type="text" class="form-control" name="form_mail_stagiaire" id="form_mail_stagiaire" onchange="checkEmailStagiaire();" autocomplete="email" placeholder="Mail unique d\'utilisateur" required value="' . $stagiaire['stagiaire_mail'] . '">
                                <span class="d-none" id="form_mail_disponible"></span>
                            </div>
                        </div>
                        <div class="col">
                            <div class="form-group">
                                <label for="form_mdp_stagiaire">Mot de passe</label>
                                <input type="password" autocomplete="new-password" class="form-control" name="form_mdp_stagiaire" id="form_mdp_stagiaire" placeholder="Mot de passe de l\'utilisateur" value="">
                            </div>
                        </div>
                    </div>
                    <div class="row pt-2">
                        <div class="col-12 text-end">
                            <input type="submit" class="btn btn-dark" name="form_update_trainee" >
                        </div>
                    </div>
                </form>
            </div>
            ' . $informations_session . '
            ' . $questionnaire_stagiaire_remplis . '
        </div>
    ';
}

ob_start();
include_once("./header.php");
echo $form; ?>
    <script>
        function changerAvatar(img) {
            var avatars = document.querySelectorAll('.img-preview');
            avatars.forEach(avatar => {
                avatar.classList.remove('img-selected');
            });
            document.querySelector('.img-profile').src = img.src;
            if (document.querySelector('input[name="form_avatar_formateur"]')) {
                document.querySelector('input[name="form_avatar_formateur"]').value = img.dataset.id;
            } else {
                document.querySelector('input[name="form_avatar_stagiaire"]').value = img.dataset.id;
            }
            img.classList.add('img-selected');
        }
    </script>
<?php include_once("./js.php"); ?>
<script>
    //TODO: not working
    function checkEmailStagiaire() {
        $.ajax({
            url: "//<?= $_SERVER["SERVER_NAME"] ?>/erp/src/c/requests.php",
            method: "post",
            dataType: "json",
            data: {
                check_email_stagiaire: 1,
                form_stagiaire_email: document.querySelector('#form_mail_stagiaire').value
            },
            success: function(r) {
                $('#form_mail_disponible').text(r.message);
                return r.disponible;
            }
        });
    }
</script>
<script src="//cdn.jsdelivr.net/npm/signature_pad@4.0.0/dist/signature_pad.umd.min.js"></script>
<script src="./js/mon-compte.js"></script>
<?php
include_once("./footer.php");
die(ob_get_clean());
