<?php

$conn = new mysqli("localhost", "root", "", "gestion_conges");

// Vérifiez la connexion
if ($conn->connect_error) {
    die("Connexion échouée : " . $conn->connect_error);
}

// Récupérer les demandes avec filtres
if ($_SERVER['REQUEST_METHOD'] === 'GET' && !isset($_GET['notifications']) && !isset($_GET['approved']) && !isset($_GET['users']) && !isset($_GET['user_teams']) && !isset($_GET['request_id']) && !isset($_GET['leave_summary'])) {
    $status = isset($_GET['status']) ? $_GET['status'] : 'all';
    $team = isset($_GET['team']) ? $_GET['team'] : 'all';
    $date = isset($_GET['date']) ? $_GET['date'] : null;
    $department = isset($_GET['department']) ? $_GET['department'] : 'all';

    $query = "SELECT d.id, u.nom, d.type_conge, d.date_debut, d.date_fin, d.heures_demandes, d.jours_demandes, u.departement,u.equipe,d.status 
              FROM demandes_conges d 
              JOIN utilisateurs u ON d.utilisateur_id = u.id 
              WHERE 1=1";

    // Ajouter les filtres dynamiquement
    if ($status !== 'all') {
        $query .= " AND d.status = ?";
    }
    if ($team !== 'all') {
        $query .= " AND FIND_IN_SET(?, REPLACE(u.equipe, '+', ','))";
    }
    if (!empty($date)) {
        $query .= " AND DATE(d.date_debut) <= ? AND DATE(d.date_fin) >= ?";
    }
    if ($department !== 'all') {
        $query .= " AND u.departement = ?";
    }

    $stmt = $conn->prepare($query);

    // Lier les paramètres en fonction des filtres
    $params = [];
    $types = '';
    if ($status !== 'all') {
        $types .= 's';
        $params[] = $status;
    }
    if ($team !== 'all') {
        $types .= 's';
        $params[] = $team;
    }
    if (!empty($date)) {
        $types .= 'ss';
        $params[] = $date;
        $params[] = $date;
    }
    if ($department !== 'all') {
        $types .= 's';
        $params[] = $department;
    }

    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $demandes = $result->fetch_all(MYSQLI_ASSOC);
    echo json_encode($demandes);
}

// Mettre à jour le statut
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id']) && isset($_POST['status'])) {
    $id = $_POST['id'];
    $status = $_POST['status'];

    // Mise à jour du statut de la demande de congé
    $stmt = $conn->prepare("UPDATE demandes_conges SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $status, $id);
    $stmt->execute();

    // Vérifier si la mise à jour a réussi
    if ($stmt->affected_rows > 0) {
        // Message de notification à envoyer
        $notification_message = "La demande de congé a été mise à jour en '$status'.";

        // Retourner la réponse au frontend
        echo json_encode([
            "success" => true,
            "message" => $notification_message,
        ]);
    } else {
        echo json_encode(["success" => false, "message" => "La mise à jour a échoué."]);
    }
}

// Déconnexion de l'administrateur
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['logout'])) {
    session_start();
    session_unset();
    session_destroy();
    echo json_encode(["success" => true, "message" => "Déconnexion réussie."]);
    exit();
}

// Récupérer les notifications
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['notifications'])) {
    // Récupérer les demandes de congé en attente
    $query = "
        SELECT u.nom, d.type_conge, d.date_debut 
        FROM demandes_conges d
        JOIN utilisateurs u ON d.utilisateur_id = u.id
        WHERE d.status = 'en attente'
    ";

    $result = $conn->query($query);

    // Préparer les notifications
    $notifications = [];
    while ($row = $result->fetch_assoc()) {
        $notifications[] = "{$row['nom']} veut un congé de {$row['type_conge']} à partir du {$row['date_debut']}";
    }

    // Retourner les notifications au frontend
    echo json_encode($notifications);
}

// Récupérer les demandes approuvées pour l'analyse
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['approved'])) {
    $query = "
        SELECT u.nom, d.type_conge, d.jours_demandes 
        FROM demandes_conges d
        JOIN utilisateurs u ON d.utilisateur_id = u.id
        WHERE d.status = 'approuvé'
    ";

    $result = $conn->query($query);
    $approvedRequests = $result->fetch_all(MYSQLI_ASSOC);

    echo json_encode($approvedRequests);
}

// Récupérer les utilisateurs pour l'affectation aux équipes
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['users'])) {
    $query = "SELECT id, nom FROM utilisateurs";
    $result = $conn->query($query);
    $users = $result->fetch_all(MYSQLI_ASSOC);

    echo json_encode($users);
}

