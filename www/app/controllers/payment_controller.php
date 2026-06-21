<?php
    // ------------------------------------------------------------ //
    // payment_controller.php
    // ------------------------------------------------------------ //
    // Rôle :
    // Point d’entrée AJAX pour PayPal (create-order / capture-order)
    // Communication serveur ↔ API PayPal
    // Enregistrement du paiement en base de données
    // Préparation du résultat pour affichage ultérieur
    // ------------------------------------------------------------ //

    // Sécurité minimale PHP
    declare(strict_types=1);

    // Connexion PDO à la base de données
    require __DIR__ . '/../config/database_pdo.php';

    // Import functions mails
    require_once __DIR__ .'/../helpers/mailer.php';

    // Démarrage de la session
    // Utilisée pour :
    // stocker les informations du checkout
    // transmettre le résultat du paiement
    session_start();

    // Chargement des secrets (Client ID / Secret PayPal)
    $secrets = require __DIR__ . '/../config/secrets.php';

    // ------------------------------------------------------------ //
    // Sécurité & headers HTTP
    // ------------------------------------------------------------ //
    // Toutes les réponses sont retournées au format JSON
    header('Content-Type: application/json');

    // ------------------------------------------------------------ //
    // Vérification de l'action demandée
    // ------------------------------------------------------------ //
    // L'action est transmise via l'URL :
    // create-order
    // capture-order
    $action = $_GET['action'] ?? null;

    // Action absente → requête invalide
    if (!$action) {
        http_response_code(400);
        echo json_encode(['error' => 'Action manquante']);
        exit;
    }

    // ------------------------------------------------------------ //
    // CONFIG PAYPAL TEST (SANDBOX)
    // ------------------------------------------------------------ //

    // Identifiants PayPal (Sandbox)
    $paypalClientId = $secrets['paypal_client_id'];
    $paypalSecret   = $secrets['paypal_secret'];

    // URL de base de l’API PayPal Sandbox
    $paypalBaseUrl  = 'https://api-m.sandbox.paypal.com';

    // ------------------------------------------------------------ //
    // Fonction utilitaire : récupération d'un token OAuth PayPal
    // ------------------------------------------------------------ //

    // Ce token est obligatoire pour :
    // créer une commande
    // capturer un paiement
    function getPayPalAccessToken(string $clientId, string $secret, string $baseUrl): string
    {
        // Initialisation de cURL
        $ch = curl_init();

        // Configuration de la requête OAuth PayPal
        curl_setopt_array($ch, [
            CURLOPT_URL            => $baseUrl . '/v1/oauth2/token',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_USERPWD        => $clientId . ':' . $secret,
            CURLOPT_POSTFIELDS     => 'grant_type=client_credentials',
            CURLOPT_HTTPHEADER     => [
                'Accept: application/json',
                'Accept-Language: en_US'
            ],
        ]);

        // Exécution de la requête
        $response = curl_exec($ch);
        unset($ch);

        // Décodage de la réponse JSON
        $data = json_decode($response, true);

        // Retour du token OAuth (ou chaîne vide si erreur)
        return $data['access_token'] ?? '';
    }

    // ------------------------------------------------------------ //
    // Génère les licences associées à un achat
    // ------------------------------------------------------------ //

    function generateLicenses(
        PDO $pdo,
        int $purchaseId,
        ?int $userId,
        string $licenseType,
        int $quantity
    ): void {

        $stmt = $pdo->prepare('
            INSERT INTO licenses (
                user_id,
                purchase_id,
                license_key,
                license_type,
                max_activations
            ) VALUES (?, ?, ?, ?, ?)
        ');

        // ------------------------------------------------------------ //
        // LICENCE MONOPOSTE
        // → quantity = nombre de licences
        // → 1 licence = 1 clé = 1 activation
        // ------------------------------------------------------------ //
        if ($licenseType === 'mono') {

            for ($i = 0; $i < $quantity; $i++) {

                // Génération d’une clé unique
                do {
                    $licenseKey = strtoupper(bin2hex(random_bytes(16)));
                    $check = $pdo->prepare('SELECT id FROM licenses WHERE license_key = ?');
                    $check->execute([$licenseKey]);
                } while ($check->fetch());

                $stmt->execute([
                    $userId,
                    $purchaseId,
                    $licenseKey,
                    'mono',
                    1
                ]);
            }

            return;
        }

        // ------------------------------------------------------------ //
        // LICENCE MULTIPOSTE
        // → quantity = nombre de postes
        // → 1 seule licence
        // → même clé, activable N fois
        // ------------------------------------------------------------ //
        if ($licenseType === 'multi') {

            // Génération d’une clé unique
            do {
                $licenseKey = strtoupper(bin2hex(random_bytes(16)));
                $check = $pdo->prepare('SELECT id FROM licenses WHERE license_key = ?');
                $check->execute([$licenseKey]);
            } while ($check->fetch());

            $stmt->execute([
                $userId,
                $purchaseId,
                $licenseKey,
                'multi',
                $quantity
            ]);

            return;
        }

        // ------------------------------------------------------------ //
        // Sécurité : type inconnu
        // ------------------------------------------------------------ //
        throw new InvalidArgumentException('Type de licence invalide');
    }

    // ------------------------------------------------------------ //
    // ACTION : CREATE ORDER
    // ------------------------------------------------------------ //
    // Création d'une commande PayPal
    // Appelée avant l'ouverture de la popup PayPal
    if ($action === 'create-order') {

        // Vérification des données de commande en session
        if (!isset($_SESSION['checkout']['total_price'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Commande invalide']);
            exit;
        }

        // Formatage du montant total (obligatoire pour PayPal)
        $totalAmount = number_format(
            (float) $_SESSION['checkout']['total_price'],
            2,
            '.',
            ''
        );

        // Récupération du token OAuth PayPal
        $accessToken = getPayPalAccessToken($paypalClientId, $paypalSecret, $paypalBaseUrl);

        // Payload de création de commande
        $payload = [
            'intent' => 'CAPTURE',
            'purchase_units' => [[
                'amount' => [
                    'currency_code' => 'EUR',
                    'value' => $totalAmount
                ]
            ]]
        ];

        // Initialisation de la requête cURL
        $ch = curl_init();

        // Configuration de la requête PayPal
        curl_setopt_array($ch, [
            CURLOPT_URL            => $paypalBaseUrl . '/v2/checkout/orders',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $accessToken
            ],
        ]);

        // Exécution de la requête
        $response = curl_exec($ch);
        unset($ch);

        // Décodage de la réponse PayPal
        $data = json_decode($response, true);

        // Retour de l'ID de commande PayPal au JavaScript
        echo json_encode([
            'orderID' => $data['id'] ?? null
        ]);
        exit;
    }

    // ------------------------------------------------------------ //
    // ACTION : CAPTURE ORDER
    // ------------------------------------------------------------ //

    if ($action === 'capture-order') {

        // Lecture du body JSON
        $rawInput = file_get_contents('php://input');
        $input = json_decode($rawInput, true);

        // Récupération sécurisée de l'orderID
        $orderId =
            $input['orderID']
            ?? $_POST['orderID']
            ?? $_GET['orderID']
            ?? null;

        if (!$orderId) {
            http_response_code(400);
            echo json_encode(['error' => 'orderID manquant']);
            exit;
        }

        // OAuth PayPal
        $accessToken = getPayPalAccessToken(
            $paypalClientId,
            $paypalSecret,
            $paypalBaseUrl
        );

        // Capture PayPal
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $paypalBaseUrl . "/v2/checkout/orders/{$orderId}/capture",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $accessToken
            ],
        ]);

        $response = curl_exec($ch);
        unset($ch);

        $data = json_decode($response, true);

        if (!isset($data['purchase_units'][0]['payments']['captures'][0])) {
            echo json_encode(['status' => 'FAILED']);
            exit;
        }

        $capture = $data['purchase_units'][0]['payments']['captures'][0];

        if (($capture['status'] ?? '') !== 'COMPLETED') {
            echo json_encode(['status' => 'FAILED']);
            exit;
        }

        $paypalTransactionId = $capture['id'];

        try {

            $pdo->beginTransaction();

            if (!isset($_SESSION['checkout'])) {
                throw new RuntimeException('Checkout manquant');
            }

            $checkout = $_SESSION['checkout'];
            $email = strtolower(trim($checkout['email']));
            $licenseType = $checkout['license_type'];
            $quantity    = (int) $checkout['quantity'];
            $priceUnit   = (float) $checkout['unit_price'];
            $priceTotal  = (float) $checkout['total_price'];
            $userId = $_SESSION['user_id'] ?? null;

            // INSERT ACHAT
            $stmt = $pdo->prepare('
                INSERT INTO purchases (
                    user_id,
                    email,
                    payment_provider,
                    provider_transaction_id,
                    payment_method,
                    payment_status,
                    license_type,
                    license_quantity,
                    price_unit,
                    price_total
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ');

            $stmt->execute([
                $userId,
                $email,
                'paypal',
                $paypalTransactionId,
                'paypal',
                'paid',
                $licenseType,
                $quantity,
                $priceUnit,
                $priceTotal
            ]);

            $purchaseId = (int) $pdo->lastInsertId();

            //------------------------------------ //
            // GÉNÉRATION DES LICENCES (USER NULL)
            generateLicenses(
                $pdo,
                $purchaseId,
                $userId,
                $licenseType,
                $quantity
            );

            $pdo->commit();
            
            //------------------------------------ //
            // ENVOI DU MAIL DE CONFIRMATION D'ACHAT
            try {
                sendPurchaseConfirmationMail(
                    $email,
                    (string) $purchaseId,
                    (float) $priceTotal,
                );
            } catch (Throwable $e) {
                error_log('Erreur mail achat : ' . $e->getMessage());
            }
            //------------------------------------ //
            
            unset($_SESSION['checkout']);

            $_SESSION['payment_result'] = ['status' => 'success'];

            echo json_encode(['status' => 'COMPLETED']);
            exit;

        } catch (Throwable $e) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            error_log($e->getMessage());

            http_response_code(500);
            echo json_encode([
                'status'  => 'ERROR',
                'message' => $e->getMessage()
            ]);
            exit;
        }
    }

    // ------------------------------------------------------------ //
    // ACTION INCONNUE
    // ------------------------------------------------------------ //
    http_response_code(404);
    echo json_encode(['error' => 'Action inconnue']);
    exit;
?>