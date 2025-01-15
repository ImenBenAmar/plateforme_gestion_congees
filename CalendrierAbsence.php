<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calendrier Interactif</title>
    <style>
body {
    font-family: 'Roboto', sans-serif;
    background-color: #f0f4f8;
    display: flex;
    flex-direction: column;
    align-items: flex-end; /* Décale tous les éléments vers la droite */
    padding: 20px;
    margin: 0;
    color: #333;
    font-family: Georgia, 'Times New Roman', Times, serif;
    margin-right: 100px; /* Ajoute un décalage vers la droite */
}
h2 {font-family: Georgia, 'Times New Roman', Times, serif;
            font-size: 2rem;
            color: #6a0dad;;
            margin-bottom: 20px;
            text-align: center;
        }
form {
    background-color: #fff;
    padding: 20px;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    margin-bottom: 30px;
    display: flex;
    justify-content: flex-end; /* Aligne les éléments du formulaire à droite */
    gap: 20px;
    flex-wrap: wrap;
}
        select, input,button{
            padding: 10px;
            font-size: 1rem;
            border-radius: 8px;
            border: 1px solid #ccc;
            transition: all 0.3s ease;
            width: 150px;
        }

        select:focus, input:focus, button:focus {
            outline: none;
            border-color: #6a0dad;;
        }
        button {
    background-color: #6a0dad;
    color: white;
    cursor: pointer;
    border: none;
    width: 160px;
    margin-left: 20px; /* Ajoute un décalage au bouton */
}

        button:hover {
            background-color: #6a0dad;;
        }
        .absence-key {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-top: 15px;
            font-size: 1.1rem;
        }
        .absence-key div {
            display: flex;
            align-items: center;
            margin: 0 10px;
            cursor: pointer;
            padding: 10px;
            font-weight: bold;
        }
        .absence-key .conge {
            background-color: red;
            color: white;
            width: 10px;
            height: 10px;
            border-radius: 10px;
            justify-content: center;
            align-items: center;
            display: flex;
            text-align: center;
        }
        .absence-key .maladie {
            background-color: orange;
            color: white;
            width: 10px;
            height: 10px;
            border-radius: 10px;
            justify-content: center;
            align-items: center;
            display: flex;
            text-align: center;
        }
        table {
            font-family:Georgia, 'Times New Roman', Times, serif;
            width: 100%;
            max-width: 900px;
            border-collapse: collapse;
            background-color: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            margin-top: 30px;
        }
        th, td {
    font-family: Cambria, Cochin, Georgia, Times, 'Times New Roman', serif;
    border: 1px solid #ddd;
    padding: 12px;
    text-align: center;
    width: 14.28%;
    font-size: 1.1rem;
    color: #444;
    transition: background-color 0.3s, transform 0.2s ease-in-out;
}

th {
    background-color: #f5f5f5;
    font-weight: bold;
    color: #333;
}

td {
    background-color: #fff;
    cursor: pointer;
    transition: background-color 0.3s ease, transform 0.2s ease;
}

td:hover {
    background-color: #e3f2fd;
    transform: scale(1.1);
}

td.highlight {
    background-color: #e3c1e4; /* Light purple for highlighted absences */
    color: #fff;
}

td.conge {
    background-color: #ff6666; /* Red for "Congé" absence */
    color: #fff; /* White text for readability */
}

td.maladie {
    background-color: #ffcc99; /* Light orange for "Maladie" absence */
    color: #fff;
}

td:active {
    transform: scale(0.95);
}

.highlight {
    background-color: #d7e5ea28; /* Light blue background when clicked for absence */
}

.conge:hover {
    background-color: #ff4d4d; /* Darker red for hover effect on "Congé" cells */
}

.maladie:hover {
    background-color: #fbbf8c; /* Slightly darker orange for hover effect on "Maladie" cells */
}

    </style>
