<?php
    require 'env.php';

    try{
        $serveur = $env['DB_HOST'];
        $utilisateur = $env['DB_USER'];
        $mot_de_passe = $env['DB_PASS'];
        $base_de_donnees = $env['DB_NAME'];

        $pdo = new PDO(
            "mysql:host={$env['DB_HOST']}; dbname={$env['DB_NAME']}; charset=utf8",
            $env['DB_USER'],
            $env['DB_PASS']
        );
        $pdo->setAttribute(
            PDO::ATTR_ERRMODE,
            PDO::ERRMODE_EXCEPTION
        );
    }catch(PDOException $e){
        die("Erreur de connexion : " . $e->getMessage());
    }
?>