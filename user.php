<?php 
session_start(); // Assurez-vous que la session est démarrée

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    echo "Veuillez vous connecter.";
    exit();
}

$utilisateur_id = $_SESSION['user_id'];  // Récupérer l'ID utilisateur depuis la session

// Connexion à la base de données
$conn = new mysqli("localhost", "root", "", "gestion_conges");

// Vérifier la connexion
if ($conn->connect_error) {
    die("Échec de la connexion : " . $conn->connect_error);
}

// Année sélectionnée (par défaut, l'année actuelle)
$annee = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');


// Plage d'années autorisées
if ($annee < 2020 || $annee > 2030) {
    $annee = date('Y');
}
// Requête pour récupérer les demandes de congé de l'utilisateur
$sql = "SELECT date_debut, date_fin, status, type_conge 
        FROM demandes_conges 
        WHERE utilisateur_id = ? 
        AND YEAR(date_debut) = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $utilisateur_id, $annee); // Utilisez $annee comme deuxième paramètre

$stmt->execute();
$result = $stmt->get_result();

// Créer un tableau de demandes de congé
$demandes_conges = [];
while ($row = $result->fetch_assoc()) {
    $demandes_conges[] = $row;
}

// Fermer la connexion à la base de données
$stmt->close();


// Tableau des mois et du nombre de jours
$is_leap = (($annee % 4 == 0 && $annee % 100 != 0) || ($annee % 400 == 0));
$mois = [
    "Janvier" => 31,
    "Février" => $is_leap ? 29 : 28, 
    "Mars" => 31,
    "Avril" => 30,
    "Mai" => 31,
    "Juin" => 30,
    "Juillet" => 31,
    "Août" => 31,
    "Septembre" => 30,
    "Octobre" => 31,
    "Novembre" => 30,
    "Décembre" => 31
];

// Requête pour récupérer les notifications non lues
$query_notifications = "SELECT type_conge, status, date_debut, date_fin 
                        FROM demandes_conges 
                        WHERE utilisateur_id = ? 
                        AND (status = 'approuvé' OR status = 'refusé') 
                        AND vue = 0
                        ORDER BY date_creation DESC";

$stmt = $conn->prepare($query_notifications);
$stmt->bind_param("i", $utilisateur_id);
$stmt->execute();
$result_notifications = $stmt->get_result();

// Vérifiez si la requête renvoie bien les données attendues
$notifications = [];
while ($row = $result_notifications->fetch_assoc()) {
    $notifications[] = [
        'message' => "Votre demande de congé ({$row['type_conge']}) du {$row['date_debut']} au {$row['date_fin']} a été {$row['status']}.",
        'status' => $row['status'],
        'date_debut' => $row['date_debut'],  // Assurez-vous que cette colonne est bien récupérée
        'date_fin' => $row['date_fin']       // Assurez-vous que cette colonne est bien récupérée
    ];
}

$stmt->close();

// Marquer les notifications comme lues
$update_sql = "UPDATE demandes_conges SET vue = 1 
               WHERE utilisateur_id = ? AND vue = 0";
$stmt_update = $conn->prepare($update_sql);
$stmt_update->bind_param("i", $utilisateur_id);
$stmt_update->execute();
// Compter le nombre de notifications
$notification_count = count($notifications);

if (isset($_SESSION['success_message'])) {
    echo $_SESSION['success_message'];
    unset($_SESSION['success_message']); // Supprimer le message après l'affichage
}
?>



<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calendrier en Matrice</title>
    <style>
 /* Réinitialisation des marges et paddings */
* {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
}

/* Style global du body */
body {
    font-family: Arial, sans-serif;
    display: flex;
    justify-content: right;
    align-items: flex-start; /* Alignement vers le haut */
    min-height: 100vh;
    background-color: #f4f4f4;
    flex-direction: column; /* Permet de disposer les éléments de haut en bas */
    padding-top: 20px; /* Espacement au sommet */
}

.header-container {
    display: flex;
    justify-content: flex-end; /* Aligne les éléments à droite */
    width: 100%; /* Remplir toute la largeur */
    margin-bottom: 20px; /* Marge sous l'entête */
    padding-right: 20px; /* Ajouter un peu d'espace à droite */
}

/* Bouton "Nouvelle demande" */
.new-request-btn {
    padding: 10px 20px;
    background-color: #4CAF50; /* Vert */
    color: white;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    font-size: 16px;
    margin-right: 20px; /* Espacement entre le bouton et le sélecteur d'année */
}

/* Effet au survol du bouton */
.new-request-btn:hover {
    background-color: #45a049;
}

/* Conteneur du sélecteur d'année */
.year-select-container {
    display: flex;
    align-items: center;
}

