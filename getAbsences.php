<?php
// Connexion à la base de données
$host = 'localhost';
$dbname = 'gestion_conges';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Récupérer l'id de l'employé passé en paramètre
    $employeeId = isset($_GET['employeeId']) ? (int)$_GET['employeeId'] : 0;
    $month = isset($_GET['month']) ? (int)$_GET['month'] : 0;
    $year = isset($_GET['year']) ? (int)$_GET['year'] : 0;

    if ($employeeId > 0 && $month > 0 && $year > 0) {
        // Requête pour récupérer les absences approuvées de l'employé dans le mois et l'année spécifiés
        $query = "
            SELECT date_debut, date_fin, type_conge
            FROM demandes_conges
            WHERE utilisateur_id = :employeeId
            AND status = 'approuvé'
            AND (
                (YEAR(date_debut) = :year AND MONTH(date_debut) = :month)
                OR (YEAR(date_fin) = :year AND MONTH(date_fin) = :month)
            )
        ";

        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':employeeId', $employeeId, PDO::PARAM_INT);
        $stmt->bindParam(':month', $month, PDO::PARAM_INT);
        $stmt->bindParam(':year', $year, PDO::PARAM_INT);
        $stmt->execute();

        $absences = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Ajouter une couleur en fonction du type de congé
        foreach ($absences as &$absence) {
            if ($absence['type_conge'] === 'Congé') {
                $absence['color'] = 'red'; // Couleur pour Congé
            } elseif ($absence['type_conge'] === 'Maladie') {
                $absence['color'] = 'orange'; // Couleur pour Maladie
            } else {
                $absence['color'] = 'gray'; // Par défaut pour autres types
            }
        }

        // Retourner les absences en format JSON
        echo json_encode($absences);
    } else {
        echo json_encode([]);
    }
} catch (PDOException $e) {
    echo "Erreur de connexion : " . $e->getMessage();
}
?>
