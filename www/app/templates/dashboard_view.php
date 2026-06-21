<?php
    // ------------------------------------------------------------ //
    // Fichier dashboard_view.php
    // ------------------------------------------------------------ //

    // Sécurité minimale : accès uniquement via le contrôleur
    declare(strict_types=1);

    // Sécurité minimale
    if (!isset($licenses)) {
        header('Location: index.php');
        exit;
    }

    // ------------------------------------------------------------ //
    // Regroupement des licences par type (Monoposte & Multiposte)
    // ------------------------------------------------------------ //
    // Tableau pour stocker les licences
    $monoLicenses  = [];
    $multiLicenses = [];

    // Parcourir toutes les licences par type "mono" & "multi" puis stocker,
    // dans les 2 variables des 2 tableaux
    foreach ($licenses as $license) {
        if ($license['license_type'] === 'mono') {
            $monoLicenses[] = $license;
        } elseif ($license['license_type'] === 'multi') {
            $multiLicenses[] = $license;
        }
    }

    // ------------------------------------------------------------ //
    // Calculs synthèse (affichage uniquement)
    // ------------------------------------------------------------ //

    // MONO
    // Compter le nombre de licence "monoposte"
    $monoTotal = count($monoLicenses);
    // Compter le nombre de licence "monoposte" utilisees
    $monoUsed  = count(array_filter($monoLicenses,fn($l) => $l['used_activations'] > 0));

    // MULTI
    // Compter le nombre de licence "multiposte"
    $multiTotal = count($multiLicenses);
    $multiUsed = 0;
    $multiCapacity = 0;

    // Parcourir le nombre de licence "multiposte" en stockant,
    // le nombre qui ont ete utilisees et le nombre maximum par licence (quota)
    foreach ($multiLicenses as $l) {
        $multiUsed     += (int) $l['used_activations'];
        $multiCapacity += (int) $l['max_activations'];
    }
?>

<!DOCTYPE html>
<html lang="fr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>DossiFind - Dashboard</title>
    </head>
    <body>
    <header>
        <?php require_once __DIR__ . '/../fragments/header.php'; ?>
    </header>
    <main>
    <h1>Mes licences</h1>

        <!-- ERREUR CHARGEMENT LICENCES -->
        <?php if ($monoTotal === 0 && $multiTotal === 0): ?>
            <p>Problème de chargement de vos licences</p>
        <?php else: ?>

        <!-- LICENCES MONOPOSTES -->
        <div style="border:1px solid #000; padding:16px; max-width:720px; margin-bottom:24px;">
            <h2>Licences monopostes</h2>
            <p>Total : <?= $monoTotal; ?> <?= $monoTotal > 1 ? 'licences' : 'licence'; ?></p>
            <p>Utilisées : <?= $monoUsed; ?> / <?= $monoTotal; ?></p>
            <p style="margin-top:12px;">
                <?php if ($monoTotal > 0): ?>
                    <a href="#" class="js-show-mono" title="Voir le détail">Voir le détail</a>
                <?php else: ?>
                    <span style="color:#888;" title="Aucune licence">Voir le détail</span>
                <?php endif; ?>
            </p>

            <!-- DÉTAIL MONOPOSTES (rempli par JS) -->
            <div
                id="mono-details"
                data-type="mono"
                style="display:none; margin-top:16px; padding:12px; border-top:1px dashed #999;"
            >
            <!-- contenu injecté par licence.js -->
            </div>
        </div>

        <!-- LICENCES MULTIPOSTES -->
        <div style="border:1px solid #000; padding:16px; max-width:720px;">
            <h2>Licences multipostes</h2>
            <p>Total : <?= $multiTotal; ?> <?= $multiTotal > 1 ? 'licences' : 'licence'; ?></p>
            <p>Utilisation : <?= $multiUsed; ?> / <?= $multiCapacity; ?> postes</p>
            <p style="margin-top:12px;">
                <?php if ($multiTotal > 0): ?>
                    <a href="#" class="js-show-multi" title="Voir le détail">Voir le détail</a>
                <?php else: ?>
                    <span style="color:#888;" title="Aucune licence">Voir le détail</span>
                <?php endif; ?>
            </p>
            <!-- DÉTAIL MULTIPOSTES (rempli par JS) -->
            <div id="multi-details" data-type="multi" style="display:none; margin-top:16px; padding:12px; border-top:1px dashed #999;">
                <!-- contenu injecté par licence.js -->
            </div>
        </div>
    <?php endif; ?>
    </main>
    <?php require __DIR__ . '/../fragments/footer.php'; ?>
        <script src="<?= JS_BASE_PATH ?>/assets/js/license.js"></script>
    </body>
</html>