/* Label du sélecteur d'année */
.year-label {
    font-size: 16px;
    margin-right: 10px; /* Espacement entre le label et le sélecteur */
}

/* Sélecteur d'année */
.year-select {
    padding: 5px 10px;
    font-size: 16px;
    border-radius: 5px;
    border: 1px solid #ccc;
}

/* Calendrier - Grille */
.calendar-container {
    margin-top: 20px; /* Marge sous l'entête */
    display: flex;
    flex-direction: column; /* Disposition verticale */
    padding-left: 0px;
    width: 100%; /* Prendre toute la largeur disponible */
    max-width: 1000px; /* Limiter la largeur maximale du calendrier */
    padding-left: 30px; /* Contrôler l'espace à gauche */
    padding-right: 30px; /* Contrôler l'espace à droite si nécessaire */
    padding-top: 20px; /* Contrôler l'espace en haut */
}

/* Grille du calendrier (les jours) */
.calendar-matrix {
    display: grid;
    grid-template-columns: repeat(32, 1fr); /* 31 jours + 1 colonne pour les mois */
    gap: 1px;
    border: 1px solid #ddd;
    background-color: #fff;
    width: 80vw; /* Ajustez la largeur */
    max-width: 1000px; /* Largeur maximale */
}

/* Cellules du calendrier */
.cell {
    padding: 5px; /* Taille réduite */
    text-align: center;
    border: 1px solid #ccc;
    font-size: 12px; /* Réduction de la taille de police */
}

/* Cellules d'entête (jours du mois) */
.header {
    font-weight: bold;
    background-color: #ae5eec;
    color: white;
}

/* Cellules vides */
.empty-cell {
    background-color: #fff;
}

/* Style pour les jours (cellules du mois) */
.day {
    min-height: 20px; /* Taille réduite */
    background-color: #e7f7ff;
    border-radius: 2px;
    margin: 1px;
}

/* Style pour les mois */
.month-name {
    font-weight: bold;
    background-color: #f0f0f0;
    text-align: left;
    padding-left: 3px;
}

/* Conteneur de la légende */
.legend-container {
    padding-left: 30px;
    display: flex;
     /* Affichage des éléments de légende l'un sous l'autre */
    gap: 10px; /* Espacement entre chaque élément de légende */
    margin-top: 20px; /* Marge au-dessus de la légende */
}

/* Élément de la légende */
.legend-item {
    display: flex;
    align-items: center;
    font-size: 14px; /* Taille de texte pour la légende */
}

/* Carré coloré pour la légende */
.legend-indicator {
    width: 20px;
    height: 20px;
    border-radius: 3px; /* Coins légèrement arrondis */
    margin-right: 10px; /* Espacement entre le carré et le texte */
}

/* Définir les couleurs de fond pour chaque statut */
.bg-yellow {
    background-color:rgb(247, 247, 37); /* Jaune */
}

.bg-teal {
    background-color:rgb(8, 136, 44); /* Teal */
}

.bg-cyan {
    background-color:rgb(255, 40, 40); /* Cyan */
}

.bg-red {
    background-color:rgb(11, 8, 184); /* Rouge */
}

.bg-yellow-light {
    background-color:rgb(117, 116, 110); /* Jaune clair */
}

/* Couleur du texte pour la légende */
.text-muted {
    color: #6b7280; /* Gris clair */
}

/*container*/

.container {
            display: flex;
            justify-content: center;
            align-items: flex-start; /* Alignement en haut de la page */
            padding: 20px;
            margin-top: 20px; /* Espacement optionnel pour éloigner du bord du haut */
        }

    .info {
    display: flex;
    flex-direction: column;
    background-color: #d4b5e9; /* Fond bleu similaire au calendrier */
    color: white; /* Texte blanc pour contraste */
    padding: 20px;
    border-radius: 8px; /* Coins légèrement arrondis si souhaité */
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    width: 1500px; /* Largeur ajustée pour la section d'information */
    border: none; /* Supprimer la bordure */
}

        /* Alignement horizontal des informations */
        .header1 {
            display: flex;
            justify-content: space-between; /* Distribuer les éléments à gauche et à droite */
            align-items: center;
            margin-bottom: 20px;
        }

        .user-info, .poste-dept-info {
            display: flex;
            align-items: center;
        }

        /* Style de l'icône */
        .icon {
            width: 40px;
            height: 40px;
            margin-right: 10px;
        }

        .text {
            display: flex;
            flex-direction: column;
        }

        .text p {
            margin: 0;
            font-size: 18px;
            color: #333;
            font-weight: bold;
        }

        /* Aligner poste et département horizontalement */
        .poste-dept-info {
            display: flex;
            justify-content: flex-start; /* Aligner à gauche */
            font-size: 16px;
            color: #333;
            padding-right: 100px;
        }

        .poste-dept-info p {
            font-size: 20px;
            margin-right: 40px; /* Ajouter un espace entre le poste et le département */
        }

        .poste-dept-info p:last-child {
            margin-right: 50; /* Supprimer l'espace après le dernier élément */
        }

        .name-prenom-info {
            display: flex;
            justify-content: flex-start;
            align-items: center;
            margin-bottom: 10px; /* Espacement entre nom et prénom */
        }

        .poste-dept-info p {
            font-size: 16px;
        }
        /*a cote de notification*/
        /* Conteneur de la barre supérieure */
        .top-bar {
    display: flex;
    justify-content: flex-end; /* Aligne tous les éléments à droite */
    align-items: center; /* Centrer verticalement les éléments */
    gap: 15px; /* Espacement entre les éléments */
    padding: 10px 20px; /* Ajout de marges internes */
    width: 100%; /* Assurez-vous que la barre prend toute la largeur de l'écran */
}

