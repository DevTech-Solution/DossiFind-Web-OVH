<?php
    // ------------------------------------------------------------ //
    // Fichier : license_controller.php
    // Rôle    : API licences (dashboard)
    // ------------------------------------------------------------ //

    // Active le typage strict de PHP
    declare(strict_types=1);

    // Démarre la session si elle n'est pas déjà active
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Indique que la réponse sera au format JSON
    header('Content-Type: application/json');

    // ----------------------------------------------------------------------------- //
    // Sécurité : vérifie que l'utilisateur est authentifié
    // ----------------------------------------------------------------------------- //
    if (empty($_SESSION['user_id'])) {
        echo json_encode([
            'success' => false,
            'error'   => 'Utilisateur non authentifié'
        ]);
        exit;
    }

    // Récupération de l'identifiant utilisateur depuis la session
    $userId = (int) $_SESSION['user_id'];

    // Récupère l'action demandée via l'URL (?action=...)
    // Si aucune action n'est fournie, on considère par défaut
    // qu'il s'agit de l'action "list" (affichage des licences)
    $action = $_GET['action'] ?? $_POST['action'] ?? 'list';

    // ----------------------------------------------------------------------------- //
    // Connexion à la base de données via PDO
    // ----------------------------------------------------------------------------- //
    try {
        // Chargement du fichier de configuration PDO
        require __DIR__ . '/../config/database_pdo.php';
    } catch (Throwable $e) {
        // Erreur de connexion à la base de données
        echo json_encode([
            'success' => false,
            'error'   => 'Erreur de connexion à la base de données'
        ]);
        exit;
    }

    // ----------------------------------------------------------------------------- //
    // ACTION : Mise à jour du nom du poste (device)
    // ----------------------------------------------------------------------------- //
    if ($action === 'updateDeviceName') {

        $input = json_decode(file_get_contents('php://input'), true);

        $deviceId   = isset($input['device_id']) ? (int) $input['device_id'] : 0;
        $deviceName = isset($input['device_name']) ? trim($input['device_name']) : '';

        if ($deviceId <= 0 || $deviceName === '') {
            echo json_encode([
                'success' => false,
                'error'   => 'Données invalides'
            ]);
            exit;
        }

        $sqlUpdate = '
            UPDATE license_devices ld
            INNER JOIN licenses l ON l.id = ld.license_id
            SET ld.device_name = :device_name
            WHERE ld.id = :device_id
            AND l.user_id = :user_id
            AND ld.revoked_at IS NULL
        ';

        $stmt = $pdo->prepare($sqlUpdate);
        $stmt->execute([
            'device_name' => $deviceName,
            'device_id'   => $deviceId,
            'user_id'     => $userId
        ]);

        echo json_encode([
            'success' => true
        ]);
        exit;
    }


    // ------------------------------------------------------------ //
    // ACTION : GET LICENSE DEVICES
    // ------------------------------------------------------------ //

    if ($action === 'get_license_devices') {

        // Sécurité : utilisateur connecté
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['error' => 'Non autorisé.']);
            exit;
        }

        $licenseId = (int) ($_POST['license_id'] ?? 0);
        if ($licenseId <= 0) {
            echo json_encode(['error' => 'Licence invalide.']);
            exit;
        }

        // Vérifie que la licence appartient bien à l'utilisateur
        $stmt = $pdo->prepare('
            SELECT id, max_activations
            FROM licenses
            WHERE id = :license_id AND user_id = :user_id
            LIMIT 1
        ');
        $stmt->execute([
            'license_id' => $licenseId,
            'user_id'    => $_SESSION['user_id']
        ]);

        $license = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$license) {
            echo json_encode(['error' => 'Licence introuvable.']);
            exit;
        }

        // Récupération des devices liés à la licence
        $stmt = $pdo->prepare('
            SELECT
                id,
                device_name,
                activated_at,
                revoked_at
            FROM license_devices
            WHERE license_id = :license_id
            ORDER BY activated_at ASC
        ');
        $stmt->execute([
            'license_id' => $licenseId
        ]);

        $devices = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $devices[] = [
                'id'            => (int) $row['id'],
                'device_name'   => $row['device_name'] ?: 'Poste inconnu',
                'activated_at'  => $row['activated_at'],
                'revoked_at'    => $row['revoked_at'],
                'status'        => $row['revoked_at'] === null ? 'ACTIVE' : 'REVOKED'
            ];
        }

        echo json_encode([
            'status'          => 'SUCCESS',
            'max_activations' => (int) $license['max_activations'],
            'devices'         => $devices
        ]);
        exit;
    }

    // ------------------------------------------------------------ //
    // ACTION : REVOKE DEVICE
    // ------------------------------------------------------------ //
    if ($action === 'revoke_device') {

        // Sécurité : utilisateur connecté
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['error' => 'Non autorisé.']);
            exit;
        }

        $licenseId = (int) ($_POST['license_id'] ?? 0);
        $deviceId  = (int) ($_POST['device_id'] ?? 0);

        if ($licenseId <= 0 || $deviceId <= 0) {
            echo json_encode(['error' => 'Paramètres invalides.']);
            exit;
        }

        // Vérifie que le device appartient à une licence de l'utilisateur
        $stmt = $pdo->prepare('
            SELECT ld.id
            FROM license_devices ld
            JOIN licenses l ON l.id = ld.license_id
            WHERE ld.id = :device_id
            AND l.id = :license_id
            AND l.user_id = :user_id
            AND ld.revoked_at IS NULL
            LIMIT 1
        ');
        $stmt->execute([
            'device_id'  => $deviceId,
            'license_id' => $licenseId,
            'user_id'    => $_SESSION['user_id']
        ]);

        if (!$stmt->fetch()) {
            echo json_encode(['error' => 'Poste introuvable ou déjà désaffecté.']);
            exit;
        }

        // Désaffectation définitive du poste
        $stmt = $pdo->prepare('
            UPDATE license_devices
            SET revoked_at = NOW()
            WHERE id = :device_id
            LIMIT 1
        ');
        $stmt->execute([
            'device_id' => $deviceId
        ]);

        echo json_encode([
            'status'  => 'SUCCESS',
            'message' => 'Poste désaffecté avec succès.'
        ]);
        exit;
    }

    // ------------------------------------------------------------ //
    // ACTION : LIST
    // ------------------------------------------------------------ //
    if ($action === 'list') {


        $type = $_GET['type'] ?? null;

        if (!in_array($type, ['mono', 'multi'], true)) {
            echo json_encode([
                'success' => false,
                'error'   => 'Type de licence invalide'
            ]);
            exit;
        }

        // ----------------------------------------------------------------------------- //
        // Requête SQL : récupération des licences utilisateur
        // ----------------------------------------------------------------------------- //
        $sql = '
            SELECT
                l.id,
                l.license_key,
                l.license_type,
                l.max_activations,
                l.is_active,
                l.revoked_at,
                COUNT(ld.id) AS used_activations,
                MAX(ld.activated_at) AS last_activated_at
            FROM licenses l
            LEFT JOIN license_devices ld
                ON ld.license_id = l.id
                AND ld.revoked_at IS NULL
            WHERE l.user_id = :user_id
            AND l.license_type = :type
            GROUP BY l.id
            ORDER BY l.id ASC
        ';

        // Préparation sécurisée de la requête
        $stmt = $pdo->prepare($sql);

        // Exécution avec paramètres liés
        $stmt->execute([
            'user_id' => $userId,
            'type'    => $type
        ]);

        // Récupération de toutes les licences
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);


        // ----------------------------------------------------------------------------- //
        // Récupération des devices actifs liés aux licences de l'utilisateur
        // ----------------------------------------------------------------------------- //
        $sqlDevices = '
            SELECT
                ld.id,
                ld.license_id,
                ld.device_name,
                ld.device_fingerprint
            FROM license_devices ld
            INNER JOIN licenses l ON l.id = ld.license_id
            WHERE l.user_id = :user_id
            AND ld.revoked_at IS NULL
        ';

        // Prépare la requête SQL afin de sécuriser l'accès aux données
        $stmtDevices = $pdo->prepare($sqlDevices);

        // Exécute la requête en liant l'identifiant de l'utilisateur connecté
        $stmtDevices->execute([
            'user_id' => $userId
        ]);

        // Récupère tous les devices actifs sous forme de tableau associatif
        $deviceRows = $stmtDevices->fetchAll(PDO::FETCH_ASSOC);

        // ----------------------------------------------------------------------------- //
        // Regroupement des devices par licence afin de faciliter l'affichage
        // et la gestion des activations multipostes
        // ----------------------------------------------------------------------------- //
        $devicesByLicense = [];

        // Parcourt chaque device récupéré depuis la base de données
        foreach ($deviceRows as $device) {

            // Récupère et caste l'identifiant de la licence associée au device
            $licenseId = (int) $device['license_id'];

            // Initialise le tableau de la licence si elle n'existe pas encore
            if (!isset($devicesByLicense[$licenseId])) {
                $devicesByLicense[$licenseId] = [];
            }

            // Ajoute le device à la licence correspondante
            $devicesByLicense[$licenseId][] = [
                'id'                 => (int) $device['id'],          // Identifiant du device
                'device_name'        => $device['device_name'],       // Nom du poste (modifiable par l'utilisateur)
                'device_fingerprint' => $device['device_fingerprint'] // Empreinte unique du poste
            ];
        }

        // ----------------------------------------------------------------------------- //
        // Calcul des STATUTS MÉTIER
        // ----------------------------------------------------------------------------- //
        // Tableau final contenant les licences formatées
        // (prêt à être renvoyé à l’API / au front)
        $licenses = [];

        // Parcours de chaque ligne brute récupérée depuis la base de données
        foreach ($rows as $row) {

            // string ou null
            $lastActivatedAt = $row['last_activated_at']; 

            // Nombre d’activations déjà utilisées (cast explicite en entier)
            $used = (int) $row['used_activations'];

            // Nombre maximum d’activations autorisées pour la licence
            $max  = (int) $row['max_activations'];

            // Récupère et caste en entier l’identifiant unique de la licence depuis la base de données
            $licenseId = (int) $row['id'];

            // --------------------------------------------------------------------- //
            // Détermination du statut métier de la licence
            // Ce statut est utilisé côté UI pour l’affichage et les actions possibles
            // --------------------------------------------------------------------- //

            // Licence désactivée manuellement ou révoquée
            if ((int)$row['is_active'] === 0 || $row['revoked_at'] !== null) {
                $status = 'INACTIVE';

            // Licence active mais encore jamais utilisée
            } elseif ($used === 0) {
                $status = 'ACTIVE_UNUSED';

            // Licence totalement utilisée (aucune activation restante)
            } elseif ($used >= $max) {
                $status = 'ACTIVE_FULL';

            // Licence active avec au moins une activation utilisée,
            // mais pas encore totalement consommée
            } else {
                $status = 'ACTIVE_PARTIAL';
            }

            // --------------------------------------------------------------------- //
            // Construction de la structure de licence normalisée
            // (clé, type, compteurs et statut métier)
            // --------------------------------------------------------------------- //
            $licenses[] = [
                'id'                => $licenseId,
                'license_key'       => $row['license_key'],
                'license_type'      => $row['license_type'],
                'used_activations'  => $used,
                'max_activations'   => $max,
                'status'            => $status,
                'last_activated_at' => $lastActivatedAt,
                'devices'           => $devicesByLicense[$licenseId] ?? []
            ];
        }

        // ----------------------------------------------------------------------------- //
        // Réponse JSON retournée au JavaScript
        // ----------------------------------------------------------------------------- //
        echo json_encode([
            'success'  => true,
            'licenses' => $licenses
        ]);
        exit;
    }