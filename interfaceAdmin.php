<?php
session_start();
if (!isset($_SESSION['admin_nom'])) {
    // Redirect to login page if not logged in
    header("Location: loginadmin.html");
    exit();
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Congés</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        body {
            background-color: #e0e0e0;
            display: flex;
        }
        .sidebar {
            height: 100vh;
            background-color: #860c9c;
            padding: 20px;
            position: fixed;
            top: 0;
            bottom: 0;
            left: 0;
            width: 250px;
        }
        .sidebar a {
            color: #fff;
            text-decoration: none;
            display: block;
            margin: 20px 0;
            padding: 10px;
            border-radius: 5px;
        }
        .sidebar a:hover {
            background-color: #650d7f;
        }
        .content {
            margin-left: 270px;
            padding: 30px;
            width: calc(100% - 270px);
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .user-profile {
            display: flex;
            align-items: center;
        }
        .user-profile img {
            margin-left: 10px;
            border-radius: 50%;
        }
        .notification-icon {
            position: relative;
        }
        .notification-badge {
            position:absolute;
            top: -5px;
            right: -5px;
            background-color: #dd1529;
            color: #ffffff;
            border-radius: 50%;
            padding: 3px 7px;
            font-size: 12px;
        }
        .notifications-window {
            display:none;
            position:absolute;
            top: 50px;
            right: 0; /* Adjusted to position the notification box to the right */
            width: 300px;
            background-color: #ffffff;
            border: 1px solid #ddd;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            padding: 10px;
            z-index: 1000;
        }
        .notification-item {
            padding: 10px;
            border-bottom: 1px solid #ddd;
        }
        .notification-item:hover {
            background-color: #f0f0f0;
        }
        .filters {
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
        }
        .filters select,
        .filters input {
            width: 32%;
        }
        .status-indicator {
            padding: 5px 10px;
            border-radius: 5px;
            color: white;
            text-align: center;
        }
        .status-pending {
            background-color: #898781;
        }
        .status-approved {
            background-color: #8e30aa;
        }
        .status-rejected {
            background-color: #a31823;
        }
        /* Styles des boutons Approuver et Rejeter */
        .btn-approve {
            background-color: #7828a7;
            border-color: #28a745;
            color: #fff;
        }
        .btn-approve:hover {
            background-color: #218838;
            border-color: #1e7e34;
        }
        .btn-reject {
            background-color: #dc3545;
            border-color: #dc3545;
            color: #fff;
        }
        .btn-reject:hover {
            background-color: #c82333;
            border-color: #bd2130;
        }
        .dashboard {
            display: none;
            margin-top: 20px;
        }
        .dashboard canvas {
            max-width: 100%;
            height: 300px; /* Adjusted height for better fit */
        }
        .highlight {
            border: 2px solid blue; /* Highlight border color */
        }
        .chart-container {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-around;
        }
        .chart-box {
            flex: 1 1 45%;
            margin: 10px;
            padding: 20px;
            background-color: #fff;
            border: 1px solid #ddd;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        .assign-team {
            display: none;
            margin-top: 20px;
        }
        .modal {
    display: none; /* Hidden by default */
    position: fixed;
    top: 20%; /* Distance depuis le haut */
    left: 50%;
    transform: translate(-50%, 0); /* Ne pas centrer verticalement */
    background-color: white;
    padding: 20px; /* Espacement interne équilibré */
    border: 1px solid #ccc;
    border-radius: 8px; /* Coins arrondis */
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2); /* Effet de flottement */
    z-index: 10000; /* Priorité d'affichage */
    width: 40%; /* Largeur modale */
    max-width: 500px; /* Largeur maximale */
    min-width: 300px; /* Largeur minimale */
    text-align: center;
    margin-bottom: 20px; /* Optionnel : espacement en bas */
}

.modal.active {
    display: block; /* Afficher la modale quand active */
}
    </style>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="sidebar">
        <h4 class="text-white">Gestion des Congés</h4>
        <a href="#manage-leave" onclick="toggleManageLeave()">Gérer les Congés</a>
        <a href="#absence-calendar" onclick="loadCalendarContent()">Calendrier des Absences</a>
        <a href="#dashboard" onclick="toggleDashboard()">Analyse des Absences</a>
        <a href="#assign-team" onclick="toggleAssignTeam()">Ajouter un employé</a>
        <a href="#leave-summary" onclick="toggleLeaveSummary()">Résumé des Congés</a>
        <a href="#" onclick="logout()">Déconnexion</a>

    </div>
    <div class="content">
        <div class="header">
            <div class="notification-icon">
                <img src="notif.png" alt="Notifications" width="50" onclick="toggleNotifications()">
                <span class="notification-badge" id="notification-count">0</span>
                <div class="notifications-window" id="notifications-window">
                    <!-- Les notifications seront ajoutées ici dynamiquement -->
                </div>
            </div>
            <div class="user-profile">
            <span>Responsable: <?php echo htmlspecialchars($_SESSION['admin_nom']); ?></span>
                <img src="avatar.png" alt="Manager" width="60">
            </div>
        </div>
        <div id="manage-leave" class="content-section">
            <div class="filters">
                <input type="text" id="search" class="form-control mb-2" placeholder="Rechercher par nom ou date">
                
                <select id="team-filter" class="form-control mb-2">
                    <option value="all">Sélectionner une équipe</option>
                    <option value="RH">RH</option>
                    <option value="Développement">Développement</option>
                    <option value="Marketing">Marketing</option>
                </select>
                
                <select id="department-filter" class="form-control mb-2">
                    <option value="all">Sélectionner un département</option>
                    <option value="Informatique (IT)">Informatique (IT)</option>
                    <option value="Développement Logiciel">Développement Logiciel</option>
                    <option value="Infrastructure et Réseaux">Infrastructure et Réseaux</option>
                    <option value="Cybersécurité">Cybersécurité</option>
                    <option value="Support Technique">Support Technique</option>
                    <option value="Gestion de Projet Informatique">Gestion de Projet Informatique</option>
                    <option value="Data Science et Analyse de Données">Data Science et Analyse de Données</option>
                    <option value="Gestion de Base de Données">Gestion de Base de Données</option>
                    <option value="Business Intelligence (BI)">Business Intelligence (BI)</option>
                    <option value="Cloud Computing">Cloud Computing</option>
                    <option value="Innovation Technologique">Innovation Technologique</option>
                </select>
                
                
                <input type="date" id="date-filter" class="form-control mb-2">
                
                <select id="status-filter" class="form-control mb-2">
                    <option value="all">Tous les statuts</option>
                    <option value="en attente">En attente</option>
                    <option value="approuvé">Approuvé</option>
                    <option value="refusé">Refusé</option>
                </select>
                
                <button class="btn btn-primary" onclick="filterRequests()">Filtrer</button>
            </div>
            
            
            <h2>Demandes de Congés</h2>
            <table class="table table-striped mt-4">
                <thead class="thead-dark">
                    <tr>
                        <th>Nom de l'Employé</th>
                        <th>Type de Congé</th>
                        <th>Date de Début</th>
                        <th>Date de Fin</th>
                        <th>Heure Demandée</th>
                        <th>Jours Ouvrables</th>
                        <th>Heure Totale</th>
                        <th>Département</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="leave-requests"></tbody>
            </table>
        </div>
        <div id="dashboard" class="content-section">
            <h2>Analyse des Absences</h2>
            <div class="chart-container">
                <iframe title="dashbordminiprojet" width="100%" height="1400" src="https://app.powerbi.com/view?r=eyJrIjoiZmI4ODdmZGYtYmMyMS00ZThjLTk0MDAtNjY0YjEzOGQ5ODBlIiwidCI6ImRiZDY2NjRkLTRlYjktNDZlYi05OWQ4LTVjNDNiYTE1M2M2MSIsImMiOjl9" frameborder="0" allowFullScreen="true"></iframe>
            </div>
        </div>
        <div id="assign-team" class="content-section">
            <h2>Ajouter un employé à une équipe</h2>
            <div class="form-group">
                <label for="user-select">Sélectionner un utilisateur</label>
                <select id="user-select" class="form-control">
                    <!-- Les utilisateurs seront ajoutés ici dynamiquement -->
                </select>
            </div>
            <div class="form-group">
                <label for="team-select">Sélectionner une équipe</label>
                <select id="team-select" class="form-control">
                    <option value="RH">RH</option>
                    <option value="Développement">Développement</option>
                    <option value="Marketing">Marketing</option>
                    <!-- Ajouter d'autres équipes si nécessaire -->
                </select>
            </div>
            <button class="btn btn-primary" onclick="assignTeam()">Affecter à l'équipe</button>
            <h2>Liste des utilisateurs et leurs équipes</h2>
            <table class="table table-striped mt-4">
                <thead class="thead-dark">
                    <tr>
                        <th>Nom de l'Utilisateur</th>
                        <th>Équipes</th>
                    </tr>
                </thead>
                <tbody id="user-teams"></tbody>
            </table>
        </div>
        <div id="leave-summary" class="content-section">
            <h2>Résumé des Congés</h2>
            <div class="filters">
                <select id="team-filter-summary" class="form-control mb-2">
                    <option value="all">Sélectionner une équipe</option>
                    <option value="RH">RH</option>
                    <option value="Développement">Développement</option>
                    <option value="Marketing">Marketing</option>
                    <!-- Ajouter d'autres équipes si nécessaire -->
                </select>
                <select id="year-filter-summary" class="form-control mb-2">
                    <option value="all">Sélectionner une année</option>
                    <option value="2024">2024</option>
                    <option value="2025">2025</option>
                    <option value="2026">2026</option>
                    <!-- Ajouter d'autres années si nécessaire -->
                </select>
                <button class="btn btn-primary" onclick="filterLeaveSummary()">Filtrer</button>
            </div>
            <table class="table table-striped mt-4">
                <thead class="thead-dark">
                    <tr>
                        <th>Nom de l'Utilisateur</th>
                        <th>Équipe</th>
                        <th>Total Congés Donnés (Année)</th>
                        <th>Total Congés Disponibles (Année)</th>
                    </tr>
                </thead>
                <tbody id="leave-summary-body"></tbody>
            </table>
        </div>
    </div>



    <div id="absence-calendar" class="content-section" style="display: none;">
    <!-- Le contenu de CalendrierAbsence.php sera injecté ici -->
    </div>




<script>
 

    window.onload = function() {
        // Hide all sections by default
        const sections = document.querySelectorAll('.content-section');
        sections.forEach(section => {
            section.style.display = 'none';
        });
        // Show the "Manage Leave" section by default
        showSection('manage-leave');
    }

    function showSection(sectionId) {
        const sections = document.querySelectorAll('.content-section');
        sections.forEach(section => {
            section.style.display = 'none';
        });
        document.getElementById(sectionId).style.display = 'block';
    }

    function toggleManageLeave() {
        showSection('manage-leave');
    }

    function toggleDashboard() {
        showSection('dashboard');
    }

    function toggleAssignTeam() {
        showSection('assign-team');
    }
    
    function toggleLeaveSummary() {
        showSection('leave-summary');
    }

function loadLeaveRequests() {
    fetch('backend_admin.php')
        .then(response => response.json())
        .then(data => {
            displayRequests(data);
        });
}

function displayRequests(data) {
    const tbody = document.getElementById('leave-requests');
    tbody.innerHTML = ''; // Réinitialiser le contenu
    data.forEach(demande => {
        tbody.innerHTML += `
            <tr>
                <td>${demande.nom}</td>
                <td>${demande.type_conge}</td>
                <td>${demande.date_debut}</td>
                <td>${demande.date_fin}</td>
                <td>${demande.heures_demandes}</td>
                <td>${demande.jours_demandes}</td>
                <td>${demande.heures_demandes * demande.jours_demandes}</td>
                <td>${demande.departement}</td>
                <td id="status-${demande.id}">${demande.status}</td>
                <td id="action-${demande.id}">
                        ${
                        demande.status === 'en attente'
                        ? `
                            <button class="btn btn-success" onclick="showConfirmationModal(${demande.id}, 'approuvé')">Approuver</button>
                            <button class="btn btn-danger" onclick="showConfirmationModal(${demande.id}, 'refusé')">Refuser</button>
                        `
                        : `<span>Aucune action</span>`
                    }
                </td>
            </tr>
        `;
    });
}
function updateStatus(id, status) {
    fetch('backend_admin.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: `id=${id}&status=${status}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Mettre à jour le statut dans le tableau
            document.getElementById(`status-${id}`).textContent = status;
            document.getElementById(`action-${id}`).innerHTML = '<span>Aucune action</span>';
            
            // Afficher l'alerte de succès
            alert('Le statut a été mis à jour avec succès !');
            // Recharger les demandes de congés pour actualiser l'interface
            loadLeaveRequests();
        } else {
            alert('La mise à jour du statut a échoué.');
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        alert('Une erreur est survenue lors de la mise à jour du statut.');
    });
}