.top-bar-btn {
    background-color: #4CAF50;
    color: white;
    padding: 10px 20px;
    border: none;
    cursor: pointer;
    font-size: 16px;
    border-radius: 5px;
}

.notification-container {
    position: relative;
    display: inline-block;
    cursor: pointer;
}

.notification-badge {
    position: absolute;
    top: -5px;
    right: -5px;
    padding: 5px 10px;
    border-radius: 50%;
    background-color: red;
    color: white;
    font-size: 12px;
}

.notification-modal {
    display: none;
    position: fixed;
    z-index: 1;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.4);
    overflow: auto;
    padding-top: 60px;
}

.modal-content {
    background-color: #fefefe;
    margin: 5% auto;
    padding: 20px;
    border: 1px solid #888;
    width: 80%;
    max-width: 400px;
    border-radius: 10px;
}

.close {
    color: #aaa;
    font-size: 28px;
    font-weight: bold;
    position: absolute;
    top: 10px;
    right: 25px;
}

.close:hover,
.close:focus {
    color: black;
    text-decoration: none;
    cursor: pointer;
}

.icon {
    width: 30px;
    height: 30px;
}

.small-icon {
    width: 30px;
    height: 30px;
}
.notification-container {
        position: relative;
        display: inline-block;
        cursor: pointer;
    }

    .notification-badge {
        position: absolute;
        top: -5px;
        right: -5px;
        padding: 5px 10px;
        border-radius: 50%;
        background-color: red;
        color: white;
        font-size: 12px;
    }

    /* Style pour la fenêtre modale */
    .notification-modal {
        display: none;
        position: fixed;
        z-index: 1;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0,0,0,0.4); /* Fond semi-transparent */
        overflow: auto;
        padding-top: 60px;
    }

    /* Contenu de la fenêtre modale */
    .modal-content {
        background-color: #fefefe;
        margin: 5% auto;
        padding: 20px;
        border: 1px solid #888;
        width: 80%;
        max-width: 400px;
        border-radius: 10px;
    }

    /* Style pour fermer la fenêtre modale */
    .close {
        color: #aaa;
        font-size: 28px;
        font-weight: bold;
        position: absolute;
        top: 10px;
        right: 25px;
    }

    .close:hover,
    .close:focus {
        color: black;
        text-decoration: none;
        cursor: pointer;
    }


    .notification-badge {
        position: absolute;
        top: -5px;
        right: -5px;
        background: red;
        color: white;
        border-radius: 50%;
        padding: 2px 6px;
        font-size: 12px;
    }

    .notification-dropdown {
        display: none;
        position: absolute;
        top: 60px; /* Ajustez en fonction de votre icône */
        right: 0;
        background-color: white;
        border: 1px solid #ccc;
        border-radius: 5px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
        z-index: 1000;
        width: 250px;
    }

    .notification-dropdown p {
        padding: 10px;
        margin: 0;
        border-bottom: 1px solid #eee;
    }

    .notification-dropdown p:last-child {
        border-bottom: none;
    }



/* Couleur personnalisée pour 'Restant' */
/* Couleur noir pour 'Restant' */
.custom-black {
    background-color: #000000; /* Noir */
}

/* Couleur bleu pour 'Total' */
.custom-blue {
    background-color: #2196F3; /* Bleu */
}


/* Jours fériés */
.cell.holiday {
    background-color:rgb(169, 160, 160); /* Gris clair */
    color: #555; /* Texte en gris foncé */
}

.cell.en_attente {
    background-color: yellow;
}
.cell.approuvé {
    background-color: teal;
}
.cell.restant {
    background-color: cyan;
}
.cell.total {
    background-color: red;
}

