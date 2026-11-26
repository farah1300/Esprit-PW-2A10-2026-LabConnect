<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($titrePage ?? 'LabConnect') ?> - LabConnect</title>
    <link rel="stylesheet" href="public/css/prelevement.css">
</head>
<body class="front">
<header class="entete">
    <div class="entete-int">
        <a class="logo" href="index.php?action=prelevement_liste">Lab<span>Connect</span></a>
        <nav class="menu">
            <a class="<?= ($menuActif ?? '') === 'prelevements' ? 'actif' : '' ?>" href="index.php?action=prelevement_liste">Mes prélèvements</a>
            <a class="<?= ($menuActif ?? '') === 'adresses' ? 'actif' : '' ?>" href="index.php?action=adresse_liste">Mes adresses</a>
            <a href="index.php?action=back_liste">Espace responsable</a>
        </nav>
    </div>
</header>
<main class="conteneur"></main>