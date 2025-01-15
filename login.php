<?php
// Connexion à la base de données
$host = 'localhost';
$dbname = 'gestion_conges';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $mot_de_passe = $_POST['mot_de_passe'];

    // Recherche dans les deux tables (Admins et Utilisateurs)
    $roles = ['Admins', 'Utilisateurs'];
    $authenticated = false;
    foreach ($roles as $role) {
        $stmt = $pdo->prepare("SELECT * FROM $role WHERE email = :email");
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($mot_de_passe, $user['mot_de_passe'])) {
            $authenticated = true;
            echo "Connexion réussie en tant que " . (($role === 'Admins') ? 'Responsable' : 'Employé') . "!";
            break;
        }
    }

    if (!$authenticated) {
        echo "Erreur : Email ou mot de passe incorrect.";
    }
}
?>
