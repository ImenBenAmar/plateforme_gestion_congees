<?php
session_start(); // Démarrer la session PHP

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    echo "Utilisateur non connecté.";
    exit();
}

// Vérifier si l'ID utilisateur est fourni
if (!isset($_GET['utilisateur_id'])) {
    echo "ID utilisateur manquant.";
    exit();
}

$utilisateur_id = (int) $_GET['utilisateur_id']; // Récupérer l'ID utilisateur depuis l'URL
$status_filter = isset($_GET['status']) ? $_GET['status'] : ""; // Récupérer le filtre de statut

// Connexion à la base de données
$conn = new mysqli("localhost", "root", "", "gestion_conges");

// Vérification de la connexion
if ($conn->connect_error) {
    die("Erreur de connexion à la base de données : " . $conn->connect_error);
}

// Requête SQL pour récupérer les demandes de congé avec ou sans filtre
$sql = "SELECT 
            type_conge,
            date_debut,
            date_fin,
            heures_demandes,
            jours_demandes,
            status
        FROM 
            demandes_conges
        WHERE 
            utilisateur_id = ?";

// Ajouter un filtre de statut si un statut est spécifié
if (!empty($status_filter)) {
    $status_filter = strtolower(trim($status_filter)); // Mettre la valeur du filtre en minuscule et supprimer les espaces
    $sql .= " AND LOWER(status) = ?";
}

$stmt = $conn->prepare($sql);

// Si un filtre est appliqué, lier le paramètre
if (!empty($status_filter)) {
    $stmt->bind_param("ss", $utilisateur_id, $status_filter); // Paramètres : entier pour utilisateur_id, chaîne pour status
} else {
    $stmt->bind_param("i", $utilisateur_id); // Si aucun filtre, seulement l'utilisateur_id
}

$stmt->execute();
$result = $stmt->get_result();

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historique des Congés</title>
    <style>
/* Style général */
body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background-color: #eef2f7;
    margin: 0;
    padding: 0;
    color: #333;
}

/* En-tête */
header {
    background-color: #ae5eec; /* Couleur plus vive et moderne */
    color: white;
    text-align: center;
    padding: 20px;
    font-size: 26px;
    font-weight: bold;
    letter-spacing: 1px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

/* Conteneur principal */
main {
    padding: 20px;
    max-width: 1200px;
    margin: 0 auto;
}

/* Conteneur du filtre */
.filter-container {
    margin-bottom: 20px;
    text-align: center;
    background-color: #ffffff;
    padding: 15px;
    border-radius: 8px;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
}

.filter-container label {
    font-size: 18px;
    margin-right: 10px;
    color: #555;
    font-weight: 600;
}

.filter-container select {
    font-size: 16px;
    padding: 8px;
    width: 220px;
    border-radius: 5px;
    border: 1px solid #ddd;
    background-color: #f9f9f9;
    color: #333;
    transition: all 0.3s ease;
}

.filter-container select:hover,
.filter-container select:focus {
    background-color:rgb(184, 113, 238);
    background-color: #f3f0fa;
}

/* Tableau */
table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;
    background-color: #ffffff;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

th, td {
    padding: 15px;
    text-align: left;
    font-size: 14px;
    border-bottom: 1px solid #ddd;
}

th {
    background-color: #ae5eec;
    color: white;
    font-weight: bold;
    text-transform: uppercase;
}

tr:nth-child(even) {
    background-color: #f9f9f9;
}

tr:hover {
    background-color: #f1f1f1;
    transition: background-color 0.3s ease;
}

td {
    color: #555;
}

/* Message quand il n'y a pas de données */
.no-data {
    text-align: center;
    font-size: 18px;
    color: #888;
    margin-top: 20px;
}

/* Lien de retour */
a {
    display: inline-block;
    margin-top: 20px;
    padding: 12px 25px;
   background-color: #ae5eec;
    color: white;
    text-decoration: none;
    border-radius: 5px;
    font-weight: 600;
    transition: all 0.3s ease;
    text-align: center;
}

a:hover {
    background-color: #ae5eec;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
}

/* Responsiveness */
@media (max-width: 768px) {
    th, td {
        font-size: 12px;
    }

    .filter-container {
        padding: 10px;
    }

    .filter-container select {
        width: 100%;
    }
}


    </style>
</head>
<body>
    <header>
        Historique des Congés
    </header>
    <main>
        <!-- Conteneur pour le filtre -->
        <div class="filter-container">
            <label for="status">Filtrer par statut :</label>
            <form method="GET" action="historique.php">
                <input type="hidden" name="utilisateur_id" value="<?php echo $utilisateur_id; ?>">
                <select id="status" name="status" onchange="this.form.submit()">
                    <option value="">Tous</option>
                    <option value="Approuvé" <?php echo $status_filter == "approuvé" ? "selected" : ""; ?>>Approuvé</option>
                    <option value="En attente" <?php echo $status_filter == "en attente" ? "selected" : ""; ?>>En attente</option>
                    <option value="Refusé" <?php echo $status_filter == "refusé" ? "selected" : ""; ?>>Refusé</option>
                </select>
            </form>
        </div>
        
        <!-- Tableau des congés -->
        <?php if ($result->num_rows > 0): ?>
            <table>
                <tr>
                    <th>Type de Congé</th>
                    <th>Date Début</th>
                    <th>Date Fin</th>
                    <th>Heures Demandées</th>
                    <th>Jours Demandés</th>
                    <th>Statut</th>
                </tr>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $row["type_conge"]; ?></td>
                        <td><?php echo $row["date_debut"]; ?></td>
                        <td><?php echo $row["date_fin"]; ?></td>
                        <td><?php echo $row["heures_demandes"]; ?></td>
                        <td><?php echo $row["jours_demandes"]; ?></td>
                        <td><?php echo $row["status"]; ?></td>
                    </tr>
                <?php endwhile; ?>
            </table>
        <?php else: ?>
            <p class="no-data">Aucune demande trouvée pour cet utilisateur.</p>
        <?php endif; ?>
        <a href="user.php">Retour à l'accueil</a>
    </main>
</body>
</html>
<?php
$conn->close();
?>