function showConfirmationModal(id, status) {
    fetch(`backend_admin.php?request_id=${id}`)
        .then(response => response.json())
        .then(data => {
            const modal = document.getElementById('confirmationModal');
            let requestDetails = `
                <p>La demande de <strong>${data.nom}</strong> dans les équipes <strong>${data.equipe}</strong>.</p>
                <p>Période : <strong>${data.date_debut}</strong> à <strong>${data.date_fin}</strong>.</p>
            `;

            data.teamAbsences.forEach(team => {
                const absencesList = team.absences.map(absence => absence.nom).join(', ');
                requestDetails += `
                    <p><strong>Équipe ${team.team} :</strong></p>
                    <p>Nombre total d'utilisateurs en congé : ${team.totalInPeriod}</p>
                    ${team.totalInPeriod > 0 ? `<p>Utilisateurs en congé : ${absencesList}</p>` : ''}
                    ${team.recommendation ? `<p style="color: red;">Recommandation : Il est préférable de ne pas approuver cette demande pour maintenir le travail dans cette équipe.</p>` : ''}
                `;
            });

            document.getElementById('requestDetails').innerHTML = requestDetails;
            modal.style.display = 'block';

            // Bouton de confirmation
            document.getElementById('confirmAction').onclick = function () {
                updateStatus(id, status);
                modal.style.display = 'none';
            };

            // Bouton d'annulation
            document.getElementById('cancelAction').onclick = function () {
                modal.style.display = 'none';
            };
        })
        .catch(error => {
            console.error('Erreur lors de la récupération des données :', error);
        });
}

