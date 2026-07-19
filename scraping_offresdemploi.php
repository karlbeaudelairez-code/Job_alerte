<?php

    require_once 'config.php';
    require_once 'detecter_domaine.php';

    function scraper_offresdemplois($pdo){

        $url = "https://offresdemplois.bj/";

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0");
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $html = curl_exec($ch);

        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        echo "Status : $status <br>";

        if($html === false){

            echo "Erreur cURL : " . curl_error($ch);

            curl_close($ch);

            return;
        }

        $dom = new DOMDocument();

        libxml_use_internal_errors(true);

        $dom->loadHTML($html);

        libxml_clear_errors();

        $xpath = new DOMXPath($dom);

        $containers = $xpath->query(
            "//div[contains(@class, 'jbs-grid-usrs-block')]"
        );

        echo "
            Nombre d'offres :
            {$containers->length}<br>
        ";

        $offres_ajoutees = 0;

        foreach($containers as $container){

            $titre = null;

            $titre_nodes = $xpath->query(".//h6", $container);
            $titre = $titre_nodes->length > 0 ? trim($titre_nodes->item(0)->textContent) : null;

            $lien = null;

            $lien_nodes = $xpath->query(".//div[contains(@class, 'jbs-btn-groups')]//a", $container);
            $lien = $lien_nodes->length > 0 ? $lien_nodes->item(0)->getAttribute('href') : null; 

            $date = date('Y-m-d');

            $domaine = detecter_domaine($titre);

            $date_limite = null;

            $date_limite_nodes = $xpath->query(
                ".//div[contains(@class, 'jbs-single-y1')]",
                $container
            );

            foreach($date_limite_nodes as $node){

                $texte = trim($node->textContent);

                if(stripos($texte, 'Date dépôt de dossier') !== false){

                    $date_limite = str_replace(
                        'Date dépôt de dossier',
                        '',
                        $texte
                    );

                    $date_limite = trim($date_limite);

                    $date_obj = DateTime::createFromFormat(
                        'd/m/Y',
                        $date_limite
                    );

                    if($date_obj){
                        $date_limite = $date_obj->format('Y-m-d');
                    }

                    break;
                }
            }
            if($titre && $lien){

                $stmt = $pdo->prepare("
                    SELECT id
                    FROM offres_emplois
                    WHERE lien = ?
                ");

                $stmt->execute([$lien]);

                if(!$stmt->fetch()){
                    try{
                        $insert = $pdo->prepare("
                            INSERT INTO offres_emplois
                            (titre, lien, domaine, date_publication, date_limite)
                            VALUES (?, ?, ?, ?, ?)
                        ");

                        $insert->execute([
                            $titre,
                            $lien,
                            $domaine,
                            $date,
                            $date_limite
                        ]);

                        $offres_ajoutees++;
                        
                    }catch(PDOException $e){

                        echo "
                            Erreur SQL :
                            " . $e->getMessage() . "
                            <br>
                        ";
                    }
                }
                else{
                    echo "Offre déjà existante : $titre <br>";
                }
            }
        }
    }

?>
