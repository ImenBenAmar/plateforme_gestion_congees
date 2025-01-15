<?php 
session_start(); // Assurez-vous que la session est démarrée

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    echo "Veuillez vous connecter.";
    exit();
}

$utilisateur_id = $_SESSION['user_id']; // Récupérer l'ID utilisateur depuis la session

// Connexion à la base de données
$conn = new mysqli("localhost", "root", "", "gestion_conges");

// Vérifier la connexion
if ($conn->connect_error) {
    die("Échec de la connexion : " . $conn->connect_error);
}

// Requête pour récupérer les notifications non lues
$sql_notifications = "SELECT id, date_debut, date_fin, status 
                      FROM demandes_conges 
                      WHERE utilisateur_id = ? 
                      AND status IN ('Approuvé', 'Refusé') 
                      AND vue = 1";
$stmt_notifications = $conn->prepare($sql_notifications);
$stmt_notifications->bind_param("i", $utilisateur_id);
$stmt_notifications->execute();
$result_notifications = $stmt_notifications->get_result();

$notifications = [];
while ($row = $result_notifications->fetch_assoc()) {
    $notifications[] = $row;
}

// Si les notifications sont vues, mettre à jour la colonne `vue`
if (count($notifications) > 0) {
    $update_sql = "UPDATE demandes_conges SET vue = 0
                   WHERE utilisateur_id = ? AND vue = 1";
    $stmt_update = $conn->prepare($update_sql);
    $stmt_update->bind_param("i", $utilisateur_id);
    $stmt_update->execute();
}

// Fermer la connexion
$stmt_notifications->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications de Congé</title>
    <style>
        .notification {
            background-color: #f9f9f9;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .notification.approuve {
            border-left: 5px solid green;
        }
        .notification.refuse {
            border-left: 5px solid red;
        }
    </style>
</head>
<body>
    <h1>Notifications de Congé</h1>

    <?php if (count($notifications) > 0): ?>
        <?php foreach ($notifications as $notif): ?>
            <div class="notification <?= strtolower($notif['status']) ?>">
                <p><strong>Statut :</strong> <?= htmlspecialchars($notif['status']) ?></p>
                <p><strong>Date de début :</strong> <?= htmlspecialchars($notif['date_debut']) ?></p>
                <p><strong>Date de fin :</strong> <?= htmlspecialchars($notif['date_fin']) ?></p>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p>Aucune nouvelle notification.</p>
    <?php endif; ?>
</body>
</html>

