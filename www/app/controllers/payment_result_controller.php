<?php
    // ------------------------------------------------------------ //
    // Fichier payment_result_controller.php
    // ------------------------------------------------------------ //
    // Contrôleur chargé :
    // de vérifier que le paiement a bien été traité
    // de récupérer les informations du paiement
    // de charger la vue de résultat du paiement
    // ------------------------------------------------------------ //

    // Sécurité minimale PHP
    declare(strict_types=1);

    // Démarrage de la session PHP
    // Nécessaire pour accéder aux données stockées lors du paiement
    session_start();

    // ------------------------------------------------------------ //
    // Sécurité : accès interdit sans résultat de paiement
    // ------------------------------------------------------------ //
    // Si la variable de session "payment_result" n'existe pas,
    // cela signifie que l'utilisateur tente d'accéder directement
    // à cette page sans passer par le flux de paiement.
    if (!isset($_SESSION['payment_result'])) {
        // Redirection vers la page d'accueil
        header('Location: index.php');
        exit;
    }

    // ------------------------------------------------------------ //
    // Récupération du statut du paiement
    // ------------------------------------------------------------ //
    // Peut contenir par exemple :
    // 'success' : paiement validé
    // 'error'   : paiement refusé ou annulé
    $paymentStatus = $_SESSION['payment_result']['status'];

    // ------------------------------------------------------------ //
    // Récupération optionnelle des données d'achat
    // ------------------------------------------------------------ //
    // Initialisation par défaut à null
    // Les données d'achat ne sont récupérées que si le paiement est validé
    $purchase = null;

    // ------------------------------------------------------------ //
    // Rattache les licences non associées à un utilisateur existant
    // ------------------------------------------------------------ //
    function attachLicensesToUser(PDO $pdo, int $userId, string $email): void
    {
        $stmt = $pdo->prepare('
            UPDATE licenses l
            JOIN purchases p ON p.id = l.purchase_id
            SET l.user_id = :user_id
            WHERE p.email = :email
            AND l.user_id IS NULL
        ');

        $stmt->execute([
            'user_id' => $userId,
            'email'   => $email
        ]);
    }

    // ------------------------------------------------------------ //
    // Si le paiement est un succès
    // ------------------------------------------------------------ //
    if ($paymentStatus === 'success') {
        // Connexion à la base de données via PDO
        require __DIR__ . '/../config/database_pdo.php';

        // Récupération du dernier achat enregistré
        // (supposé correspondre au paiement en cours)
        $stmt = $pdo->query(
            'SELECT * FROM purchases ORDER BY id DESC LIMIT 1'
        );

        // Récupération des données sous forme de tableau associatif
        $purchase = $stmt->fetch(PDO::FETCH_ASSOC);

        // ------------------------------------------------------------ //
        // Utilisateur déjà connecté -> Liaison licence
        // ------------------------------------------------------------ //
        if (
            $purchase &&
            isset($_SESSION['user_id']) &&
            isset($purchase['email'])
        ) {
            attachLicensesToUser(
                $pdo,
                (int) $_SESSION['user_id'],
                $purchase['email']
            );
        }
    }

    // ------------------------------------------------------------ //
    // Nettoyage de la session
    // ------------------------------------------------------------ //
    // Suppression des données de paiement pour éviter :
    // les doublons
    // le rechargement accidentel de la page
    unset($_SESSION['payment_result']);

    // ------------------------------------------------------------ //
    // Chargement de la vue de résultat
    // ------------------------------------------------------------ //
    // Le template utilise :
    // $paymentStatus
    // $purchase (si paiement réussi)
    require __DIR__ . '/../templates/payment_result_view.php';
    // ------------------------------------------------------------ //