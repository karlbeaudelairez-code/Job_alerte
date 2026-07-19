<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription réussie - Job Alert Bénin</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container" style="text-align: center;">
        <div style="font-size: 60px;">🎉</div>
        <h1 style="color: #008751;">Inscription réussie !</h1>
        <p><?= htmlspecialchars($_GET['message'] ?? '') ?></p>
        <br>
        <a href="index.php" style="
            display: inline-block;
            padding: 12px 30px;
            background-color: #008751;
            color: white;
            border-radius: 6px;
            text-decoration: none;
            font-size: 16px;
        ">← Retour à l'accueil</a>
    </div>
</body>
</html>