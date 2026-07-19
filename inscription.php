
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

    if($_SERVER["REQUEST_METHOD"] == "POST"){

        $prenom = trim($_POST['prenom']);
        $email = trim($_POST['email']);
        $domaine = trim($_POST['domaine']);

        $verification = "
            SELECT * FROM utilisateurs
            WHERE prenom = ?
            AND email = ?
            AND domaine = ?
        ";

        $stmt = $pdo->prepare($verification);

        $stmt->execute([$prenom, $email, $domaine]);

        if($stmt->rowCount() > 0){
            header("Location: index.html?message=Déjà inscrit !");
            exit();
            
        } else {

            $requete = "
                INSERT INTO utilisateurs
                (prenom, email, domaine)
                VALUES (?, ?, ?)
            ";

            $stmt = $pdo->prepare($requete);

            if($stmt->execute([$prenom, $email, $domaine])){

                $mail = new PHPMailer(true);

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

                    $mail->Subject = 'Inscription réussie';

                    $mail->Body = "
                        <h2>Bonjour $prenom 👋</h2>

                        <p>
                            Vous êtes maintenant inscrit
                            aux alertes <b>$domaine</b>.
                        </p>

                        <p>
                            Vous recevrez les nouvelles
                            offres d'emploi par email.
                        </p>
                    ";

                    $mail->send();
                    header("Location: success.php?message=Inscription réussie !");
                    exit();

                }catch(Exception $e){
                    header("Location: success.php?message=Erreur lors de l inscription.");
                    exit();
                }
            }
        }
    }

?>