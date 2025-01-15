<?php
// Connexion à la base de données
$host = 'localhost';
$dbname = 'gestion_conges';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Récupérer tous les employés
    $query = "SELECT id, nom FROM utilisateurs";
    $stmt = $pdo->prepare($query);
    $stmt->execute();

    $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Générer les options HTML pour le select
    foreach ($employees as $employee) {
        echo "<option value=\"{$employee['id']}\">{$employee['nom']}</option>";
    }
} catch (PDOException $e) {
    echo "Erreur de connexion : " . $e->getMessage();
}
?>