</head>
<body>

    <h2 id="calendar-title"></h2>
    
    <form id="calendar-form" action="getAbsences.php" method="POST">    
            <label for="month">Sélectionner un mois :</label>
        <select name="month" id="month">
            <option value="1">Janvier</option>
            <option value="2">Février</option>
            <option value="3">Mars</option>
            <option value="4">Avril</option>
            <option value="5">Mai</option>
            <option value="6">Juin</option>
            <option value="7">Juillet</option>
            <option value="8">Août</option>
            <option value="9">Septembre</option>
            <option value="10">Octobre</option>
            <option value="11">Novembre</option>
            <option value="12">Décembre</option>
        </select>
    
        <label for="year">Année :</label>
        <input type="number" name="year" id="year" value="2025" min="1900" max="2100">
    
        <label for="employee">Employé :</label>
        <select name="employee" id="employee" >
            <option value="">-- Sélectionnez un employé --</option>
            <?php include 'employees.php'; ?>
        </select>
    
        <button type="button" id="showAbsencesButton">Afficher</button>
        </form>
    
    <!-- Clé de Motif d'Absence -->
    <div class="absence-key">
    <span>Clé de motif d'Absence :</span>
    <div class="conge" onclick="setAbsenceType('C')">C</div>
    <span>Congé</span>
    <div class="maladie" onclick="setAbsenceType('M')">M</div>
    <span>Maladie</span>
</div>

    <table id="calendar-table">
        <thead>
            <tr>
                <th>Lundi</th>
                <th>Mardi</th>
                <th>Mercredi</th>
                <th>Jeudi</th>
                <th>Vendredi</th>
                <th>Samedi</th>
                <th>Dimanche</th>
            </tr>
        </thead>
        <tbody id="calendar-body">
        </tbody>
    </table>
    <div id="absence-summary" style="margin-top: 20px; text-align: center;">
    <h3>Résumé des absences pour l'année</h3>
    <ul id="annual-totals-list" style="list-style: none; padding: 0;"></ul>
</div>


    <script>
let absenceType = ''; // Pour garder la trace du type d'absence (Congé ou Maladie)
let currentMonth = 1; // Mois initial
let currentYear = 2024; // Année initiale

// Fonction pour générer le calendrier
function generateCalendar() {
    const monthSelect = document.getElementById('month');
    const yearInput = document.getElementById('year');
    const calendarBody = document.getElementById('calendar-body');
    const month = parseInt(monthSelect.value);
    const year = parseInt(yearInput.value);
    const monthNames = ["Janvier", "Février", "Mars", "Avril", "Mai", "Juin", "Juillet", "Août", "Septembre", "Octobre", "Novembre", "Décembre"];

    // Mise à jour du titre du calendrier
    document.getElementById('calendar-title').textContent = `Calendrier d'absence en ${monthNames[month - 1]} ${year}`;

    // Calculer le nombre de jours dans le mois et le premier jour de la semaine
    const daysInMonth = new Date(year, month, 0).getDate();
    const firstDay = new Date(year, month - 1, 1).getDay();
    const startDay = firstDay === 0 ? 7 : firstDay; // Ajuster si le premier jour est un dimanche

    // Réinitialiser le contenu du calendrier
    calendarBody.innerHTML = '';
    let row = document.createElement('tr');

    // Ajouter les cellules vides pour aligner le premier jour du mois
    for (let i = 1; i < startDay; i++) {
        row.appendChild(document.createElement('td'));
    }

    // Créer les cellules pour chaque jour du mois
    for (let day = 1; day <= daysInMonth; day++) {
        if ((startDay - 1 + day) % 7 === 1 && day !== 1) {
            calendarBody.appendChild(row);
            row = document.createElement('tr');
        }

        const cell = document.createElement('td');
        cell.textContent = day;
        cell.onclick = function() {
            document.querySelectorAll('td').forEach(td => td.classList.remove('highlight'));
            cell.classList.add('highlight');
            if (absenceType) {
                cell.textContent += ` (${absenceType})`;
            }
        };
        row.appendChild(cell);
    }

    // Ajouter des cellules vides pour compléter la dernière ligne
    while (row.children.length < 7) {
        row.appendChild(document.createElement('td'));
    }
    calendarBody.appendChild(row);

    // Récupérer les absences de l'employé sélectionné si un employé est choisi
    const employeeId = document.getElementById('employee').value;
    if (employeeId) {
        fetchAbsencesForEmployee(employeeId, month, year); // Passer le mois et l'année sélectionnés
    }
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
        while (currentDate <= endDate) {
            if (currentDate.getMonth() + 1 === month && currentDate.getFullYear() === year) {
                const cell = getCalendarCell(currentDate.getDate(), currentDate.getMonth(), currentDate.getFullYear());
                if (cell) {
                    cell.classList.add('highlight');
                    if (color) {
                        cell.style.backgroundColor = color; // Appliquer la couleur en fonction du type de congé
                    }
                }
            }
            currentDate.setDate(currentDate.getDate() + 1); // Passer au jour suivant
        }
    });
}



