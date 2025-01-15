<?php
session_start();

// Connexion à la base de données
$servername = "localhost";
$username = "root"; // Remplacez par votre nom d'utilisateur
$password = ""; // Remplacez par votre mot de passe
$dbname = "gestion_conges"; // Nom de la base de données

// Créer la connexion
$conn = new mysqli($servername, $username, $password, $dbname);

// Vérifier la connexion
if ($conn->connect_error) {
    die("La connexion a échoué: " . $conn->connect_error);
}

// Vérifier si les données sont envoyées via POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Récupérer et nettoyer les données du formulaire
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $mot_de_passe = isset($_POST['mot_de_passe']) ? $_POST['mot_de_passe'] : '';

    // Vérification simple pour s'assurer que les champs ne sont pas vides
    if (empty($email) || empty($mot_de_passe)) {
        echo "Veuillez remplir tous les champs.";
    } else {
        // Validation de l'email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo "L'email fourni n'est pas valide.";
        } else {
            // Rechercher l'utilisateur dans la base de données
            $sql = "SELECT * FROM utilisateurs WHERE email = ?";

            if ($stmt = $conn->prepare($sql)) {
                // Lier l'email et exécuter la requête
                $stmt->bind_param("s", $email);
                $stmt->execute();
                $result = $stmt->get_result();

                // Vérification si la connexion est réussie
if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    
    // Vérifier le mot de passe
    if (password_verify($mot_de_passe, $row['mot_de_passe'])) {
        // Stocker les informations de l'utilisateur dans la session
        $_SESSION['user_id'] = $row['id'];
        $_SESSION['nom'] = $row['nom'];
        $_SESSION['prenom'] = $row['prenom'];
        $_SESSION['poste'] = $row['poste'];
        $_SESSION['departement'] = $row['departement'];

        // Rediriger l'utilisateur vers la page de l'interface utilisateur
        header("Location: user.php");
        exit(); // Toujours arrêter le script après une redirection
    } else {
        echo "Mot de passe incorrect.";
    }
} else {
    echo "Aucun utilisateur trouvé avec cet email.";
}

                // Fermer la déclaration
                $stmt->close();
            } else {
                echo "Erreur de préparation de la requête : " . $conn->error;
            }
        }
    }
}

// Fermer la connexion
$conn->close();
?>