// Affecter un utilisateur à une équipe
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id']) && isset($_POST['team'])) {
    $user_id = $_POST['user_id'];
    $team = $_POST['team'];

    // Récupérer l'équipe actuelle de l'utilisateur
    $stmt = $conn->prepare("SELECT equipe FROM utilisateurs WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user) {
        $currentTeams = $user['equipe'] ? explode('+', $user['equipe']) : [];
        if (!in_array($team, $currentTeams)) {
            $currentTeams[] = $team;
        }
        $newTeams = implode('+', $currentTeams);

        // Mettre à jour l'équipe de l'utilisateur
        $stmt = $conn->prepare("UPDATE utilisateurs SET equipe = ? WHERE id = ?");
        $stmt->bind_param("si", $newTeams, $user_id);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            echo json_encode(["success" => true, "message" => "L'utilisateur a été affecté à l'équipe avec succès."]);
        } else {
            echo json_encode(["success" => false, "message" => "La mise à jour de l'équipe a échoué."]);
        }
    } else {
        echo json_encode(["success" => false, "message" => "Utilisateur non trouvé."]);
    }
}

// Récupérer les utilisateurs et leurs équipes
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['user_teams'])) {
    $query = "SELECT id, nom, equipe FROM utilisateurs";
    $result = $conn->query($query);
    $users = [];

    while ($row = $result->fetch_assoc()) {
        $users[] = [
            'nom' => $row['nom'],
            'equipes' => $row['equipe'] ? explode('+', $row['equipe']) : []
        ];
    }

    echo json_encode($users);
}

// Récupérer les détails de la demande
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['request_id'])) {
    $request_id = $_GET['request_id'];
    $query = "
        SELECT d.id, u.nom, u.equipe, d.date_debut, d.date_fin
        FROM demandes_conges d
        JOIN utilisateurs u ON d.utilisateur_id = u.id
        WHERE d.id = ?
    ";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $request_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $request = $result->fetch_assoc();

    if ($request) {
        // Séparer les équipes de l'utilisateur
        $teams = explode('+', $request['equipe']);
        $teamAbsences = [];

        foreach ($teams as $team) {
            // Récupérer les absences pour chaque équipe
            $query = "
                SELECT u.nom, d.date_debut, d.date_fin
                FROM demandes_conges d
                JOIN utilisateurs u ON d.utilisateur_id = u.id
                WHERE FIND_IN_SET(?, REPLACE(u.equipe, '+', ',')) AND (
                    (d.date_debut <= ? AND d.date_fin >= ?) OR
                    (d.date_debut <= ? AND d.date_fin >= ?)
                ) AND d.id != ?
            ";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("sssssi", $team, $request['date_debut'], $request['date_debut'], $request['date_fin'], $request['date_fin'], $request_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $absences = $result->fetch_all(MYSQLI_ASSOC);
            $totalInPeriod = count($absences);

            // Ajouter une recommandation si plus de 2 utilisateurs sont en congé
            $recommendation = $totalInPeriod > 2;

            $teamAbsences[] = [
                'team' => $team,
                'totalInPeriod' => $totalInPeriod,
                'absences' => $absences,
                'recommendation' => $recommendation
            ];
        }

        // Réponse JSON
        echo json_encode([
            'nom' => $request['nom'],
            'equipe' => $request['equipe'],
            'date_debut' => $request['date_debut'],
            'date_fin' => $request['date_fin'],
            'teamAbsences' => $teamAbsences
        ]);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Demande non trouvée']);
    }
}

// Récupérer le résumé des congés
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['leave_summary'])) {
    $team = isset($_GET['team']) ? $_GET['team'] : 'all';
    $year = isset($_GET['year']) ? $_GET['year'] : 'all';

    $query = "
        SELECT u.nom, u.equipe, 
               COALESCE(SUM(d.jours_demandes), 0) as totalCongeDonneAnnee,
               (60 - COALESCE(SUM(d.jours_demandes), 0)) as totalCongeDisponibleAnnee
        FROM utilisateurs u
        LEFT JOIN demandes_conges d ON u.id = d.utilisateur_id AND YEAR(d.date_debut) = ?
        WHERE 1=1
    ";

    if ($team !== 'all') {
        $query .= " AND FIND_IN_SET(?, REPLACE(u.equipe, '+', ','))";
    }

    $query .= " GROUP BY u.id ORDER BY totalCongeDisponibleAnnee DESC";

    $stmt = $conn->prepare($query);

    $params = [];
    $types = 'i';
    $params[] = $year === 'all' ? 0 : $year;

    if ($team !== 'all') {
        $types .= 's';
        $params[] = $team;
    }

    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    $leaveSummary = $result->fetch_all(MYSQLI_ASSOC);

    echo json_encode($leaveSummary);
}

$conn->close();
?>
