<?php
    // -------------------------------------------------------- //
    // Fichier "verify.php"
    //
    // Point d’entrée serveur pour :
    // vérifier l’existence d’une licence
    // activer une licence pour la première fois
    // vérifier le couplage licence ↔ machine
    //
    // Le serveur est la source de vérité
    // Le client (logiciel) applique uniquement la réponse
    //-------------------------------------------------------- //
    // Active le mode strict de PHP :
    // Force le respect des types déclarés
    // Empêche les conversions implicites (ex: string → int)
    // Améliore la fiabilité et la sécurité du code
    declare(strict_types=1);

    // Définit l'en-tête HTTP de la réponse :
    // Indique que la réponse est au format JSON
    // Spécifie l'encodage UTF-8 pour éviter les problèmes de caractères
    // Essentiel pour les appels AJAX / API
    header('Content-Type: application/json; charset=utf-8');

try {

    // -------------------------------------------------------- //
    // Sécurité V1 : méthode HTTP autorisée
    // -------------------------------------------------------- //
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        // Réponse volontairement neutre
        http_response_code(404);
        exit;
    }
    
    // -------------------------------------------------------- //
    // Réponse négative uniforme + délai d'attente forcee
    // -------------------------------------------------------- //
    function deny(string $message = 'Licence invalide'): void
    {
        usleep(2_000_000);

        echo json_encode([
            'status'  => 'INVALID',
            'message' => $message
        ]);
        exit;
    }

    // -------------------------------------------------------- //
    // 1) Connexion à la base de données
    // -------------------------------------------------------- //
    // Utilisation du PDO centralisé du projet
    require __DIR__ . '/../../../config/database_pdo.php';

    // -------------------------------------------------------- //
    // 2) Données reçues depuis le client
    // -------------------------------------------------------- //
    // licence_id : clé de licence utilisateur
    // machine_fingerprint : empreinte unique de la machine
    $licenceId   = $_POST['licence_id'] ?? null;
    $fingerprint = $_POST['machine_fingerprint'] ?? null;

    // Données incomplètes → requête invalide
    if (empty($licenceId) || empty($fingerprint)) {
        // Reponse negative + delai d'attente
        deny();
        exit;
    }

    // -------------------------------------------------------- //
    // 3) Récupération de la licence en base
    // -------------------------------------------------------- //
    // Recherche stricte par clé de licence
    $sqlLicense = "
        SELECT
            id,
            license_key,
            license_type,
            max_activations,
            is_active,
            revoked_at
        FROM licenses
        WHERE license_key = :license_key
        LIMIT 1
    ";

    $stmt = $pdo->prepare($sqlLicense);
    $stmt->execute([
        ':license_key' => $licenceId
    ]);

    $license = $stmt->fetch(PDO::FETCH_ASSOC);

    // Licence inexistante
    if (!$license) {
        deny('Licence inconnue');
    }

    // -------------------------------------------------------- //
    // 4) Vérification du statut de la licence
    // -------------------------------------------------------- //-
    // Licence désactivée ou révoquée
    // Licence désactivée ou révoquée
    if ((int)$license['is_active'] !== 1 || $license['revoked_at'] !== null) {
        deny('Licence désactivée');
    }

    $licenseIdDb   = (int) $license['id'];
    $maxActivations = (int) $license['max_activations'];

    // -------------------------------------------------------- //
    // 5-A) Vérification si la machine a DÉJÀ ÉTÉ DÉSAFFECTÉE
    // -------------------------------------------------------- //
    $sqlRevoked = "
        SELECT id
        FROM license_devices
        WHERE license_id = :license_id
        AND device_fingerprint = :fingerprint
        AND revoked_at IS NOT NULL
        LIMIT 1
    ";

    $stmt = $pdo->prepare($sqlRevoked);
    $stmt->execute([
        ':license_id' => $licenseIdDb,
        ':fingerprint' => $fingerprint
    ]);

    $revokedDevice = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($revokedDevice) {
        deny('Cette licence a été désactivée pour ce poste');
    }

    // -------------------------------------------------------- //
    // 5) Vérification si la machine est déjà enregistrée
    // -------------------------------------------------------- //
    // Cas : redémarrage / réinstallation / vérification classique
    $sqlDevice = "
        SELECT id
        FROM license_devices
        WHERE license_id = :license_id
        AND device_fingerprint = :fingerprint
        AND revoked_at IS NULL
        LIMIT 1
    ";

    $stmt = $pdo->prepare($sqlDevice);
    $stmt->execute([
        ':license_id' => $licenseIdDb,
        ':fingerprint' => $fingerprint
    ]);

    $device = $stmt->fetch(PDO::FETCH_ASSOC);

    // Machine déjà autorisée → simple vérification
    if ($device) {
        echo json_encode([
            'status'  => 'ACTIVE',
            'message' => 'Licence déjà activée sur cette machine'
        ]);
        exit;
    }

    // -------------------------------------------------------- //
    // 6) Nouvelle machine : Contrôle du quota des licences
    // -------------------------------------------------------- //
    // Nombre d’activations déjà utilisées
    $sqlCount = "
        SELECT COUNT(*) 
        FROM license_devices
        WHERE license_id = :license_id
        AND revoked_at IS NULL
    ";

    $stmt = $pdo->prepare($sqlCount);
    $stmt->execute([
        ':license_id' => $licenseIdDb
    ]);

    $usedActivations = (int) $stmt->fetchColumn();

    //-- Quota atteint
    if ($usedActivations >= $maxActivations) {
        deny('Nombre maximum d’activations atteint');
    }

    // -------------------------------------------------------- //
    // 7) Activation de la licence sur cette machine
    // -------------------------------------------------------- //
    // Enregistrement définitif du device
    $sqlInsert = "
        INSERT INTO license_devices (
            license_id,
            device_fingerprint,
            activated_at
        ) VALUES (
            :license_id,
            :fingerprint,
            NOW()
        )
    ";

    $stmt = $pdo->prepare($sqlInsert);
    $stmt->execute([
        ':license_id' => $licenseIdDb,
        ':fingerprint' => $fingerprint
    ]);

    // Activation validée
    echo json_encode([
        'status'  => 'ACTIVE',
        'message' => 'Licence activée avec succès'
    ]);
    exit;

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'status'  => 'ERROR',
        'message' => 'Erreur serveur'
    ]);
    exit;
}
// -------------------------------------------------------- //