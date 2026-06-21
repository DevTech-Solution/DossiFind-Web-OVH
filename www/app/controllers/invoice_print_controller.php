<?php
    // invoice_print_controller.php

    declare(strict_types=1);

    // Session obligatoire
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    // Vérification utilisateur connecté
    if (!isset($_SESSION['user_id'])) {
        http_response_code(403);
        exit('Accès interdit');
    }

    $userId = (int) $_SESSION['user_id'];

    // Vérification de l’ID d’achat
    $purchaseId = isset($_GET['purchase_id']) ? (int) $_GET['purchase_id'] : 0;
    if ($purchaseId <= 0) {
        http_response_code(404);
        exit('Facture introuvable');
    }

    // Connexion BDD
    require_once __DIR__ . '/../config/database_pdo.php';

    // Récupération de la facture
    $stmt = $pdo->prepare("
        SELECT
            id,
            email,
            payment_provider,
            provider_transaction_id,
            payment_method,
            payment_status,
            license_type,
            license_quantity,
            price_unit,
            price_total,
            created_at
        FROM purchases
        WHERE id = :purchase_id
        AND user_id = :user_id
        LIMIT 1
    ");

    $stmt->execute([
        ':purchase_id' => $purchaseId,
        ':user_id'     => $userId
    ]);

    $invoice = $stmt->fetch(PDO::FETCH_ASSOC);

    // Facture inexistante
    if (!$invoice) {
        http_response_code(404);
        exit('Facture introuvable');
    }

    // Chargement du template d’impression
    require __DIR__ . '/../templates/invoice_print_view.php';