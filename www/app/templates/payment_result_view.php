<?php
    // ------------------------------------------------------------ //
    // Fichier "payment_result_view.php"
    // Affichage du resultat de paiement
    // Si paiement validé, affichage du bouton pour se creer un compte ou se connecter
    // Alors bouton retour vers order_setup_controller.php ou index_controller.php
    // ------------------------------------------------------------ //
    // Sécurité minimale
    declare(strict_types=1);
    // ------------------------------------------------------------ //
    // Sécurité : accès uniquement via le contrôleur
    if (!isset($paymentStatus)) {
        header('Location: index.php');
        exit;
    }
    // ------------------------------------------------------------ //
?>

<!DOCTYPE html>
<html lang="fr">
    <head>
        <meta charset="UTF-8">
        <title>DossiFind - Confirmation de paiement</title>
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
    </head>
    <body>
    <header>
        <?php require_once __DIR__ . '/../fragments/header.php'; ?>
    </header>
    <main>
        <!-- Paiement valide -->
        <?php if ($paymentStatus === 'success' && isset($purchase)): ?>
            <h1>Paiement confirmé</h1>
            <p>Merci pour votre achat.</p>
            <p>Votre paiement a bien été validé.</p>
            <section>
                <h2>Récapitulatif de votre commande</h2>
                <ul>
                    <li>
                        <strong>Email :</strong>
                        <?= htmlspecialchars($purchase['email'], ENT_QUOTES, 'UTF-8'); ?>
                    </li>
                    <li>
                        <strong>Type de licence :</strong>
                        <?= htmlspecialchars($purchase['license_type'], ENT_QUOTES, 'UTF-8'); ?>
                    </li>
                    <li>
                        <strong>Nombre de licences :</strong>
                        <?= (int) $purchase['license_quantity']; ?>
                    </li>
                    <li>
                        <strong>Prix unitaire :</strong>
                        <?= number_format((float)$purchase['price_unit'], 2, ',', ' '); ?> €
                    </li>
                    <li>
                        <strong>Total payé :</strong>
                        <?= number_format((float)$purchase['price_total'], 2, ',', ' '); ?> €
                    </li>
                </ul>
            </section>
            <section>
                <?php
                    // Récupération du nombre de licences achetées et conversion explicite en entier
                    $qty = (int) $purchase['license_quantity'];

                    // Détermination du pluriel : vrai si plus d'une licence
                    $isPlural = $qty > 1;

                    // Libellé dynamique selon le nombre de licences (singulier / pluriel)
                    $labelLicenses = $isPlural ? 'vos licences' : 'votre licence';

                    // Forme du verbe adaptée au singulier ou au pluriel
                    $verbAssociated = $isPlural ? 'ont été associées' : 'a été associée';
                ?>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <!-- Utilisateur déjà connecté -->
                    <p>
                        <?= ucfirst($labelLicenses); ?> <?= $verbAssociated; ?> automatiquement à votre compte.
                    </p>
                    <a href="index.php?page=dashboard">Accéder à mon dashboard</a>

                <?php else: ?>
                    <!-- Utilisateur non connecté -->
                    <p>Vous pouvez maintenant créer votre compte ou vous connecter pour gérer votre licence.</p>
                    <!-- Btn login -->
                    <a href="index.php?page=login">Créer un compte/Se connecter</a>
                <?php endif; ?>
            </section>
        <?php endif; ?>
    </main>
    <?php require __DIR__ . '/../fragments/footer.php'; ?>
    </body>
</html>