/* Style des cases approuvées */
.approved {
    background-color: green;
    color: white;
}

/* Style des cases refusées */
.refused {
    background-color: red;
    color: white;
}

/* Style des cases en attente */
.pending {
    background-color: yellow;
    color: black;
}


/* Approuvé */
.approuvé {
    background-color: green;
    color: white;
}

/* En attente */
.en-attente {
    background-color: orange;
    color: white;
}

/* Rejeté */
.rejeté {
    background-color: red;
    color: white;
}

.en attente {
    background-color: yellow;
    color: black;
}
/* Styles pour les différentes statuts */
.cell.approuvé {
    background-color: green;
    color: white;
}

.cell.en-attente {
    background-color: yellow;
    color: black;
}

.cell.rejeté {
    background-color: red;
    color: white;
}


/* Conteneur global du calendrier et de la légende */
.calendar-and-legend {
    display: flex;
    flex-direction: row;
    align-items: flex-start;
    margin-top: 20px;
    gap: 20px;
    justify-content: center; /* Centrer horizontalement */
    width: 100%; /* Assurez-vous que la largeur du conteneur est de 100% */
    max-width: 1200px; /* Optionnel: Limitez la largeur pour éviter que le calendrier ne soit trop large */
    margin-left: auto;  /* Pour centrer avec "auto" */
    margin-right: auto; /* Pour centrer avec "auto" */
}

/* Conteneur principal du calendrier */
.calendar-container {
    flex: 3;
    width: 100%; /* Assurez-vous que la largeur du calendrier est de 100% */
    max-width: 900px; /* Limitez la largeur du calendrier */
    margin: 0 auto; /* Centrer le calendrier horizontalement */
}

/* Matrice de calendrier */
.calendar-matrix {
    display: flex;
    flex-direction: column;
    width: 100%;
    border-collapse: collapse;
}

/* Ligne d'entête avec les mois (première ligne) */
.calendar-header {
    display: flex;
    justify-content: space-between;
    background-color: #f0f0f0;
    padding: 10px 0;
    border-bottom: 2px solid #ccc;
}

/* Conteneur de la légende */
.custom-legend-container {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 10px;
    padding: 8px;
    background-color: #f9f9f9;
    border-radius: 5px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

/* Ajustement pour chaque élément de la légende */
.custom-legend-item {
    display: flex;
    align-items: center;
    gap: 5px;
    font-size: 12px;
}

/* Style pour les indicateurs de la légende */
.custom-legend-indicator {
    width: 15px;
    height: 15px;
    border-radius: 3px;
}

/* Texte de la légende */
.custom-text-muted {
    font-size: 12px;
    color: #6c757d;
}

.custom-legend-count {
    font-size: 12px;
    font-weight: 600;
    color: #333;
}


/* Conteneur de la légende */
.custom-legend-container {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 10px;
    padding: 8px;
    background-color: #f9f9f9;
    border-radius: 5px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

/* Ajustement pour chaque élément de la légende */
.custom-legend-item {
    display: flex;
    align-items: center;
    gap: 5px;
    font-size: 12px;
}

/* Style pour les indicateurs de la légende */
.custom-legend-indicator {
    width: 15px;
    height: 15px;
    border-radius: 3px;
}

/* Texte de la légende */
.custom-text-muted {
    font-size: 12px;
    color: #6c757d;
}

.custom-legend-count {
    font-size: 12px;
    font-weight: 600;
    color: #333;
}




/* Cellule contenant le nom du mois et les jours */
.cell.month-name {
    width: 150px; /* Largeur pour le mois */
    text-align: center;
    font-weight: bold;
    padding: 5px;
    background-color: #f0f0f0;
}

/* Cellule contenant les jours */
.cell.days {
    display: flex;
    flex-wrap: wrap;
    gap: 5px; /* Espace entre les jours */
}

/* Cellule contenant le jour */
.cell.day {
    width: 30px;  /* Taille plus petite pour les cases de jour */
    height: 30px; /* Taille plus petite pour les cases de jour */
    text-align: center;
    vertical-align: middle;
    display: flex;
    justify-content: center;
    align-items: center;
    border: 1px solid #ccc;
    box-sizing: border-box;
    font-size: 12px; /* Taille de police réduite pour les jours */
}

/* Changement de couleur de fond en fonction du type de congé */
.day[style*="background-color: green;"] {
    background-color: green;
    color: white;
}

.day[style*="background-color: red;"] {
    background-color: red;
    color: white;
}

.day[style*="background-color: yellow;"] {
    background-color: yellow;
    color: black;
}

/* Ajout d'un survol léger pour les jours */
.cell.day:hover {
    background-color: #f5f5f5;
}


.legend-container {
    display: flex;
    justify-content: flex-start; /* Aligner les éléments à gauche */
    align-items: center; /* Centrer verticalement */
    flex-direction: row; /* Placer les éléments sur la même ligne */
    gap: 20px; /* Espacement entre les éléments */
    margin-top: 20px; /* Espacement au-dessus */
    padding-left: 320px; /* Ajouter de l'espace à gauche dans le conteneur */
}

.legend-item {
    display: flex;
    align-items: center;
    gap: 8px; /* Espacement entre l'indicateur et le texte */
    font-size: 14px;
}

.legend-indicator {
    width: 20px;
    height: 20px;
    border-radius: 50%;
}

.text-muted {
    color: #6c757d;
}

/* Centrer #rapportContent au centre de la page */
#rapportContent {
    display: none; /* Par défaut, masqué */
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%); /* Centrer parfaitement */
    padding: 20px;
    background-color: rgba(0, 0, 0, 0.7);
    color: white;
    border-radius: 8px;
    z-index: 1000;
}


    </style>
