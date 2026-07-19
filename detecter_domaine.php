<?php
    function detecter_domaine($titre){

        $titre = strtolower($titre);

        // Informatique

        if(
            str_contains($titre, 'développeur') ||
            str_contains($titre, 'developpeur') ||
            str_contains($titre, 'informatique') ||
            str_contains($titre, 'web') ||
            str_contains($titre, 'logiciel') ||
            str_contains($titre, 'programmeur') ||
            str_contains($titre, 'data') ||
            str_contains($titre, 'réseau') ||
            str_contains($titre, 'reseau') ||
            str_contains($titre, 'sécurité')
        ){
            return 'informatique';
        }

        // Comptabilité / Finance

        if(
            str_contains($titre, 'comptable') ||
            str_contains($titre, 'finance') ||
            str_contains($titre, 'audit') ||
            str_contains($titre, 'caissier') ||
            str_contains($titre, 'banque')
        ){
            return 'comptabilite';
        }

        // Commerce / Marketing

        if(
            str_contains($titre, 'commercial') ||
            str_contains($titre, 'marketing') ||
            str_contains($titre, 'vente') ||
            str_contains($titre, 'business') ||
            str_contains($titre, 'client') ||
            str_contains($titre, 'communication') ||
            str_contains($titre, 'community manager')
        ){
            return 'commerce';
        }

        // Santé

        if(
            str_contains($titre, 'infirmier') ||
            str_contains($titre, 'infirmière') ||
            str_contains($titre, 'medecin') ||
            str_contains($titre, 'médecin') ||
            str_contains($titre, 'sage-femme') ||
            str_contains($titre, 'santé') ||
            str_contains($titre, 'pharmacien') ||
            str_contains($titre, 'pharmaceutique') ||
            str_contains($titre, 'psychologue')
        ){
            return 'sante';
        }

        //Restauration
        if(
            str_contains($titre, 'pâtissier') ||
            str_contains($titre, 'patissier') ||
            str_contains($titre, 'patissière') ||
            str_contains($titre, 'pâtissière') ||
            str_contains($titre, 'pâtisserie') ||
            str_contains($titre, 'patisserie') ||
            str_contains($titre, 'boulanger') ||
            str_contains($titre, 'cuisinier') ||
            str_contains($titre, 'cuisine')
        )
        // Enseignement

        if(
            str_contains($titre, 'professeur') ||
            str_contains($titre, 'enseignant') ||
            str_contains($titre, 'instituteur') ||
            str_contains($titre, 'éducation')
        ){
            return 'enseignement';
        }

        // BTP

        if(
            str_contains($titre, 'btp') ||
            str_contains($titre, 'chantier') ||
            str_contains($titre, 'maçon') ||
            str_contains($titre, 'macon') ||
            str_contains($titre, 'électricien') ||
            str_contains($titre, 'electricien') ||
            str_contains($titre, 'architecte') ||
            str_contains($titre, 'génie civil')
        ){
            return 'btp';
        }

        // Logistique / Transport

        if(
            str_contains($titre, 'chauffeur') ||
            str_contains($titre, 'livreur') ||
            str_contains($titre, 'logistique') ||
            str_contains($titre, 'transport')
        ){
            return 'logistique';
        }

        // Ressources humaines

        if(
            str_contains($titre, 'rh') ||
            str_contains($titre, 'ressources humaines') ||
            str_contains($titre, 'recrutement')
        ){
            return 'rh';
        }

        // Juridique

        if(
            str_contains($titre, 'juriste') ||
            str_contains($titre, 'avocat') ||
            str_contains($titre, 'droit')
        ){
            return 'juridique';
        }

        //Design
        if(
            str_contains($titre, 'design') ||
            str_contains($titre, 'graphiste') ||
            str_contains($titre, 'designer') ||
            str_contains($titre, 'ui') ||
            str_contains($titre, 'ux')
        )
        // Par défaut

        return 'autres';
    }

?>