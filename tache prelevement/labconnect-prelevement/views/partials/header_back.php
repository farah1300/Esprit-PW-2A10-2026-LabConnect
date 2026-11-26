<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($titrePage ?? 'BackOffice') ?> - LabConnect BackOffice</title>
    <link rel="stylesheet" href="public/css/prelevement.css">
</head>
<body class="back">
<header class="entete">
    <div class="entete-int">
        <a class="logo" href="index.php?action=back_liste">Lab<span>Connect</span> BackOffice</a>
        <nav class="menu">
            <a class="<?= ($menuActif ?? '') === 'demandes' ? 'actif' : '' ?>" href="index.php?action=back_liste">Demandes de prélèvement</a>
            <a href="index.php?action=prelevement_liste">Espace patient</a>
        </nav>
    </div>
</header>
<main class="conteneur"></main>