</head>
<body>
    <div class="top-bar">
        <!-- Bouton "Historique" -->
        <button class="top-bar-btn" onclick="window.location.href='PageAcceuil.html'">Déconnexion</button>
    
        <!-- Bouton "Dashboard" -->

    
        <!-- Bouton "Historique" -->
<!-- Bouton Historique -->
<!-- Bouton pour charger l'historique sur une autre page -->

            
<div class="notification-container" onclick="toggleNotification()">
    <img src="https://img.icons8.com/ios/50/000000/appointment-reminders--v1.png" alt="Icône notification" class="icon notification-icon">
    <span class="notification-badge" id="notificationBadge">
        <?php echo $notification_count > 0 ? $notification_count : ''; ?>
    </span>
</div>

<div id="notificationDropdown" class="notification-dropdown" style="display:none;">
    <div id="notificationContent">
        <?php if ($notification_count > 0): ?>
            <?php foreach ($notifications as $notif): ?>
                <div class="notification <?= strtolower($notif['status']) ?>">
                    <p><strong>Statut :</strong> <?= htmlspecialchars($notif['status']) ?> | 
                       <strong>Date de début :</strong> <?= htmlspecialchars($notif['date_debut']) ?> | 
                       <strong>Date de fin :</strong> <?= htmlspecialchars($notif['date_fin']) ?></p>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>Aucune nouvelle notification.</p>
        <?php endif; ?>
    </div>
</div>

        
    
        <!-- Icône similaire à côté du nom et prénom -->
        <img src="https://img.icons8.com/ios/50/000000/user-male.png" alt="Icône utilisateur" class="icon small-icon">
    </div>
    
    
    

  

    <div class="container">
        <div class="info">
            <div class="header1">
                <!-- Informations de l'utilisateur à gauche -->
                <div class="user-info">
                    <img src="https://img.icons8.com/ios/50/000000/user-male.png" alt="Icône utilisateur" class="icon">
                    <div class="text">
                    
                    <p>Bienvenue, 
                        <?php 
                            // Vérification si les variables de session sont définies
                            echo isset($_SESSION['nom']) ? $_SESSION['nom'] : '';
                            echo " "; 
                            echo isset($_SESSION['prenom']) ? $_SESSION['prenom'] : '';
                        ?>
                    </p>
                </div>
            </div>

            <!-- Poste et Département à droite -->
            <div class="poste-dept-info">
                <p><strong>Poste:</strong> 
                    <?php 
                        // Vérification si la variable session 'poste' est définie
                        echo isset($_SESSION['poste']) ? $_SESSION['poste'] : 'Poste non défini';
                    ?>
                </p>
                <p><strong>Département:</strong> 
                    <?php 
                        // Vérification si la variable session 'departement' est définie
                        echo isset($_SESSION['departement']) ? $_SESSION['departement'] : 'Département non défini';
                    ?>
                </p>
            </div>
        </div>
    </div>
</div>
    
<div class="header-container">
    <button class="new-request-btn" id="openModalBtn">Nouvelle demande</button>

    <a href="historique.php?utilisateur_id=<?php echo $_SESSION['user_id']; ?>">
    <button class="top-bar-btn">Historique</button>
</a>

</div>


<!-- Modal container for dynamic loading -->
<div id="modalContainer"></div>


