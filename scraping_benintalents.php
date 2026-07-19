<?php

    require_once 'config.php';
    require_once 'detecter_domaine.php';

    function scraper_benintalents($pdo){

        $page = 1;
        $offres_ajoutees = 0;

        do {
            $url = "https://www.benintalents.com/api/jobs?page=$page";

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0");
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json']);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);

            $response = curl_exec($ch);
            curl_close($ch);

            $data = json_decode($response, true);

            if(!$data || !isset($data['jobs'])){
                echo "Erreur API page $page <br>";
                break;
            }

            $jobs = $data['jobs'];
            echo "Page $page : " . count($jobs) . " offres <br>";

            foreach($jobs as $job){

                $titre = $job['title'] ?? null;
                $lien = $job['url'] ?? "https://www.benintalents.com/jobs/{$job['id']}";
                $date_limite = isset($job['expirationDate']) ? date('Y-m-d', strtotime($job['expirationDate'])) : null;
                $domaine = detecter_domaine($titre);

                if($titre && $lien){

                    $stmt = $pdo->prepare("SELECT id FROM offres_emplois WHERE lien = ?");
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
                                date('Y-m-d'),
                                $date_limite
                            ]);
                            $offres_ajoutees++;
                            echo "Ajoutée : $titre ($domaine) <br>";

                        }catch(PDOException $e){
                            echo "Erreur SQL : " . $e->getMessage() . "<br>";
                        }
                    }
                }
            }

            $hasNextPage = $data['pagination']['hasNextPage'] ?? false;
            $page++;

        } while($hasNextPage && $page <= 5); // max 5 pages pour commencer

        echo "Total ajoutées : $offres_ajoutees <br>";
    }

?>