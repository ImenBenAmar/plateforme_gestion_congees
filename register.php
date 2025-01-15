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
    // Récupération des données du formulaire
    $nom = $_POST['nom'];
    $entreprise = $_POST['entreprise'];
    $email = $_POST['email'];
    $mot_de_passe = $_POST['mot_de_passe'];
    $confirm_password = $_POST['confirm_password'];
    $departement = $_POST['Departement'];
    $equipe = $_POST['Equipe'];
    $poste = $_POST['Poste'];
    $role = $_POST['role'];

    // Vérification des mots de passe
    if ($mot_de_passe !== $confirm_password) {
        die("Les mots de passe ne correspondent pas.");
    }

    // Hachage du mot de passe
    $hashed_password = password_hash($mot_de_passe, PASSWORD_BCRYPT);

    // Détermination de la table cible
    $table = ($role === 'Admin') ? 'Admins' : 'Utilisateurs';

    // Insertion dans la base de données
    try {
        $stmt = $pdo->prepare("
            INSERT INTO $table (nom, entreprise, email, mot_de_passe, departement, equipe, poste) 
            VALUES (:nom, :entreprise, :email, :mot_de_passe, :departement, :equipe, :poste)
        ");
        $stmt->execute([
            ':nom' => $nom,
            ':entreprise' => $entreprise,
            ':email' => $email,
            ':mot_de_passe' => $hashed_password,
            ':departement' => $departement,
            ':equipe' => $equipe,
            ':poste' => $poste
        ]);

        echo "Inscription réussie !";
    } catch (PDOException $e) {
        if ($e->errorInfo[1] == 1062) {
            die("Erreur : Cet email est déjà utilisé.");
        } else {
            die("Erreur : " . $e->getMessage());
        }
    }
}
?>