<div class="custom-legend-container">
    <div class="custom-legend-item">
        <div class="custom-legend-indicator bg-yellow"></div>
        <span class="custom-text-muted">En attente</span>
        <span class="custom-legend-count" id="pendingDays"><?= $pendingDays; ?> jours</span>
    </div>
    <div class="custom-legend-item">
        <div class="custom-legend-indicator bg-teal"></div>
        <span class="custom-text-muted">Approuvé</span>
        <span class="custom-legend-count" id="approvedDays"><?= $approvedDays; ?> jours</span>
    </div>
    <div class="custom-legend-item">
        <div class="custom-legend-indicator"></div> <!-- Noir pour Restant -->
        <span class="custom-text-muted">Nombre de congés restants:</span>
        <span class="custom-legend-count" id="remainingDays"><?= $remainingDays; ?> jours</span>
    </div>
    <div class="custom-legend-item">
        <div class="custom-legend-indicator"></div> <!-- Bleu pour Total -->
        <span class="custom-text-muted">Max à ne pas depasser:</span>
        <span class="custom-legend-count" id="sickDays">60 jours</span>
    </div>
</div>
<div class="calendar-and-legend">
    <div class="calendar-container">
        <div class="calendar-matrix">
            <div class="calendar-header">
                <div class="cell month-name">Mois</div>
                <div class="cell days-header">Jours</div>
            </div>

            <!-- Boucle pour chaque mois -->
            <?php foreach ($mois as $nom_mois => $nombre_jours) { ?>
                <div class="calendar-row">
                    <div class="cell month-name"><?php echo $nom_mois; ?></div>
                    <div class="cell days">
                        <?php 
                        // Boucle sur les jours du mois
                        for ($i = 1; $i <= $nombre_jours; $i++) { 
                            $congeColor = "";  // Par défaut, pas de couleur

                            // Vérification des congés pour l'année sélectionnée
                            foreach ($demandes_conges as $conge) {
                                // Calcul des dates de début et de fin du congé
                                $start_date = strtotime($conge['date_debut']);
                                $end_date = strtotime($conge['date_fin']);

                                // Extraire l'année, le mois et le jour de la date actuelle
                                $current_date = strtotime("$annee-" . (array_search($nom_mois, array_keys($mois)) + 1) . "-" . str_pad($i, 2, '0', STR_PAD_LEFT));

                                // Vérifier si le congé est dans l'année sélectionnée
                                $start_year = date('Y', $start_date);
                                $end_year = date('Y', $end_date);

                                // Si le congé est dans l'année sélectionnée, on procède à la vérification du jour
                                if (($start_year == $annee || $end_year == $annee) && ($current_date >= $start_date && $current_date <= $end_date)) {
                                    if ($conge['status'] == 'approuvé') {
                                        if ($conge['type_conge'] == 'Maladie') {
                                            $congeColor = "background-color: blue;";  // Couleur pour congé de type Maladie
                                        } else {
                                            $congeColor = "background-color: green;";  // Couleur pour autres congés approuvés
                                        }
                                    } elseif ($conge['status'] == 'refusé') {
                                        $congeColor = "background-color: red;";  // Exemple de couleur pour congé refusé
                                    } elseif ($conge['status'] == 'en attente') {
                                        $congeColor = "background-color: yellow;";  // Exemple de couleur pour congé en attente
                                    }
                                    
                                    break;
                                }
                            }
                            ?>
                            <div class="cell day" data-month="<?php echo $nom_mois; ?>" data-day="<?php echo $i; ?>" style="<?php echo $congeColor; ?>">
                                <?php echo str_pad($i, 2, '0', STR_PAD_LEFT); ?>
                            </div>
                        <?php } ?>
                    </div>
                </div>
            <?php } ?>
        </div>
    </div>
</div>

            
        </div>
    



    
            
            
        </div>
        <?php
// Connexion à la base de données
try {
    $pdo = new PDO("mysql:host=localhost;dbname=gestion_conges", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Échec de la connexion : " . $e->getMessage());
}

// Requête pour calculer les jours approuvés et en attente pour un utilisateur spécifique
$sql = "SELECT status, date_debut, date_fin
        FROM demandes_conges
        WHERE utilisateur_id = :utilisateur_id AND status IN ('approuvé', 'en attente')";
$stmt = $pdo->prepare($sql);
$stmt->execute(['utilisateur_id' => $utilisateur_id]);

// Initialisation des variables
$pendingDays = 0;   // Jours "en attente"
$approvedDays = 0;  // Jours "approuvés"

// Parcourir les résultats pour affecter les valeurs selon le statut
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    // Calcul de la différence de jours entre la date de début et de fin
    $jours = (int) (strtotime($row['date_fin']) - strtotime($row['date_debut'])) / (60 * 60 * 24) + 1;  // Calcul des jours

    // Si le statut est "en attente", on ajoute aux jours en attente
    if ($row['status'] === 'en attente') {
        $pendingDays += $jours;
    } 
    // Si le statut est "approuvé", on ajoute aux jours approuvés
    elseif ($row['status'] === 'approuvé') {
        $approvedDays += $jours;
    }
}