function filterRequests() {
    const search = document.getElementById('search').value.toLowerCase();
    const teamFilter = document.getElementById('team-filter').value;
    const dateFilter = document.getElementById('date-filter').value;
    const dep = document.getElementById('department-filter').value;

    const statusFilter = document.getElementById('status-filter').value;

    fetch('backend_admin.php')
        .then(response => response.json())
        .then(data => {
            const filteredData = data.filter(demande => {
                return (
                    (dep === 'all' || demande.departement === dep) &&
                    (teamFilter === 'all' || demande.equipe.split('+').includes(teamFilter)) &&
                    (statusFilter === 'all' || demande.status === statusFilter) &&
                    (!dateFilter || demande.date_debut <= dateFilter && demande.date_fin >= dateFilter) &&
                    (demande.nom.toLowerCase().includes(search) || demande.date_debut.includes(search))
                );
            });
            displayRequests(filteredData);
        });
}
let notifications = [];

function toggleNotifications() {
    const notificationsWindow = document.getElementById('notifications-window');
    notificationsWindow.innerHTML = notifications.map((notif, index) => `
        <div class="notification-item" onclick="openNotification(${index})">${notif}</div>
    `).join('');
    notificationsWindow.style.display = notificationsWindow.style.display === 'block' ? 'none' : 'block';
    document.getElementById('notification-count').textContent = '0'; // Mark notifications as read
}

