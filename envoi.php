<?php

    require 'config.php';
    require 'env.php';

    use PHPMailer\PHPMailer\PHPMailer;
    use PHPMailer\PHPMailer\Exception;

    require 'src/Exception.php';
    require 'src/PHPMailer.php';
    require 'src/SMTP.php';

    $EMAIL_EXPEDITEUR = $env['EMAIL_EXPEDITEUR'];
    $MOT_DE_PASSE = $env['MOT_DE_PASSE'];

    // Récupérer les utilisateurs

    $requeteUtilisateurs = $pdo->query("
        SELECT * FROM utilisateurs
    ");
    $utilisateurs = $requeteUtilisateurs->fetchAll(PDO::FETCH_ASSOC);

    // Parcourir les utilisateurs

    foreach($utilisateurs as $user){
        $utilisateur_id = $user['id'];

        $prenom = $user['prenom'];

        $email = $user['email'];

        $domaine = $user['domaine'];

        // Récupérer les offres du domaine

        $requeteOffres = $pdo->prepare(
            "
                SELECT o.* 
                FROM offres_emplois o
                WHERE domaine = ?
                AND date_limite >= CURDATE()
                AND NOT EXISTS (
                    SELECT 1
                    FROM offres_envoyees oe
                    WHERE oe.utilisateur_id = ?
                    AND oe.offre_id = o.id
                )
            "
        );

        $requeteOffres->execute([$domaine, $utilisateur_id]);

        $offres = $requeteOffres->fetchAll(PDO::FETCH_ASSOC);

        // Vérifier s'il y a des offres

        if(count($offres) === 0){
            echo "Aucune nouvelle offre pour $email <br>";
            continue;
        }

        // Construire contenu email

        $contenu = "
            <h2>Bonjour $prenom 👋</h2>

            <p>
                Voici les nouvelles offres disponibles dans le domaine
                <b>$domaine</b>.
            </p>

            <hr>

            <ul>
        ";

            foreach($offres as $offre){

                $titre = $offre['titre'];

                $lien = $offre['lien'];

                $date_limite = $offre['date_limite'];

                $contenu .= "
                    <li>
                        <h3>$titre</h3>
                        <p>
                            Date limite : <b>$date_limite</b>
                        </p>
                        <a href='$lien'>
                            Voir l'offre
                        </a>
                    </li>

                    <br>
                ";
            }

            $contenu .= "
                </ul>
                <hr>
                <p>
                    Merci d'utiliser Job Alert Bénin.
                </p>
                ";

            $mail = new PHPMailer(true);
            $mail->SMTPDebug = 0;
            $mail->Debugoutput = 'html';

            try{

                $mail->isSMTP();

                $mail->Host = 'smtp.gmail.com';

                $mail->SMTPAuth = true;

                $mail->Username = $EMAIL_EXPEDITEUR;

                $mail->Password = $MOT_DE_PASSE;

                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;

                $mail->Port = 587;

                $mail->setFrom(
                    $EMAIL_EXPEDITEUR,
                    'Job Alert Benin'
                );

                $mail->addAddress($email, $prenom);

                $mail->isHTML(true);

                $mail->Subject = "Nouvelles offres d\'emploi";

                $mail->Body = $contenu;

                $mail->send();

                echo "Email envoyé à $email <br>";

            foreach($offres as $offre){
                $requeteInsert = $pdo->prepare("
                    INSERT INTO offres_envoyees(
                        utilisateur_id,
                        offre_id
                    ) VALUES (?, ?)
                ");
                $requeteInsert->execute([$utilisateur_id, $offre['id']]);
            }
            } catch(Exception $e){

                echo "
                    Erreur pour $email :
                    {$mail->ErrorInfo}<br>
                ";
            }
    }

?>