// Nombre total de jours autorisés
$totalDays = 60;

// Calcul des jours restants (on déduit uniquement les jours approuvés)
$remainingDays = $totalDays - $approvedDays;

// Validation pour éviter les nombres négatifs
if ($remainingDays < 0) {
    $remainingDays = 0;
}


// Affichage des valeurs en JavaScript pour les intégrer dans la page HTML
echo "<script>
    // Ici on s'assure que la variable est bien définie dans le contexte global de JavaScript
    let totalDays = $totalDays;
    let pendingDays = $pendingDays;
    let approvedDays = $approvedDays;
    let remainingDays = $remainingDays;

    document.getElementById('pendingDays').innerText = `${pendingDays} jours`;
    document.getElementById('approvedDays').innerText = `${approvedDays} jours`;
    document.getElementById('remainingDays').innerText = `${remainingDays} jours`;
</script>
";


?>
    </div>




    <!-- Légende placée sous le calendrier -->
    <!-- Légende placée sous le calendrier -->
    <div class="legend-container">
        <div class="legend-item">
            <div class="legend-indicator bg-yellow"></div>
            <span class="text-muted">En attente</span>
        </div>
        <div class="legend-item">
            <div class="legend-indicator bg-teal"></div>
            <span class="text-muted">Congé approuvé</span>
        </div>
        <div class="legend-item">
            <div class="legend-indicator bg-cyan"></div>
            <span class="text-muted">refusé</span>
        </div>
        <div class="legend-item">
            <div class="legend-indicator bg-red"></div>
            <span class="text-muted">Maladie approuvé</span>
        </div>
        <div class="legend-item">
            <div class="legend-indicator bg-yellow-light"></div>
            <span class="text-muted">Jour férié</span>
        </div>
        <!-- conges maladie /conges nrml -->
<div id="rapportContent" style="display: none; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%);">

</div>
<div id="rapportContent2" style="display: none; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); ">

</div>

    </div>
    <script>

        // fonction de conges nrml
        function loadRapport() {
    // Récupérer l'utilisateur ID à partir de la session PHP ou une variable JavaScript
    const utilisateurId = <?php echo $_SESSION['user_id']; ?>; // Assurez-vous que cela est défini

    // Construire l'URL avec l'utilisateur_id
    const url = `rapportconges.php?utilisateur_id=${utilisateurId}`;

    // Requête pour charger la page rapportmaladie.php avec l'ID utilisateur dans l'URL
    fetch(url)
        .then(response => response.text())
        .then(data => {
            const contentDiv = document.getElementById('rapportContent');
            contentDiv.innerHTML = data;
            contentDiv.style.display = 'block';

            // Exécutez les scripts après l'injection
            const scripts = contentDiv.querySelectorAll('script');
            scripts.forEach(script => {
                const newScript = document.createElement('script');
                newScript.textContent = script.textContent;
                document.body.appendChild(newScript);
            });
        })
        .catch(error => console.error('Erreur lors du chargement:', error));
}

function loadRapport2() {
    // Récupérer l'utilisateur ID à partir de la session PHP ou une variable JavaScript
    const utilisateurId = <?php echo $_SESSION['user_id']; ?>; // Assurez-vous que cela est défini

    // Construire l'URL avec l'utilisateur_id
    const url = `rapportmaladie.php?utilisateur_id=${utilisateurId}`;

    // Requête pour charger la page rapportmaladie.php avec l'ID utilisateur dans l'URL
    fetch(url)
        .then(response => response.text())
        .then(data => {
            const contentDiv = document.getElementById('rapportContent2');
            contentDiv.innerHTML = data;
            contentDiv.style.display = 'block';

            // Exécutez les scripts après l'injection
            const scripts = contentDiv.querySelectorAll('script');
            scripts.forEach(script => {
                const newScript = document.createElement('script');
                newScript.textContent = script.textContent;
                document.body.appendChild(newScript);
            });
        })
        .catch(error => console.error('Erreur lors du chargement:', error));
}



   // Fonction pour afficher la notification dans une fenêtre modale
   function showNotification() {
        const notificationModal = document.getElementById('notificationModal');
        notificationModal.style.display = 'block'; // Affiche la fenêtre modale
    }

    // Fonction pour fermer la fenêtre modale
    function closeNotification() {
        const notificationModal = document.getElementById('notificationModal');
        notificationModal.style.display = 'none'; // Cache la fenêtre modale
    }


    
    function fetchNotifications() {
        fetch('get_notifications.php')
            .then(response => response.json())
            .then(data => {
                const notificationBadge = document.getElementById('notificationBadge');
                const notificationContent = document.getElementById('notificationContent');

                // Réinitialiser le contenu
                notificationContent.innerHTML = '';

                if (data.length > 0) {
                    // Afficher le nombre de notifications non lues
                    notificationBadge.textContent = data.length;

                    // Ajouter les notifications au menu déroulant
                    data.forEach(notification => {
                        const notifDiv = document.createElement('div');
                        notifDiv.className = 'notification-item';
                        notifDiv.innerHTML = `
                            <p><strong>Statut :</strong> ${notification.status}</p>
                            <p><strong>Début :</strong> ${notification.date_debut}</p>
                            <p><strong>Fin :</strong> ${notification.date_fin}</p>
                        `;
                        notificationContent.appendChild(notifDiv);
                    });
                } else {
                    // Aucune notification
                    notificationBadge.textContent = '';
                    notificationContent.innerHTML = '<p>Aucune nouvelle notification.</p>';
                }
            })
            .catch(error => console.error('Erreur lors de la récupération des notifications:', error));
    }

    function toggleNotification() {
        const dropdown = document.getElementById('notificationDropdown');
        dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';

        // Charger les notifications lorsque le menu s'ouvre
        if (dropdown.style.display === 'block') {
            fetchNotifications();
        }
    }