function openNotification(index) {
    toggleNotifications();
    highlightRequest(notifications[index]);
}

function highlightRequest(notification) {
    const rows = document.querySelectorAll('#leave-requests tr');
    rows.forEach(row => {
        if (row.innerText.includes(notification.split(' ')[0])) { // Assuming the notification contains the employee's name
            row.classList.add('highlight');
            row.scrollIntoView({ behavior: 'smooth', block: 'center' });
        } else {
            row.classList.remove('highlight');
        }
    });
}

document.addEventListener('DOMContentLoaded', () => {
    // Charger les notifications depuis le backend PHP
    fetch('backend_admin.php?notifications=true')  // Remplacez par le chemin vers votre fichier PHP
        .then(response => response.json())
        .then(data => {
            notifications = data;
            document.getElementById('notification-count').textContent = notifications.length;
        })
        .catch(error => {
            console.error('Erreur lors du chargement des notifications:', error);
        });

    // Charger les demandes de congés
    loadLeaveRequests();
    // Charger les utilisateurs pour l'affectation aux équipes
    loadUsers();
    // Charger les utilisateurs et leurs équipes
    loadUserTeams();
    // Charger le résumé des congés
    loadLeaveSummary();
});

function loadUsers() {
    fetch('backend_admin.php?users=true')
        .then(response => response.json())
        .then(data => {
            const userSelect = document.getElementById('user-select');
            userSelect.innerHTML = data.map(user => `<option value="${user.id}">${user.nom}</option>`).join('');
        })
        .catch(error => {
            console.error('Erreur lors du chargement des utilisateurs:', error);
        });
}