// Fonction pour obtenir la cellule du calendrier correspondant à une date spécifique
function getCalendarCell(day, month, year) {
    const calendarBody = document.getElementById('calendar-body');
    const rows = calendarBody.querySelectorAll('tr');
    let cell = null;

    rows.forEach(row => {
        row.querySelectorAll('td').forEach(td => {
            if (td.textContent == day) {
                const cellDate = new Date(year, month, day);
                if (cellDate.getDate() === day && cellDate.getMonth() === month && cellDate.getFullYear() === year) {
                    cell = td;
                }
            }
        });
    });

    return cell;
}

// Initialiser le calendrier avec la date actuelle
document.addEventListener('DOMContentLoaded', () => {
    const currentDate = new Date();
    document.getElementById('month').value = currentDate.getMonth() + 1;
    document.getElementById('year').value = currentDate.getFullYear();
    generateCalendar();
});

// Event listener pour générer le calendrier lorsque l'utilisateur sélectionne un mois/année
document.getElementById('calendar-form').addEventListener('submit', (e) => {
    e.preventDefault();
    generateCalendar();
});

// Event listener pour la sélection de l'employé
document.getElementById('employee').addEventListener('change', (event) => {
    generateCalendar(); // Re-générer le calendrier quand un employé est sélectionné
});

// Event listener pour le bouton "Afficher"
document.getElementById('showAbsencesButton').addEventListener('click', () => {
    generateCalendar(); // Générer le calendrier et afficher les absences
});

// Fonction pour récupérer les totaux annuels d'absences
function fetchAnnualTotals(employeeId, year) {
    fetch(`getAnnualTotals.php?employeeId=${employeeId}&year=${year}`)
        .then(response => response.json())
        .then(totals => {
            displayAnnualTotals(totals);
        })
        .catch(error => {
            console.error('Erreur lors de la récupération des totaux annuels :', error);
        });
}

// Fonction pour afficher les totaux annuels
function displayAnnualTotals(totals) {
    const totalsList = document.getElementById('annual-totals-list');
    totalsList.innerHTML = ''; // Effacer les anciens totaux

    if (totals.length === 0) {
        totalsList.innerHTML = '<li>Aucune absence enregistrée pour cette année.</li>';
        return;
    }

    totals.forEach(total => {
        const listItem = document.createElement('li');
        listItem.textContent = `${total.type_conge}: ${total.nombre_absences} absences (${total.total_jours} jours)`;
        totalsList.appendChild(listItem);
    });
}

// Mise à jour des événements liés au formulaire
document.getElementById('employee').addEventListener('change', () => {
    const employeeId = document.getElementById('employee').value;
    const year = document.getElementById('year').value;
    if (employeeId) {
        fetchAnnualTotals(employeeId, year); // Récupérer les totaux annuels lors de la sélection d'un employé
    }
});

document.getElementById('year').addEventListener('change', () => {
    const employeeId = document.getElementById('employee').value;
    const year = document.getElementById('year').value;
    if (employeeId) {
        fetchAnnualTotals(employeeId, year); // Recharger les totaux si l'année change
    }
});

</script>


    
    </body>
    </html>
    