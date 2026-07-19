<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Job Alert Bénin</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <h1> Job Alert Bénin </h1>
        <div class="header-actions">
            <div class="ins">
                <h2> Inscription </h2>
            </div>
            <div class="desinsc">
                <a href="/logout">
                    <button type="button">Se désinscrire</button>
                </a>
            </div>
        </div>
        <p>Recevez les offres d'emploi de votre domaine directement dans votre boîte mail.</p>

        <form action="inscription.php" method="POST">
            <input type="text" name="prenom" placeholder="Votre prénom" required>
            <input type="email" name="email" placeholder="Votre email" required>
            <select name="domaine" required>
                <option value="">--Choisissez votre domaine--</option>
                <option vaalue="informatique">Informatique</option>
                <option value="enseignement">Education</option>
                <option value="sante">Santé</option>
                <option value="restauration">Restauration</option>
                <option value="btp">BTP & Architecture</option>
                <option value="logistique">Logistique et Transport</option>
                <option value="comptabilite">Finance & Comptabilité</option>
                <option value="rh">Ressources humaines</option>
                <option value="commerce">Commerce</option>
                <option value="juridique">Droit & Justice</option>
                <option value="design">Design</option>
            </select>
            
            <button type="submit">S'inscrire aux alertes</button>
        </form>
    </div>
</body>
</html>