<?php
header('Content-Type: application/json');

// Connexion à la base de données
$conn = new mysqli('localhost', 'root', '', 'gestion_conges');

// Vérifiez la connexion
if ($conn->connect_error) {
    die(json_encode(['error' => 'Erreur de connexion à la base de données']));
}

// Récupérer les paramètres GET
$employeeId = intval($_GET['employeeId']);
$year = intval($_GET['year']);

if (!$employeeId || !$year) {
    echo json_encode(['error' => 'Paramètres invalides']);
    exit;
}

// Calculer les totaux d'absences
$query = "
    SELECT 
        type_conge, 
        COUNT(*) AS nombre_absences, 
        SUM(jours_demandes) AS total_jours
    FROM demandes_conges
    WHERE utilisateur_id = ? 
    AND YEAR(date_debut) = ? 
    AND status = 'approuvé'
    GROUP BY type_conge
";

$stmt = $conn->prepare($query);
$stmt->bind_param('ii', $employeeId, $year);
$stmt->execute();
$result = $stmt->get_result();

$totals = [];
while ($row = $result->fetch_assoc()) {
    $totals[] = $row;
}

echo json_encode($totals);

$stmt->close();
$conn->close();
?>
