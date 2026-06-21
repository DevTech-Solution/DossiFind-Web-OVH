<?php
    // ------------------------------------------------------------ //
    // Fichier "payment_checkout_controller.php"
    // Recuperation des elements du template "order_setup_view.php"
    // puis redirection vers le template "payment_checkout_view.php"
    // ------------------------------------------------------------ //

    // Active le typage strict PHP
    declare(strict_types=1);

    // Import PDO
    require_once __DIR__ . '/../config/database_pdo.php';

    // Démarre la session pour gérer les données temporaires (erreurs, checkout)
    session_start();

    // ------------------------------------------------------------ //
    // 1. Vérifier que le formulaire a bien été soumis
    // ------------------------------------------------------------ //

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        // Accès direct interdit
        header('Location: index.php?page=order_setup');
        exit;
    }

    // ------------------------------------------------------------ //
    // 2. Récupération des données (POST)
    // ------------------------------------------------------------ //

    if (isset($_POST['license_type'])) {
        $licenseType = $_POST['license_type'];
    } else {
        $licenseType = null;
    }

    if (isset($_POST['quantity'])) {
        $quantity = $_POST['quantity'];
    } else {
        $quantity = null;
    }

    if (isset($_POST['email'])) {
        $email = $_POST['email'];
    } else {
        $email = null;
    }

    // Verification saisie mail
    if ($email !== null) {
        $email = strtolower(trim($email));
    }

    // ------------------------------------------------------------ //
    // 3. Validation des données
    // ------------------------------------------------------------ //

    $errors = [];

    // Type de licence
    if (!in_array($licenseType, ['mono', 'multi'], true)) {
        $errors[] = 'Type de licence invalide.';
    }

    // Quantité
    if (!is_numeric($quantity) || (int)$quantity < 1) {
        $errors[] = 'Quantité invalide.';
    } else {
        $quantity = (int)$quantity;
    }

    // Règle métier : une licence multiposte impose un minimum de 2 licences (sécurité côté serveur)
    if ($licenseType === 'multi' && $quantity < 2) {
        $errors[] = 'Une licence multiposte nécessite au minimum 2 licences.';
    }

    // Email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Adresse email invalide.';
    }

    // ------------------------------------------------------------ //
    // Vérification métier : email mort (history sans être actif)
    // ------------------------------------------------------------ //

    if (empty($errors)) {

        // Email actif ?
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => $email]);
        $isActiveUser = $stmt->fetch();

        // Email dans l'historique ?
        $stmt = $pdo->prepare(
            'SELECT id FROM user_email_history WHERE email = :email LIMIT 1'
        );
        $stmt->execute(['email' => $email]);
        $isInHistory = $stmt->fetch();

        /*
            RÈGLE :
            actif → OK
            pas actif + historique → BLOQUÉ
            - nulle part → OK
        */
        if (!$isActiveUser && $isInHistory) {
            $errors[] = 'Cette adresse email a déjà été utilisée. Veuillez utiliser une adresse email active.';
        }
    }

    // ------------------------------------------------------------ //
    // 4. Gestion des erreurs
    // ------------------------------------------------------------ //

    if (!empty($errors)) {

        $_SESSION['errors'] = $errors;

        header('Location: index.php?page=order_setup');
        exit;
    }

    // ------------------------------------------------------------ //
    // 5. Calcul du prix (degressif)
    // ------------------------------------------------------------ //

    // Prix de base
    $basePrice = 19.90;

    // Détermination du prix unitaire selon la quantité
    if ($quantity >= 10) {
        $pricePerLicense = 13.90;
    } elseif ($quantity >= 5) {
        $pricePerLicense = 15.90;
    } elseif ($quantity >= 2) {
        $pricePerLicense = 17.90;
    } else {
        $pricePerLicense = $basePrice;
    }

    // Total prix apres degressif
    $totalPrice = $pricePerLicense * $quantity;

    // ------------------------------------------------------------ //
    // 6. Stockage temporaire (session)
    // ------------------------------------------------------------ //
    $_SESSION['checkout'] = [
        'license_type' => $licenseType,
        'quantity'     => $quantity,
        'email'        => $email,
        'unit_price'   => $pricePerLicense,
        'total_price'  => $totalPrice,
    ];

    // ------------------------------------------------------------ ///
    // 7. Mise à disposition pour la vue
    // ------------------------------------------------------------ //
    $checkout = $_SESSION['checkout'];

    // ------------------------------------------------------------ //
    // 8. Redirection vers le template "payment_checkout_view.php"
    // ------------------------------------------------------------ //
    require_once __DIR__ . '/../templates/payment_checkout_view.php';
?>