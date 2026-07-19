<?php

    require_once 'config.php';

    if($_SERVER["REQUEST_METHOD"] == "POST"){

        $prenom = trim($_POST['prenom']);
        $email = trim($_POST['email']);
        $domaine = trim($_POST['domaine']);

        // Vérifier si le candidat existe
        $stmt = $pdo->prepare("
            SELECT * FROM utilisateurs
            WHERE prenom = ?
            AND email = ?
            AND domaine = ?
        ");
        $stmt->execute([$prenom, $email, $domaine]);

        if($stmt->rowCount() > 0){

            // Supprimer le candidat
            $delete = $pdo->prepare("
                DELETE FROM utilisateurs
                WHERE prenom = ?
                AND email = ?
                AND domaine = ?
            ");
            $delete->execute([$prenom, $email, $domaine]);

            header("Location: successlogout.html?message=".urlencode("$prenom, vous avez été désinscrit avec succès. Merci pour votre fidélité !"));
            exit();

        } else {

            header("Location: successlogout.html?message=".urlencode("Aucun compte trouvé avec ces informations."));
            exit();
        }
    }
?>