<?php

use PHPMailer\PHPMailer\PHPMailer;

function recupererStages($id_formateur = 0, $nom_session = 0)
{
    global $db;

    $sql = "SELECT * 
            FROM stagiaires
            JOIN sessions ON sessions.session_id = stagiaires.id_session
            JOIN stages ON stages.stage_id = stagiaires.id_stage 
            WHERE 1 ";
    if (!empty($id_formateur)) {
        $sql .= " AND sessions.id_formateur=:id_formateur ";
    }
    if (!empty($nom_session)) {
        $sql .= " AND sessions.session_nom=:nom_session ";
    }
    $sql .= ";";
    $req = $db->prepare($sql);
    if (!empty($id_formateur)) {
        $req->bindParam(":id_formateur", $id_formateur);
    }
    if (!empty($nom_session)) {
        $req->bindParam(":nom_session", $nom_session);
    }
    $req->execute();
    $stages = $req->fetchAll(PDO::FETCH_ASSOC);
    return $stages;
}

function recupererFormateurs()
{
    global $db;

    $req = $db->prepare("SELECT *
                        FROM formateurs
                        INNER JOIN secteurs ON secteurs.secteur_id = formateurs.id_secteur;");
    $req->execute();
    $formateurs = $req->fetchAll(PDO::FETCH_ASSOC);
    return $formateurs;
}

function recupererStagiaires()
{
    global $db;

    $req = $db->prepare("SELECT *
                        FROM stagiaires;");
    $req->execute();
    $stagiaires = $req->fetchAll(PDO::FETCH_ASSOC);
    return $stagiaires;
}

function recupererSessions($id_formateur = 0)
{
    global $db;

    $sql = "SELECT *
            FROM sessions
            LEFT JOIN formateurs ON formateurs.formateur_id = sessions.id_formateur 
            WHERE 1 ";
    if (!empty($id_formateur)) {
        $sql .= " AND sessions.id_formateur=:id_formateur ";
    }
    $req = $db->prepare($sql);
    if (!empty($id_formateur)) {
        $req->bindValue(':id_formateur', filter_var($id_formateur, FILTER_VALIDATE_INT));
    }
    $req->execute();
    $sessions = $req->fetchAll(PDO::FETCH_ASSOC);
    return $sessions;
}

function recupererSecteurs()
{
    global $db;

    $req = $db->prepare("SELECT *
                        FROM secteurs;");
    $req->execute();
    $secteurs = $req->fetchAll(PDO::FETCH_ASSOC);
    return $secteurs;
}

function recupererSites()
{
    global $db;

    $req = $db->prepare("SELECT *
                        FROM sites;");
    $req->execute();
    $sites = $req->fetchAll(PDO::FETCH_ASSOC);
    return $sites;
}

/**
 * Permet d'envoyer les documents manquants à un tuteur donné.
 *
 *
 * @param PHPMailer $mailer Une instance paramétrée de PHPMailer.
 * @param int       $id_stagiaire L'identifiant du stagiaire.
 * @param int       $id_formateur L'identifiant du formateur associé à la démarche.
 * @param array     $documents Tableau des documents à générer et joindre.
 * @param array     $document_libelles Tableau des explications pour chaque document.
 * @param boolean   $bEstRelance Permet de changer le template du mail envoyé, si rien n'est précisé, il ne s'agit pas d'une relance par défaut.
 * @return boolean  Renvoi un simple booléen.
 */
function envoyerMailTuteur($mailer, $id_stagiaire, $id_formateur, $documents, $document_libelles, $bEstRelance = false)
{
    global $db;
    global $mailer;

    //Récupération des données du stage
    $req = $db->prepare("SELECT stagiaire_nom, stagiaire_prenom, session_duree_stage, session_sigle, DATE_FORMAT(session_date_debut, '%d/%m/%Y') AS session_date_debut, DATE_FORMAT(session_date_fin, '%d/%m/%Y') AS session_date_fin, DATE_FORMAT(session_stage_date_debut, '%d/%m/%Y') AS session_stage_date_debut, DATE_FORMAT(session_stage_date_fin, '%d/%m/%Y') AS session_stage_date_fin, stage_adresse_rue, stage_adresse_cp, stage_adresse_ville, stage_adresse_pays, stage_nom_tuteur, stage_prenom_tuteur, stage_mail_tuteur
                        FROM stagiaires 
                        JOIN sessions ON sessions.session_id = stagiaires.id_session
                        LEFT JOIN stages ON stages.stage_id = stagiaires.id_stage
                        WHERE stagiaires.stagiaire_id=:id_stagiaire;");
    $req->bindParam(":id_stagiaire", $id_stagiaire);
    $req->execute();
    $stage = $req->fetch(PDO::FETCH_ASSOC);

    try {
        //Recipients
        $mailer->setFrom('marceaurodrigues@adrar-formation.com', 'Marceau RODRIGUES');
        if (DEV) {
            $mailer->addAddress('marceau0707@gmail.com', $stage['stage_prenom_tuteur'] . " " . $stage['stage_nom_tuteur']);
        } else {
            $mailer->addAddress($stage['stage_mail_tuteur'], $stage['stage_prenom_tuteur'] . " " . $stage['stage_nom_tuteur']);
            $mailer->addBCC('marceaurodrigues@adrar-formation.com'); // Blind Carbon Copy
        }
        $mailer->addReplyTo('marceaurodrigues@adrar-formation.com', 'Marceau RODRIGUES');
        // $mailer->addCC('marceaurodrigues@adrar-formation.com');

        //Création des PDFs personnalisés
        // $documents = array('convention', 'attestation', 'evaluation', 'presence');

        $liste_documents = implode("', '", $documents);
        $req = $db->prepare("SELECT document_id, document_index, document_nom
                            FROM documents
                            WHERE document_index IN ('" . $liste_documents . "');");
        $req->execute();
        $documents = $req->fetchAll(PDO::FETCH_ASSOC);

        $req = $db->prepare("SELECT formateur_nom, formateur_prenom, formateur_mail, formateur_signature, secteur_nom, secteur_logo, carte_formateur_role, carte_formateur_liens, carte_formateur_tel, carte_formateur_portable, GROUP_CONCAT(site_adresse_num, ' ', site_adresse_rue, ' ', site_adresse_cp, ' ', site_adresse_ville) AS carte_formateur_adresse_site
                            FROM formateurs 
                            INNER JOIN sites ON sites.site_id = formateurs.id_site  
                            INNER JOIN secteurs ON secteurs.secteur_id = formateurs.id_secteur 
                            WHERE formateur_id=:id_formateur;");
        $req->bindParam(":id_formateur", $id_formateur);
        $req->execute();
        $formateur = $req->fetch(PDO::FETCH_ASSOC);

        if (!empty($documents)) {

            foreach ($documents as $document) {
                $req = $db->prepare("SELECT document_nom, document_page_lien, document_page_textes_ajoutes
                                    FROM documents 
                                    JOIN documents_pages ON documents.document_id = documents_pages.id_document
                                    WHERE document_index=:document_index;");
                $req->bindParam(":document_index", $document['document_index']);
                $req->execute();
                $pages = $req->fetchAll(PDO::FETCH_ASSOC);

                if (!empty($pages)) {
                    $html2pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8');
                    $html2pdf->SetCreator(ucfirst($formateur['formateur_prenom']) . " " . strtoupper($formateur['formateur_nom']));
                    $html2pdf->SetAuthor(ucfirst($formateur['formateur_prenom']) . " " . strtoupper($formateur['formateur_nom']));
                    $html2pdf->SetTitle($pages[0]['document_nom']);
                    $html2pdf->SetSubject($pages[0]['document_nom']);

                    foreach ($pages as $page) {
                        $html2pdf->AddPage();
                        $html2pdf->Image(__DIR__ . "/../v/templates_documents/" . $document['document_index'] . "/" . $page['document_page_lien']); // Le template de la convention
                        if (!empty($page['document_page_textes_ajoutes'])) {
                            foreach (json_decode($page['document_page_textes_ajoutes'], true) as $i) {
                                $html2pdf->setXY($i['X'], $i['Y']);
                                if (str_contains($i['texte'], "|")) {
                                    $texte = explode('|', $i['texte']);
                                    if (array_key_exists($texte[0], $stage) || array_key_exists($texte[1], $stage)) {
                                        $html2pdf->Cell(0, 10, strtoupper($stage[$texte[0]]) . " " . ucwords($stage[$texte[1]]), 0, 1);
                                    } elseif (array_key_exists($texte[0], $formateur) || array_key_exists($texte[1], $formateur)) {
                                        $html2pdf->Cell(0, 10, strtoupper($formateur[$texte[0]]) . " " . ucwords($formateur[$texte[1]]), 0, 1);
                                    }
                                } elseif (array_key_exists($i['texte'], $stage)) {
                                    $html2pdf->Cell(0, 10, $stage[$i['texte']], 0, 1);
                                } elseif (array_key_exists($i['texte'], $formateur)) {
                                    $html2pdf->Cell(0, 10, $formateur[$i['texte']], 0, 1);
                                } else {
                                    $html2pdf->Cell(0, 10, $i['texte'], 0, 1);
                                }
                                if (isset($i['tampon']) && !empty($i['tampon'])) { // Gère le tampon s'il est nécessaire sur le document
                                    $html2pdf->Image(__DIR__ . "/../" . $i['tampon'], $i['xtampon'], $i['ytampon'], 60, 30);
                                }
                                if (isset($i['signature']) && !empty($i['signature'])) { // Gère la signature du formateur si elle est nécessaire sur le document
                                    $html2pdf->Image(__DIR__ . "/../" . $i['signature'], $i['xsignature'], $i['ysignature'], 47, 27.5);
                                }
                            }
                        }
                    }
                    if (!is_dir(__DIR__ . '/../v/tmp/')) {
                        mkdir(__DIR__ . '/../v/tmp/');
                    }
                    $html2pdf->Output(__DIR__ . '/../v/tmp/' . $document['document_index'] . '.pdf', 'F');
                    $mailer->addAttachment(__DIR__ . '/../v/tmp/' . $document['document_index'] . '.pdf', str_replace(" ", "_", $document['document_nom']) . '.pdf');
                }
            }
        }


        $liste_documents = "";
        foreach ($document_libelles as $document) {
            $liste_documents .= "<li>" . $document . "</li>";
        }

        if ($bEstRelance) {
            $message = file_get_contents(__DIR__ . '/../v/templates_mails/MAIL_RELANCE.html');
            $message = strtr($message, array(
                '{{NB_RELANCE}}' => $stage['stagiaire_compteur_demandes'] + 1
            ));
        } else {
            $message = file_get_contents(__DIR__ . '/../v/templates_mails/MAIL_DEMANDE.html');
        }
        $numeros = "";
        $liens = "";
        if (!empty($formateur['carte_formateur_tel']) && !empty($formateur['carte_formateur_portable'])) {
            $numeros .= '<p style="margin:0;">Tél. <a href="tel:' . $formateur['carte_formateur_tel'] . '">' . $formateur['carte_formateur_tel'] . '</a></p>';
            $numeros .= '<p style="margin:0;">Port. <a href="tel:' . $formateur['carte_formateur_portable'] . '">' . $formateur['carte_formateur_portable'] . '</a></p>';
        } elseif (!empty($formateur['carte_formateur_tel'])) {
            $numeros .= '<p style="margin:0;">Tél. <a href="tel:' . $formateur['carte_formateur_tel'] . '">' . $formateur['carte_formateur_tel'] . '</a></p>';
        } elseif (!empty($formateur['carte_formateur_portable'])) {
            $numeros .= '<p style="margin:0;">Port. <a href="tel:' . $formateur['carte_formateur_portable'] . '">' . $formateur['carte_formateur_portable'] . '</a></p>';
        }
        if (!empty($formateur['carte_formateur_liens'])) {
            if (str_contains($formateur['carte_formateur_liens'], ",")) {
                foreach (explode(",", $formateur['carte_formateur_liens']) as $lien) {
                    $liens .= '<p style="margin:0;"><a href="' . $lien . '">' . $lien . '</a></p>';
                }
            } else {
                $liens .= '<p style="margin:0;"><a href="' . $formateur['carte_formateur_liens'] . '">' . $formateur['carte_formateur_liens'] . '</a></p>';
            }
        }

        $message = strtr($message, array(
            '{{NOM_TUTEUR}}' => strtoupper($stage['stage_nom_tuteur']),
            '{{PRENOM_NOM_STAGIAIRE}}' => strtoupper($stage['stagiaire_nom']) . " " . ucwords($stage['stagiaire_prenom']),
            '{{LISTE_DOCUMENTS}}' => $liste_documents,
            '{{CARTE_PRENOM}}' => ucwords($formateur['formateur_prenom']),
            '{{CARTE_NOM}}' => strtoupper($formateur['formateur_nom']),
            '{{CARTE_LOGO_SECTEUR}}' => $formateur['secteur_logo'],
            '{{CARTE_ADRESSE}}' => $formateur['carte_formateur_adresse_site'],
            '{{CARTE_NUMEROS}}' => $numeros,
            '{{CARTE_LIENS}}' => $liens,
            '{{CARTE_ROLE}}' => $formateur['carte_formateur_role']
        ));


        //Content
        if ($bEstRelance) {
            $mailer->Subject = 'Relance de demande des documents de stage';
        } else {
            $mailer->Subject = 'Demande de documents de stage';
        }
        $mailer->Body    = $message;
        $mailer->AltBody = strip_tags($message);

        if ($mailer->send()) {
            if (!DEV) {
                $req = $db->prepare("UPDATE stagiaires 
                                    SET stagiaire_compteur_demandes = stagiaire_compteur_demandes + 1
                                    WHERE stagiaire_id=:id_stagiaire;");
                $req->bindParam(":id_stagiaire", $id_stagiaire);
                $req->execute();
            }
            array_map("unlink", glob(__DIR__ . "/../v/tmp/*.pdf")); // Permet de supprimer l'entiereté des PDF généré pour qu'il n'en reste pas de trace
        }
        echo "Message délivré.";
    } catch (Exception $e) {
        throw new Error("Message non délivré. Erreur: {$mailer->ErrorInfo}");
    }
    return true;
}

/**
 * Permet d'enregistrer une session dans la table **sessions**.
 *
 * Cette fonction prend un ensemble de paramètres pour une session donnée.
 *
 * @param string        $nom Le nom de la session.
 * @param string        $sigle Le sigle de la session.
 * @param string        $date_debut La date de début.
 * @param string        $date_fin La date de fin.
 * @param array|null    $blason Le blason de la session (optionnel).
 * @param int           $id_formateur Le/la formatrice référent·e de la session.
 * @return string       Un JSON avec deux index `boolean success` et `string message`.
 */
function inscriptionSession(string $nom, string $sigle, string $date_debut, string $date_fin, array|null $blason, int $id_formateur)
{
    global $db;

    $req = $db->prepare("SELECT session_id FROM sessions WHERE session_nom=:session_nom;");
    $req->bindValue(":session_nom", $nom);
    $req->execute();
    if ($req->rowCount() === 0) { // Si la session n'existe pas encore
        $req->closeCursor();
        $blason_nom = NULL;
        if (!empty($blason)) {
            $extensions_ok = array("gif", "jpg", "jpeg", "png");
            $extension = substr(strrchr($blason['name'], '.'), 1);
            if (in_array($extension, $extensions_ok)) {
                $lien = "../../public/formation/imgs/blasons";
                $blason_nom = $nom . "." . $extension;
                if (!is_dir($lien)) {
                    mkdir($lien, 0777, true);
                }
                move_uploaded_file($blason['tmp_name'], $lien . "/" . $blason_nom);
            }
        }

        $req = $db->prepare("INSERT INTO sessions(session_nom, session_sigle, session_date_debut, session_date_fin, session_blason, id_formateur) 
                            VALUES(:session_nom, :session_sigle, :session_date_debut, :session_date_fin, :session_blason, :id_formateur);");
        $req->bindValue(":session_nom", filter_var(strtoupper($nom), FILTER_SANITIZE_SPECIAL_CHARS));
        $req->bindValue(":session_sigle", filter_var($sigle, FILTER_SANITIZE_SPECIAL_CHARS));
        $req->bindValue(":session_date_debut", date('Y-m-d', strtotime($date_debut)));
        $req->bindValue(":session_date_fin", date('Y-m-d', strtotime($date_fin)));
        $req->bindValue(":session_blason", filter_var($blason_nom, FILTER_SANITIZE_SPECIAL_CHARS));
        $req->bindValue(":id_formateur", filter_var($id_formateur, FILTER_VALIDATE_INT));
        if ($req->execute()) {
            $req->closeCursor();
            $success = true;
            $message = "Session créée.";
        } else {
            $success = false;
            $message = "Une erreur s'est produite, veuillez réessayer.";
        }
    } else {
        $success = false;
        $message = "Cette session existe déjà. Vérifiez les informations saisies.";
    }
    return json_encode(array(
        'success' => $success,
        'message' => $message
    ));
}

/**
 * Permet d'enregistrer un tuteur dans la table **stages**.
 *
 * Cette fonction prend un ensemble de paramètres pour un stage donné.
 *
 * @param PHPMailer $mailer Une instance paramétrée de PHPMailer.
 * @param string    $nom Le nom du tuteur.
 * @param string    $prenom Le prénom du tuteur.
 * @param string    $mail Le mail du tuteur.
 * @param string    $rue La rue du lieu de stage.
 * @param string    $cp Le code postal du lieu de stage.
 * @param string    $ville La ville du lieu de stage.
 * @param string    $pays Le pays du lieu de stage.
 * @return string   Un JSON avec deux index `boolean success` et `string message`.
 */
function inscriptionStage(PHPMailer $mailer, string $nom, string $prenom, string $mail, string $rue, string $cp, string $ville, string $pays)
{
    global $db;

    $mail_tuteur = filter_var($mail, FILTER_VALIDATE_EMAIL);

    $req = $db->prepare("SELECT stage_id FROM stages WHERE stage_mail_tuteur=:mail_tuteur;");
    $req->bindValue(":mail_tuteur", $mail_tuteur);
    $req->execute();
    if ($req->rowCount() === 0) { // Si le mail du tuteur n'existe pas encore
        $req->closeCursor();

        $nom_tuteur = filter_var(strtoupper($nom), FILTER_SANITIZE_SPECIAL_CHARS);
        $prenom_tuteur = filter_var(ucwords($prenom), FILTER_SANITIZE_SPECIAL_CHARS);

        $req = $db->prepare("INSERT INTO stages(stage_adresse_rue, stage_adresse_cp, stage_adresse_ville, stage_adresse_pays, stage_nom_tuteur, stage_prenom_tuteur, stage_mail_tuteur) 
                            VALUES(:stage_adresse_rue, :stage_adresse_cp, :stage_adresse_ville, :stage_adresse_pays, :stage_nom_tuteur, :stage_prenom_tuteur, :stage_mail_tuteur);");
        $req->bindValue(":stage_adresse_rue", filter_var($rue, FILTER_SANITIZE_SPECIAL_CHARS));
        $req->bindValue(":stage_adresse_cp", filter_var($cp, FILTER_SANITIZE_SPECIAL_CHARS));
        $req->bindValue(":stage_adresse_ville", filter_var($ville, FILTER_SANITIZE_SPECIAL_CHARS));
        $req->bindValue(":stage_adresse_pays", filter_var($pays, FILTER_SANITIZE_SPECIAL_CHARS));
        $req->bindValue(":stage_nom_tuteur", $nom_tuteur);
        $req->bindValue(":stage_prenom_tuteur", $prenom_tuteur);
        $req->bindValue(":stage_mail_tuteur", $mail_tuteur);
        if ($req->execute()) {
            $req->closeCursor();

            // TODO: compléter le mail + rechercher le stagiaire concerné lors de l'ajout
            $message = file_get_contents(__DIR__ . '/../v/templates_mails/MAIL_ENTREE_STAGIAIRE.html');
            $message = strtr($message, array(
                '{{NOM_TUTEUR}}' => $nom_tuteur
            ));
            $mailer->Subject = '[ADRAR] Stagiaire reçu dans votre établissement';
            $mailer->Body    = $message;
            $mailer->AltBody = strip_tags($message);
            $mailer->setFrom('marceaurodrigues@adrar-formation.com', 'Marceau RODRIGUES');
            if (DEV) {
                $mailer->addAddress('marceaurodrigues@adrar-formation.com'); // Blind Carbon Copy
            } else {
                $mailer->addAddress($mail_tuteur, $prenom_tuteur . " " . $nom_tuteur);
                $mailer->addBCC('marceaurodrigues@adrar-formation.com'); // Blind Carbon Copy
            }
            $mailer->addReplyTo('marceaurodrigues@adrar-formation.com', 'Marceau RODRIGUES');
            try {
                $mailer->send();
                $success = true;
                $message = "Email envoyé.";
            } catch (Exception $e) {
                $success = false;
                $message = "Erreur: " . $e->getMessage();
            }
        } else {
            $success = false;
            $message = "Une erreur s'est produite, veuillez réessayer.";
        }
    } else {
        $success = false;
        $message = "Cette adresse mail est déjà utilisée pour un autre stage.";
    }
    return json_encode(array(
        'success' => $success,
        'message' => $message
    ));
}

/**
 * Permet d'enregistrer un formateur dans la table **formateurs**.
 *
 * Cette fonction prend un ensemble de paramètres pour un formateur donné.
 *
 * @param PHPMailer $mailer Une instance paramétrée de PHPMailer.
 * @param string    $nom Le nom du formateur.
 * @param string    $prenom Le prénom du formateur.
 * @param string    $mail Le mail du formateur (@adrar-formation.com OU @adrar-numerique.com).
 * @param string    $role Le rôle du formateur, format libre.
 * @param string    $tel Le téléphone du formateur (commençant par 05, cf. Wildix).
 * @param int       $site L'identifiant du site du formateur, cf. table **sites**.
 * @param int       $secteur L'identifiant du secteur du formateur, cf. table **secteurs**.
 * @return string   Un JSON avec deux index `boolean success` et `string message`.
 */
function inscriptionFormateur(PHPMailer $mailer, string $nom, string $prenom, string $mail, string $role, string $tel, int $site, int $secteur)
{
    global $db;

    $mail_formateur = filter_var($mail, FILTER_VALIDATE_EMAIL);

    $req = $db->prepare("SELECT formateur_id FROM formateurs WHERE formateur_mail=:mail_formateur;");
    $req->bindValue(":mail_formateur", $mail_formateur);
    $req->execute();
    if ($req->rowCount() === 0) { // Si le mail du formateur n'existe pas encore
        $req->closeCursor();

        $nom_formateur = filter_var($nom, FILTER_SANITIZE_SPECIAL_CHARS);
        $prenom_formateur = filter_var($prenom, FILTER_SANITIZE_SPECIAL_CHARS);
        $code_entree_formateur = random_int(100000, 999999);

        $req = $db->prepare("INSERT INTO formateurs(formateur_nom, formateur_prenom, formateur_mail, carte_formateur_role, carte_formateur_tel, formateur_code_entree, formateur_date_code_entree, id_site, id_secteur) 
                            VALUES(:nom_formateur, :prenom_formateur, :mail_formateur, :carte_formateur_role, :carte_formateur_tel, :code_entree_formateur, DATE_ADD(NOW(), INTERVAL 7 DAY), :id_site, :id_secteur);");
        $req->bindValue(":nom_formateur", $nom_formateur);
        $req->bindValue(":prenom_formateur", $prenom_formateur);
        $req->bindValue(":mail_formateur", $mail_formateur);
        $req->bindValue(":carte_formateur_role", filter_var($role, FILTER_SANITIZE_SPECIAL_CHARS));
        $req->bindValue(":carte_formateur_tel", filter_var($tel, FILTER_SANITIZE_SPECIAL_CHARS));
        $req->bindValue(":code_entree_formateur", $code_entree_formateur);
        $req->bindValue(":id_site", filter_var($site, FILTER_VALIDATE_INT));
        $req->bindValue(":id_secteur", filter_var($secteur, FILTER_VALIDATE_INT));
        if ($req->execute()) {
            $req->closeCursor();

            $message = file_get_contents(__DIR__ . '/../v/templates_mails/MAIL_CODE_ENTREE.html');
            $message = strtr($message, array(
                '{{PRENOM_FORMATEUR}}' => ucwords($prenom),
                '{{CODE_ENTREE_FORMATEUR}}' => $code_entree_formateur,
                '{{LIEN}}' => $_SERVER['REQUEST_SCHEME'] . "://" . $_SERVER['SERVER_NAME'] . "/erp/public/code.php?code=" . $code_entree_formateur
            ));
            $mailer->Subject = '[ERP] Code UNIQUE formateur';
            $mailer->Body    = $message;
            $mailer->AltBody = strip_tags($message);
            $mailer->setFrom('marceaurodrigues@adrar-formation.com', 'Marceau RODRIGUES');
            if (DEV) {
                $mailer->addAddress('marceau0707@gmail.com', $mail_formateur . " - " . $prenom_formateur . " " . $nom_formateur);
            } else {
                $mailer->addAddress($mail_formateur, $prenom_formateur . " " . $nom_formateur);
                $mailer->addBCC('marceaurodrigues@adrar-formation.com'); // Blind Carbon Copy
            }
            $mailer->addReplyTo('marceaurodrigues@adrar-formation.com', 'Marceau RODRIGUES');
            try {
                $mailer->send();
                $success = true;
                $message = "Email envoyé.";
            } catch (Exception $e) {
                $success = false;
                $message = "Erreur: " . $e->getMessage();
            }
        } else {
            $success = false;
            $message = "Une erreur s'est produite, veuillez réessayer.";
        }
    } else {
        $success = false;
        $message = "Cette adresse mail est déjà utilisée par un autre formateur.";
    }
    return json_encode(array(
        'success' => $success,
        'message' => $message
    ));
}

/**
 * Permet d'enregistrer un stagiaire dans la table **stagiaires**.
 *
 *
 * @param PHPMailer     $mailer Une instance paramétrée de PHPMailer.
 * @param string        $nom Le nom du stagiaire.
 * @param string        $prenom Le prénom du stagiaire.
 * @param string        $mail Le mail du stagiaire.
 * @param string|null   $pseudo Le pseudo du stagiaire.
 * @param string|null   $tel Le téléphone du stagiaire.
 * @param string|null   $date_anniversaire La date d'anniversaire du stagiaire (format libre).
 * @param int           $id_session L'identifiant de la session du stagiaire, cf. table **sessions**.
 * @param int|null      $id_stage L'identifiant du stage en entreprise du stagiaire, cf. table **stages**.
 * @return string       Un JSON avec deux index `boolean success` et `string message`.
 */
function inscriptionStagiaire(PHPMailer $mailer, string $nom, string $prenom, string $mail, string $pseudo = null, string $tel = null, string $date_anniversaire = null, int $id_session, int $id_stage = null)
{
    global $db;

    $mail_stagiaire = filter_var($mail, FILTER_VALIDATE_EMAIL);
    if (is_null($pseudo)) {
        $pseudo = strtolower(substr($prenom, 0, 1) . $nom . date('y'));
    }

    $req = $db->prepare("SELECT stagiaire_id FROM stagiaires WHERE stagiaire_mail=:mail_stagiaire;");
    $req->bindValue(":mail_stagiaire", $mail_stagiaire);
    $req->execute();
    if ($req->rowCount() === 0) { // Si le mail du stagiaire n'existe pas encore
        $req->closeCursor();

        $nom_stagiaire = filter_var($nom, FILTER_SANITIZE_SPECIAL_CHARS);
        $prenom_stagiaire = filter_var($prenom, FILTER_SANITIZE_SPECIAL_CHARS);
        $mdp_stagiaire = readable_random_string(10);

        $req = $db->prepare("INSERT INTO stagiaires(stagiaire_nom, stagiaire_prenom, stagiaire_mail, stagiaire_pseudo, stagiaire_mdp, stagiaire_mdp_save, stagiaire_tel, stagiaire_date_naissance, id_session" . (isset($id_stage) ? ", id_stage" : "") . ") 
                            VALUES(:nom_stagiaire, :prenom_stagiaire, :mail_stagiaire, :pseudo_stagiaire, :mdp_stagiaire, :mdp_stagiaire, :stagiaire_tel, DATE_FORMAT(:stagiaire_date_naissance, '%Y-%m-%d'), :id_session" . (isset($id_stage) ? ", :id_stage" : "") . ");");
        $req->bindValue(":nom_stagiaire", $nom_stagiaire);
        $req->bindValue(":prenom_stagiaire", $prenom_stagiaire);
        $req->bindValue(":mail_stagiaire", $mail_stagiaire);
        $req->bindValue(":pseudo_stagiaire", filter_var($pseudo, FILTER_SANITIZE_SPECIAL_CHARS));
        $req->bindValue(":mdp_stagiaire", password_hash(filter_var($mdp_stagiaire, FILTER_SANITIZE_SPECIAL_CHARS), PASSWORD_BCRYPT));
        $req->bindValue(":stagiaire_tel", filter_var($tel, FILTER_SANITIZE_SPECIAL_CHARS));
        $req->bindValue(":stagiaire_date_naissance", filter_var($date_anniversaire, FILTER_SANITIZE_SPECIAL_CHARS));
        $req->bindValue(":id_session", filter_var($id_session, FILTER_VALIDATE_INT));
        if (isset($id_stage)) {
            $req->bindValue(":id_stage", filter_var($id_stage, FILTER_VALIDATE_INT));
        }
        if ($req->execute()) {
            $req->closeCursor();

            $message = file_get_contents(__DIR__ . '/../v/templates_mails/MAIL_STAGIAIRE.html');
            $message = strtr($message, array(
                '{{PRENOM_STAGIAIRE}}' => ucwords($prenom),
                '{{LOGIN_STAGIAIRE}}' => $pseudo,
                '{{MDP_STAGIAIRE}}' => $mdp_stagiaire,
                '{{LIEN}}' => $_SERVER['REQUEST_SCHEME'] . "://" . $_SERVER['SERVER_NAME'] . "/erp/public/connexion.php"
            ));
            $mailer->Subject = '[ERP] - ADRAR - Votre session stagiaire';
            $mailer->Body    = $message;
            $mailer->AltBody = strip_tags($message);
            $mailer->setFrom('marceaurodrigues@adrar-formation.com', 'Marceau RODRIGUES');
            if (DEV) {
                $mailer->addAddress('marceau0707@gmail.com', $mail_stagiaire . " - " . $prenom_stagiaire . " " . $nom_stagiaire);
            } else {
                $mailer->addAddress($mail_stagiaire, $prenom_stagiaire . " " . $nom_stagiaire);
                $mailer->addBCC('marceaurodrigues@adrar-formation.com'); // Blind Carbon Copy
            }
            $mailer->addReplyTo('marceaurodrigues@adrar-formation.com', 'Marceau RODRIGUES');
            try {
                $mailer->send();
                $success = true;
                $message = "Email envoyé.";
            } catch (Exception $e) {
                $success = false;
                $message = "Erreur: " . $e->getMessage();
            }
        } else {
            $success = false;
            $message = "Une erreur s'est produite, veuillez réessayer.";
        }
    } else {
        $success = false;
        $message = "Cette adresse mail est déjà utilisée par un autre stagiaire.";
    }
    return json_encode(array(
        'success' => $success,
        'message' => $message
    ));
}

/**
 * Permet de réinitialiser le mot de passe d'un utilisateur dans la table **formateurs** ou **stagiaires**.
 *
 *
 * @param PHPMailer $mailer Une instance paramétrée de PHPMailer.
 * @param string    $mail L'identifiant du compte.
 * @return array    Un tableau avec deux index `string type` et `string message`.
 */
function reinitialiserMotDePasse(PHPMailer $mailer, string $mail)
{
    global $db;

    $mail_utilisateur = filter_var($mail, FILTER_VALIDATE_EMAIL);
    $tmp_code = random_int(100000, 999999);
    unset($_SESSION['code_tmp']);

    $req_formateur = $db->prepare("SELECT * FROM formateurs WHERE formateur_mail=:mail_formateur AND formateur_code_entree IS NULL;");
    $req_formateur->bindValue(":mail_formateur", $mail_utilisateur);
    $req_formateur->execute();

    $req_stagiaire = $db->prepare("SELECT * FROM stagiaires WHERE stagiaire_mail=:mail_stagiaire;");
    $req_stagiaire->bindValue(":mail_stagiaire", $mail_utilisateur);
    $req_stagiaire->execute();

    if ($req_formateur->rowCount() === 1) { // Si le mail du formateur existe
        $user = $req_formateur->fetch(PDO::FETCH_ASSOC);
        $req_formateur->closeCursor();

        $req = $db->prepare("UPDATE formateurs 
                            SET 
                                formateur_code_tmp=:tmp_code_formateur, 
                                formateur_date_code_tmp=DATE_ADD(NOW(), INTERVAL 15 MINUTE)
                            WHERE formateur_mail=:mail_formateur;");
        $req->bindValue(":mail_formateur", $mail_utilisateur);
        $req->bindValue(":tmp_code_formateur", $tmp_code);
        if ($req->execute()) {
            $req->closeCursor();

            $message = file_get_contents(__DIR__ . '/../v/templates_mails/MAIL_CODE_TMP.html');
            $message = strtr($message, array(
                '{{PRENOM}}' => ucwords($user['formateur_prenom']),
                '{{CODE_TMP}}' => $tmp_code,
                '{{LIEN}}' => $_SERVER['REQUEST_SCHEME'] . "://" . $_SERVER['SERVER_NAME'] . "/erp/public/code.php?code=" . $tmp_code
            ));
            $mailer->Subject = '[ERP] Code TEMPORAIRE formateur';
            $mailer->Body    = $message;
            $mailer->AltBody = strip_tags($message);
            $mailer->setFrom('marceaurodrigues@adrar-formation.com', 'Marceau RODRIGUES');
            if (DEV) {
                $mailer->addAddress('marceau0707@gmail.com', $mail_utilisateur . " - " . $user['formateur_prenom'] . " " . $user['formateur_nom']);
            } else {
                $mailer->addAddress($mail_utilisateur, $user['formateur_prenom'] . " " . $user['formateur_nom']);
                $mailer->addBCC('marceaurodrigues@adrar-formation.com'); // Blind Carbon Copy
            }
            $mailer->addReplyTo('marceaurodrigues@adrar-formation.com', 'Marceau RODRIGUES');
            try {
                $mailer->send();
                $type = "success";
                $message = "Un mail a été envoyé si l'adresse mail existe.";
            } catch (Exception $e) {
                $type = "error";
                $message = "Erreur: " . $e->getMessage();
            }
        } else {
            $type = "error";
            $message = "Une erreur s'est produite, veuillez réessayer.";
        }
    } elseif ($req_stagiaire->rowCount() === 1) {
        $user = $req_stagiaire->fetch(PDO::FETCH_ASSOC);
        $req_stagiaire->closeCursor();

        $req = $db->prepare("UPDATE stagiaires 
                            SET 
                                stagiaire_code_tmp=:tmp_code_stagiaire, 
                                stagiaire_date_code_tmp=DATE_ADD(NOW(), INTERVAL 15 MINUTE)
                            WHERE stagiaire_mail=:mail_stagiaire;");
        $req->bindValue(":mail_stagiaire", $mail_utilisateur);
        $req->bindValue(":tmp_code_stagiaire", $tmp_code);
        if ($req->execute()) {
            $req->closeCursor();

            $message = file_get_contents(__DIR__ . '/../v/templates_mails/MAIL_CODE_TMP.html');
            $message = strtr($message, array(
                '{{PRENOM}}' => ucwords($user['stagiaire_prenom']),
                '{{CODE_TMP}}' => $tmp_code,
                '{{LIEN}}' => $_SERVER['REQUEST_SCHEME'] . "://" . $_SERVER['SERVER_NAME'] . "/erp/public/code.php?code=" . $tmp_code
            ));
            $mailer->Subject = '[ERP] Code TEMPORAIRE stagiaire';
            $mailer->Body    = $message;
            $mailer->AltBody = strip_tags($message);
            $mailer->setFrom('marceaurodrigues@adrar-formation.com', 'Marceau RODRIGUES');
            if (DEV) {
                $mailer->addAddress('marceau0707@gmail.com', $mail_utilisateur . " - " . $user['stagiaire_prenom'] . " " . $user['stagiaire_nom']);
            } else {
                $mailer->addAddress($mail_utilisateur, $user['stagiaire_prenom'] . " " . $user['stagiaire_nom']);
                $mailer->addBCC('marceaurodrigues@adrar-formation.com'); // Blind Carbon Copy
            }
            $mailer->addReplyTo('marceaurodrigues@adrar-formation.com', 'Marceau RODRIGUES');
            try {
                $mailer->send();
                $type = "success";
                $message = "Un mail a été envoyé si l'adresse mail existe.";
            } catch (Exception $e) {
                $type = "error";
                $message = "Erreur: " . $e->getMessage();
            }
        } else {
            $type = "error";
            $message = "Une erreur s'est produite, veuillez réessayer.";
        }
    } else {
        $type = "success";
        $message = "Un mail a été envoyé si l'adresse mail existe.";
    }
    return array(
        'type' => $type,
        'message' => $message
    );
}

/**
 * Permet de connecter un formateur présent dans la table **formateurs** OU un stagiaire présent dans la table **stagiaires**.
 *
 *
 * @param string|int    $identifiant L'identifiant *unique* du formateur ou du stagiaire ou un code d'accès.
 * @param string|null   $mdp         Le mot de passe du formateur ou du stagiaire.
 * @return boolean      Un booléen.
 */
function connexionUtilisateur(string|int $identifiant, string|null $mdp)
{
    global $db;

    $sql = "SELECT * 
            FROM stagiaires WHERE stagiaire_pseudo=:identifiant;";
    $req = $db->prepare($sql);
    $req->bindValue(":identifiant", filter_var($identifiant, FILTER_SANITIZE_SPECIAL_CHARS));
    $req->execute();
    if ($req->rowCount() === 1) {
        $stagiaire = $req->fetch(PDO::FETCH_ASSOC);
        if (!isset($mdp) || password_verify($mdp, $stagiaire['stagiaire_mdp'])) {
            $_SESSION['utilisateur'] = $stagiaire;
            $_SESSION['utilisateur']['formateur_id'] = -1;
            $_SESSION['utilisateur']['id_secteur'] = -1;
            $req->closeCursor();
            return true;
        }
        $req->closeCursor();
    } elseif ($req->rowCount() === 0) {
        $sql = "SELECT *  
                FROM formateurs WHERE (formateur_mail=:identifiant 
                                    OR formateur_code_entree=:identifiant 
                                    OR formateur_code_tmp=:identifiant);";
        $req = $db->prepare($sql);
        $req->bindValue(":identifiant", filter_var($identifiant . "@adrar-formation.com", FILTER_VALIDATE_EMAIL));
        $req->execute();
        if ($req->rowCount() === 1) {
            $formateur = $req->fetch(PDO::FETCH_ASSOC);
            if (password_verify($mdp, $formateur['formateur_mdp'])) {
                $_SESSION['utilisateur'] = $formateur;
                $_SESSION['utilisateur']['stagiaire_id'] = -1;
                $_SESSION['utilisateur']['id_session'] = NULL;
                $_SESSION['mode_edition'] = false;
                $req->closeCursor();
                return true;
            }
            $req->closeCursor();
        }
    }
    return false;
}

/**
 * Generates human-readable string.
 * 
 * @param string $length Desired length of random string.
 * @credits sepehr readable_random_string.php
 * 
 * @return string Random string.
 */
function readable_random_string($length = 6)
{
    $string = '';
    $vowels = array("a", "e", "i", "o", "u");
    $consonants = array(
        'b',
        'c',
        'd',
        'f',
        'g',
        'h',
        'j',
        'k',
        'l',
        'm',
        'n',
        'p',
        'r',
        's',
        't',
        'v',
        'w',
        'x',
        'y',
        'z'
    );

    $max = $length / 2;
    for ($i = 1; $i <= $max; $i++) {
        $string .= $consonants[rand(0, 19)];
        $string .= $vowels[rand(0, 4)];
        $string .= '-';
    }

    return $string;
}

/**
 * Permet la prise en charge d'un nouveau document.
 * 
 * @param string $document_nom Le nom du document.
 * @param array $fichier Le fichier a importer.
 * 
 * 
 * @return string Un tableau avec deux index `string type` et `string message`..
 */
function ajouterDocument(string $document_nom, array $fichier)
{
    $type = "info";
    $message = "Document ajouté";

    if (!is_dir("../v/templates_documents/$document_nom")) {
        if (!mkdir("../v/templates_documents/$document_nom", 0777, true)) {
            $type = "error";
            $message = "Le dossier n'a pas pu être créé.";
        }
    }

    $nom_dossier = $document_nom;

    switch ($fichier['type']) {
        case "application/pdf":
            $extension = "pdf";
            break;
        default:
            $extension = "";
            $type = "error";
            $message = "Extension non prise en charge. Seuls les PDF sont acceptés.";
    }

    if (!empty($extension)) {
        // Création de l'objet Imagick
        $imagick = new \Imagick();

        // Réglage de la résolution pour améliorer la qualité de l'image PNG
        $imagick->setResolution(300, 300);

        // Lire le fichier PDF
        $imagick->readImage($fichier['tmp_name']);

        // Convertir chaque page du PDF en PNG
        foreach ($imagick as $pageNumber => $image) {
            // Enregistrer l'image au format PNG
            $image->writeImage('../v/templates_documents/' . $nom_dossier . '/' . $document_nom . '_' . ($pageNumber + 1) . '.jpg');
        }
        // Libérer la mémoire
        $imagick->clear();
        $imagick->destroy();
    }

    return json_encode(
        array(
            'type' => $type,
            'message' => $message
        )
    );
}
