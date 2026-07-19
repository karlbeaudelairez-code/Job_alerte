<?php

    require_once 'config.php';
    require_once 'detecter_domaine.php';

    // Lancer les deux scrapers
    require_once 'scraping_offresdemploi.php';
    scraper_offresdemplois($pdo);

    require_once 'scraping_benintalents.php';
    scraper_benintalents($pdo);

    // Envoyer les alertes
    require_once 'envoi.php';

    echo "Cron terminé avec succès !";
?>