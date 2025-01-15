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
            $sql = "SELECT * FROM admins WHERE email = ?";

            if ($stmt = $conn->prepare($sql)) {
                // Lier l'email et exécuter la requête
                $stmt->bind_param("s", $email);
                $stmt->execute();
                $result = $stmt->get_result();

                // Vérifier si l'utilisateur existe
                if ($result->num_rows > 0) {
                    $row = $result->fetch_assoc();

                    // Vérifier le mot de passe
                    if (password_verify($mot_de_passe, $row['mot_de_passe'])) {
                        // Connexion réussie
                        $_SESSION['admin_id'] = $row['id'];
                        $_SESSION['admin_nom'] = $row['nom'];
                        echo "<script>
                        window.alert('Connexion réussie!');
                        window.location.href = 'interfaceAdmin.php';
                        </script>";
                        exit(); // Toujours arrêter le script après une redirection
                    } else {
                        echo "<script>
                        window.alert('Mot de passe incorrect.');
                        window.location.href = 'loginadmin.html';
                        </script>";
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
