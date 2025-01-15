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
    $nom = isset($_POST['nom']) ? trim($_POST['nom']) : '';
    $entreprise = isset($_POST['entreprise']) ? trim($_POST['entreprise']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $mot_de_passe = isset($_POST['mot_de_passe']) ? $_POST['mot_de_passe'] : '';
    $confirm_password = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';
    $departement = isset($_POST['Departement']) ? $_POST['Departement'] : '';
    $equipe = isset($_POST['Equipe']) ? trim($_POST['Equipe']) : '';
    $poste = isset($_POST['Poste']) ? trim($_POST['Poste']) : '';

    // Vérification simple pour s'assurer que les champs ne sont pas vides
    if (empty($nom) || empty($entreprise) || empty($email) || empty($mot_de_passe) || empty($confirm_password) || empty($departement) || empty($equipe) || empty($poste)) {
        echo "Tous les champs sont obligatoires.";
    } elseif ($mot_de_passe !== $confirm_password) {
        echo "<script>
        window.alert('Les mots de passe ne correspondent pas.');
        window.location.href = 'loginadmin.html';
        </script>";
    } else {
        // Validation de l'email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
         echo "<script>
        window.alert('L'email fourni n'est pas valide.');
        window.location.href = 'loginadmin.html';
      </script>";
        } else {
            // Vérifier si l'email existe déjà dans la base de données
            $sql_check = "SELECT * FROM admins WHERE email = ?";
            if ($stmt_check = $conn->prepare($sql_check)) {
                $stmt_check->bind_param("s", $email);
                $stmt_check->execute();
                $stmt_check->store_result();

                if ($stmt_check->num_rows > 0) {
                    echo "<script>
                    window.alert('Un compte existe déjà avec cet email.');
                    window.location.href = 'loginadmin.html';
                  </script>";
                } else {
                    // Hachage du mot de passe pour la sécurité
                    $mot_de_passe_hache = password_hash($mot_de_passe, PASSWORD_DEFAULT);

                    // Préparer la requête d'insertion
                    $sql = "INSERT INTO admins (nom, entreprise, email, mot_de_passe, departement, equipe, poste) VALUES (?, ?, ?, ?, ?, ?, ?)";

                    if ($stmt = $conn->prepare($sql)) {
                        $stmt->bind_param("sssssss", $nom, $entreprise, $email, $mot_de_passe_hache, $departement, $equipe, $poste);

                        if ($stmt->execute()) {
                            // Récupérer l'ID de l'admin nouvellement créé
                            $admin_id = $stmt->insert_id;

                            // Enregistrer l'ID et le nom de l'admin dans la session
                            $_SESSION['admin_id'] = $admin_id;
                            $_SESSION['admin_nom'] = $nom;
                            echo "<script>
                            window.alert('Compte créé avec succès!');
                            window.location.href = 'loginadmin.html';
                            </script>";
                            exit();
                        } else {
                            echo "Erreur lors de la création du compte: " . $stmt->error;
                        }
                        $stmt->close();
                    } else {
                        echo "Erreur de préparation de la requête : " . $conn->error;
                    }
                }
                $stmt_check->close();
            } else {
                echo "Erreur de préparation de la requête : " . $conn->error;
            }
        }
    }
}

// Fermer la connexion
$conn->close();
?>
