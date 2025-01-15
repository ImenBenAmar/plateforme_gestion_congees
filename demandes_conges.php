<?php
session_start();

// Afficher toutes les erreurs pour faciliter le débogage
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Vérifier si les données sont envoyées via GET
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    echo "Méthode GET détectée. Traitement des données...\n";

    // Récupérer les données du formulaire
    $utilisateur_id = $_GET['utilisateur_id'] ?? null;
    $type_conge = 'Maladie';  // Forcer la valeur "Maladie" pour tous les choix
    $date_debut = $_GET['date_debut'] ?? null;
    $date_fin = $_GET['date_fin'] ?? null;
    $heures_demandes = $_GET['heures_demandes'] ?? null;
    $jours_demandes = $_GET['jours_demandes'] ?? null;
    $retour_travail = isset($_GET['retour_travail']) ? 1 : 0;

    // Afficher les valeurs récupérées
    echo "utilisateur_id: $utilisateur_id\n";
    echo "type_conge: $type_conge\n";
    echo "date_debut: $date_debut\n";
    echo "date_fin: $date_fin\n";
    echo "heures_demandes: $heures_demandes\n";
    echo "jours_demandes: $jours_demandes\n";
    echo "retour_travail: $retour_travail\n";

    // Connexion à la base de données
    $host = 'localhost';
    $dbname = 'gestion_conges';
    $username = 'root';
    $password = '';

    try {
        echo "Tentative de connexion à la base de données...\n";
        $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        echo "Connexion réussie à la base de données.\n";

        // Requête pour insérer la demande de congé dans la base de données
        $stmt_insert = $pdo->prepare("
            INSERT INTO demandes_conges (
                utilisateur_id,
                type_conge,
                date_debut,
                date_fin,
                heures_demandes,
                jours_demandes,
                retour_travail,
                vue
            ) VALUES (
                :utilisateur_id,
                :type_conge,
                :date_debut,
                :date_fin,
                :heures_demandes,
                :jours_demandes,
                :retour_travail,
                0  -- vue définie explicitement à 0
            )
        ");
        $stmt_insert->bindParam(':utilisateur_id', $utilisateur_id, PDO::PARAM_INT);
        $stmt_insert->bindParam(':type_conge', $type_conge, PDO::PARAM_STR);
        $stmt_insert->bindParam(':date_debut', $date_debut);
        $stmt_insert->bindParam(':date_fin', $date_fin);
        $stmt_insert->bindParam(':heures_demandes', $heures_demandes, PDO::PARAM_INT);
        $stmt_insert->bindParam(':jours_demandes', $jours_demandes, PDO::PARAM_INT);
        $stmt_insert->bindParam(':retour_travail', $retour_travail, PDO::PARAM_BOOL);
        $stmt_insert->execute();

        echo "Tentative de redirection vers recap.php...";
        header('Location:recap.html' );
        exit;
        
    } catch (PDOException $e) {
        echo "Erreur de connexion à la base de données : " . $e->getMessage();
        exit;
    }

} else {
    echo "Erreur : Méthode de requête non autorisée.\n";
    exit;
}
?>