function assignTeam() {
    const userId = document.getElementById('user-select').value;
    const team = document.getElementById('team-select').value;

    fetch('backend_admin.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: `user_id=${userId}&team=${team}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('L\'utilisateur a été affecté à l\'équipe avec succès !');
            loadUserTeams(); // Reload the user teams after successful assignment
        } else {
            alert('L\'affectation de l\'utilisateur à l\'équipe a échoué.');
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        alert('Une erreur est survenue lors de l\'affectation de l\'utilisateur à l\'équipe.');
    });
}

function loadUserTeams() {
    fetch('backend_admin.php?user_teams=true')
        .then(response => response.json())
        .then(data => {
            const tbody = document.getElementById('user-teams');
            tbody.innerHTML = ''; // Réinitialiser le contenu
            data.forEach(user => {
                tbody.innerHTML += `
                    <tr>
                        <td>${user.nom}</td>
                        <td>${user.equipes.join(', ')}</td>
                    </tr>
                `;
            });
        })
        .catch(error => {
            console.error('Erreur lors du chargement des utilisateurs et de leurs équipes:', error);
        });
}

function loadLeaveSummary() {
    fetch('backend_admin.php?leave_summary=true')
        .then(response => response.json())
        .then(data => {
            displayLeaveSummary(data);
        });
}


function displayLeaveSummary(data) {
    const tbody = document.getElementById('leave-summary-body');
    tbody.innerHTML = ''; // Réinitialiser le contenu
    data.sort((a, b) => b.totalCongeDisponibleAnnee - a.totalCongeDisponibleAnnee); // Trier par Total Congés Disponibles (Année) en ordre décroissant
    data.forEach(user => {
        tbody.innerHTML += `
            <tr>
                <td>${user.nom}</td>
                <td>${user.equipe}</td>
                <td>${user.totalCongeDonneAnnee || 0} jours</td>
                <td>${user.totalCongeDisponibleAnnee || 60} jours</td>
            </tr>
        `;
    });
}

function filterLeaveSummary() {
    const team = document.getElementById('team-filter-summary').value;
    const year = document.getElementById('year-filter-summary').value;

    fetch(`backend_admin.php?leave_summary=true&team=${team}&year=${year}`)
        .then(response => response.json())
        .then(data => {
            displayLeaveSummary(data);
        });
}