// Event listener to open modal
document.getElementById("openModalBtn").addEventListener("click", () => {
    fetch('modal.html').then(response => response.text()).then(data => {
        document.getElementById("modalContainer").innerHTML = data;
        const modal = document.getElementById("modal");
        modal.style.display = "flex";
        modal.querySelector(".close").addEventListener("click", () => modal.style.display = "none");
        window.addEventListener("click", (event) => event.target === modal && (modal.style.display = "none"));
    });
});
function openModalFromCalendar() {
        const modalBtn = document.getElementById('openModalBtn');
        modalBtn.click(); // Simulate button click
    }


    // Add event listeners to all the calendar days
    document.querySelectorAll('.cell.day').forEach(function(dayCell) {
        dayCell.addEventListener('click', openModalFromCalendar);
    });
 
 // Récupérer l'ID utilisateur depuis PHP et l'injecter dans JavaScript
 var utilisateur_id = <?php echo json_encode($utilisateur_id); ?>;

 function loadHistorique() {
            var xhr = new XMLHttpRequest();
            var utilisateur_id = <?php echo $_SESSION['user_id']; ?>; // ID utilisateur depuis la session PHP

            xhr.open("GET", "historique.php?utilisateur_id=" + utilisateur_id, true);
            
            xhr.onreadystatechange = function () {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    document.getElementById("historique-container").innerHTML = xhr.responseText;
                }
            };

            xhr.send();
        }

// Jours fériés pour l'année 2025
const holidaysByYear = {
    2025: {
        Janvier: [10],
        Avril: [12],
        Mai: [1],
        Décembre: [25]
    }
};

// Fonction pour mettre à jour les jours fériés de l'année 2025
function updateHolidays() {
    const year = 2025; // On fixe l'année à 2025 pour ne montrer que ses jours fériés
    console.log(`Mise à jour des jours fériés pour l'année : ${year}`);

    // Réinitialiser tous les styles et réactiver les clics
    document.querySelectorAll('.calendar-matrix .cell.day').forEach(cell => {
        cell.classList.remove('holiday');
        cell.style.pointerEvents = ''; // Réactive le clic
    });

    // Ajouter les styles pour les jours fériés de 2025
    const holidays = holidaysByYear[year];
    if (!holidays) {
        console.log(`Aucun jour férié trouvé pour l'année ${year}`);
        return;
    }

    // Parcourir chaque mois et jour férié
    Object.keys(holidays).forEach(month => {
        const days = holidays[month];
        console.log(`Jours fériés pour ${month} : ${days}`);

        // Sélectionner les bonnes cellules correspondant à chaque mois et jour férié
        document.querySelectorAll(`.calendar-matrix .cell[data-month="${month}"]`).forEach(cell => {
            const day = parseInt(cell.getAttribute('data-day'), 10); // Récupérer le jour de la cellule
            console.log(`Vérification du jour ${day} pour ${month}`);

            // Vérifier si le jour est un jour férié
            if (days.includes(day)) {
                console.log(`Jour férié trouvé pour ${month} ${day}`);
                cell.classList.add('holiday');
                cell.style.pointerEvents = 'none'; // Désactive le clic
            }
        });
    });
}

// Appeler la fonction pour afficher uniquement les jours fériés de 2025
document.addEventListener('DOMContentLoaded', function () {
    updateHolidays();  // Appel à la fonction avec l'année 2025 par défaut
});

// Initialisation du calendrier avec l'année actuelle
updateCalendar(document.getElementById('year-select').value);


</script>

    
</body>
</html>