function logout() {
    fetch('backend_admin.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: 'logout=true'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            window.location.href = 'PageAcceuil.html';
        } else {
            alert('La déconnexion a échoué.');
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        alert('Une erreur est survenue lors de la déconnexion.');
    });
}


   // Fonction pour charger le calendrier et afficher les absences
function loadCalendarContent() {
    // Masquer toutes les sections
    const sections = document.querySelectorAll(".content-section");
    sections.forEach(section => section.style.display = "none");

    // Afficher la section du calendrier
    const calendarSection = document.getElementById("absence-calendar");
    calendarSection.style.display = "block";

    // Ajouter un indicateur de chargement
    calendarSection.innerHTML = "<p>Chargement du calendrier...</p>";

    // Charger le contenu de CalendrierAbsence.php
    fetch("CalendrierAbsence.php")
        .then(response => {
            if (!response.ok) {
                throw new Error("Erreur lors du chargement du calendrier.");
            }
            return response.text();
        })
        .then(html => {
            // Injecter le contenu dans la section
            calendarSection.innerHTML = html;

            // Après l'injection du contenu, recharger ou exécuter les scripts
            const scripts = calendarSection.querySelectorAll("script");
            scripts.forEach(script => {
                const newScript = document.createElement("script");
                newScript.textContent = script.textContent;
                document.body.appendChild(newScript);
            });

            // Initialiser le calendrier après l'injection
            generateCalendar(); // Appeler une fonction d'initialisation, si nécessaire

            // Charger les absences pour l'employé sélectionné
            const employeeId = document.getElementById('employee').value;
            const month = document.getElementById('month').value;
            const year = document.getElementById('year').value;

            if (employeeId) {
                fetchAbsencesForEmployee(employeeId, month, year); // Charger et afficher les absences
            }
        })
        .catch(error => {
            // Afficher un message d'erreur si un problème survient
            calendarSection.innerHTML = `<p>Erreur : ${error.message}</p>`;
        });
}

// Fonction pour récupérer les absences d'un employé
function fetchAbsencesForEmployee(employeeId, month, year) {
    fetch(`getAbsences.php?employeeId=${employeeId}&month=${month}&year=${year}`)
        .then(response => response.json())
        .then(absences => {
            highlightAbsences(absences, month, year); // Mettre en surbrillance les absences
        })
        .catch(error => {
            console.error('Erreur lors de la récupération des absences :', error);
        });
}

// Fonction pour surligner les absences et appliquer les couleurs
function highlightAbsences(absences, month, year) {
    document.querySelectorAll('td').forEach(td => td.classList.remove('highlight', 'conge', 'maladie'));

    absences.forEach(absence => {
        const { date_debut, date_fin, type_conge, color } = absence;
        const startDate = new Date(date_debut);
        const endDate = new Date(date_fin);

        // Assurez-vous que la date de début et de fin correspondent à l'année et au mois sélectionnés
        let currentDate = new Date(startDate);
        
        // Ajoutez une couleur par défaut pour le type "Maladie" si elle n'est pas définie
        let absenceColor = color || (type_conge === "maladie" ? "orange" : "green");

        while (currentDate <= endDate) {
            if (currentDate.getMonth() + 1 === month && currentDate.getFullYear() === year) {
                const cell = getCalendarCell(currentDate.getDate(), currentDate.getMonth(), currentDate.getFullYear());
                if (cell) {
                    cell.classList.add('highlight');
                    cell.style.backgroundColor = absenceColor; // Appliquer la couleur en fonction du type de congé
                }
            }
            currentDate.setDate(currentDate.getDate() + 1); // Passer au jour suivant
        }
    });
}
</script>
<!-- Confirmation Modal -->
<div id="confirmationModal" class="modal">
    <div id="requestDetails"></div>
    <p>Êtes-vous sûr de vouloir mettre à jour le statut de cette demande ?</p>
    <button id="confirmAction" class="btn btn-primary">Confirmer</button>
    <button id="cancelAction" class="btn btn-secondary">Annuler</button>
</div>

</body>
</html>
