<?php
session_start();

// Afficher toutes les erreurs pour faciliter le débogage
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Récupérer l'ID utilisateur depuis la session ou via GET
$utilisateur_id = $_GET['utilisateur_id'] ?? null;

if ($utilisateur_id === null) {
    echo "Erreur : utilisateur_id non fourni.";
    exit;
}



?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Demande de Congé</title>
</head>
<style>
          /* Style global */
          body {
    background-color: #f4f4f9; /* Couleur de fond douce */
    font-family: Arial, sans-serif;
    margin: 0;
    padding: 10px;
}

/* Conteneur principal du formulaire */
.card {
    background-color: #ffffff;
    padding: 15px;
    border-radius: 5px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    max-width: 600px;
    margin: 0 auto;
    max: height 90vh;;
    font-family: 'Gill Sans', 'Gill Sans MT', Calibri, 'Trebuchet MS', sans-serif;
}

/* Titres */
h2 {
    color: #4CAF50; /* Couleur principale */
    font-size: rem;
    text-align: center;
    font-weight: 600;
    margin-bottom: 1rem;
    font-family: 'Times New Roman', Times, serif;
}
h3 {
    color: #741176;
    font-size: 1.5rem;
    font-weight: 600;
    margin-bottom: 1rem;
}

/* Description de l'introduction */
p {
    color: #ff0000;
    font-size: 1rem;
    margin-bottom: 1.5rem;
}

/* Labels */
label {
    display: block;
    color: #e911de;
    font-size: 1rem;
    margin-bottom: 0.3rem;
    font-family: 'Gill Sans', 'Gill Sans MT', Calibri, 'Trebuchet MS', sans-serif;
}

/* Champs de formulaire */
input[type="date"],
input[type="number"],
select {
    width: calc(100% - 20px);
    padding: 8px;
    margin-bottom: 1rem;
    border-radius: 5px;
    border: 2px solid #0a0a0a;
    font-size: 1rem;
    transition: border 0.4s ease, box-shadow 0.5s ease;
}

/* Focus sur les champs de formulaire */
input[type="date"]:focus,
input[type="number"]:focus,
select:focus {
    border-color: #070707;
    box-shadow: 0 0 5px rgba(1, 1, 1, 0.3);
}

/* Groupes de boutons radio */
.heure_demande1 div {
        display: flex;
        align-items: center;
        margin-bottom: 0.8rem;
      }

      .heure_demande1 label {
        font-size: 0.9rem;
      }

input[type="radio"] {
    margin-right: 10px;
}
/* Groupes de boutons radio */
.heure_demande1 {
    margin-bottom: 1.5rem;
}

/* Aligner les boutons radio et le texte à côté */
.heure_demande1 div {
    display: flex;
    align-items: center; /* Aligner les éléments verticalement */
    margin-bottom: 1rem;
}

input[type="radio"] {
    margin-right: 7px; /* Espacement entre le bouton radio et le texte */
}

/* Vous pouvez également ajuster le label si nécessaire */
.heure_demande1 label {
    font-size: 1rem;
    color: #333;
}


/* Boutons */
button {
        background-color: #6b086c;
        color: white;
        padding: 10px;
        font-size: 1rem;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        width: calc(50% - 5px);
      }


button:hover {
    background-color: #410e55;
    transform: translateY(-2px); /* Effet de survol */
}

button:focus {
    outline: none;
}

/* Mise en page pour les erreurs */
.error-message {
    color: red;
    font-size: 0.875rem;
    margin-top: 0.5rem;
}

/* Structure flex pour les sections à afficher sur plusieurs lignes */
.date_absence1,
.nbjours {
    display: flex;
    justify-content: space-between;
}

.date_debut,
.date_absence2 {
    flex: 1;
    margin-right: 10px;
}

.nbjours2,
.nbheures {
    flex: 1;
    margin-right: 10px;
}

/* Checkbox pour "Retour au travail" */
.retour_travail {
    display: flex;
    align-items: center;
    margin-bottom: 5;
}

.retour_travail input[type="checkbox"] {
    margin-right: 8px;
}

/* Mise en page responsive */
@media (max-width: 600px) {
    .date_absence1 {
        flex-direction: column;
    }

    .nbjours {
        flex-direction: column;
    }

    button {
        width: auto;
    }
}
@media (max-width: 600px) {
        .button-group button {
          width: 100%;
          margin-bottom: 10px;
        }
    }
/* Conteneur des boutons */
.button-group {
    display: flex;
    justify-content: space-between; /* Espacement entre les deux boutons */
    align-items: flex-end; /* Aligner les boutons en bas */
}

.button-group .button {
    width: auto; /* Les boutons prennent leur largeur naturelle */
    padding: 10px 20px;
    font-size: 1rem;
    border-radius: 10px;
    cursor: pointer;
    transition: background-color 0.3s ease, transform 0.3s ease;
}

/* Bouton "Précédent" (positionné en bas à gauche) */
.button-group .button:first-child {
    align-self: flex-end; /* Alignement du bouton "Précédent" en bas à gauche */
}

/* Bouton "Envoyer" (positionné en haut à droite) */
.button-group .button:last-child {
    align-self: flex-start; /* Alignement du bouton "Envoyer" en haut à droite */
}

/* Ajoutez un espacement entre les boutons si nécessaire */
.button-group .button:not(:last-child) {
    margin-right: 10px;
}
.close-button {
    position: absolute;
    top: 5px;
    right: 10px; /* Ajusté pour un alignement plus naturel */
    font-size: 24px;
    cursor: pointer;
    color: #ff0000;
    background-color: transparent;
    border: none;
    padding: 2px;
    border-radius: 20%;
    transition: color 0.3s ease, background-color 0.3s ease;
}    
</style>

<body>
<div class="card" id="illnessReport">
      <h2>Nouveau rapport de maladie</h2>
      <p>Veuillez remplir le formulaire ci-dessous pour signaler votre absence.</p>

      <!-- Type de maladie -->
      <h3 for="illness-type">Type de maladie :</h3>    
      <form method="GET" action="demandes_conges.php">
    <input type="hidden" name="utilisateur_id" value="<?php echo $utilisateur_id; ?>">
        <select name="type_conge" id="type_conge" class="maladie">
        <option value=""disabled selected>Sélectionner</option>
        <option value="cold">Rhume / Grippe</option>
        <option value="dental-problems">Problèmes dentaires</option>
        <option value="eye-problems">Problèmes oculaires</option>
        <option value="fainting-dizziness">Évanouissement / Vertiges</option>
        <option value="food-poisoning">Intoxication alimentaire</option>
        <option value="medical-appointment">Rendez-vous médical</option>
        <option value="mental-health">Problèmes de santé mentale</option>
        <option value="migraine-headache">Migraine / Maux de tête</option>
        <option value="operation">Opération</option>
        <option value="other">Autre - Veuillez fournir plus d'informations</option>
        <option value="pregnancy">Problèmes liés à la grossesse</option>
        <option value="sickness-nausea">Maladie / Nausées</option>
        
    </select>
         <!-- Dates d'absence -->
      <h3 class="date_absence">Veuillez saisir les dates d'absence</h3>
      <div class="date_absence1">
        <div class="date_debut">
          <label for="end-date">Date de fin :</label>
          <input type="date" name="date_fin" id="end-date" class="mb-2"/>
        </div>
        <div class="date_absence2">
          <label for="date_fin">Date de début :</label>
          <input type="date" name="date_debut" id="start-date" />
        </div>
      </div>

      <div class="retour_travail">
        <input type="checkbox"  name="retour_travail" id="returned" class="retour"/>
        <label for="returned">Êtes-vous retourné au travail ?</label>
      </div>

        <!-- Heure demandée -->
        <h3 class="heure_demande">Heure demandée</h3>
      <div class="heure_demande1">
        <div class="journee">
          <input type="radio" id="full-day" name="work-day" />
          <label for="full-day">Journée complète</label>
        </div>
        <div class="matin">
          <input type="radio" id="morning" name="work-day" />
          <label for="morning">Matin seulement</label>
        </div>
        <div class="midi">
          <input type="radio" id="afternoon" name="work-day" />
          <label for="afternoon">Après-midi seulement</label>
        </div>
      </div>

      <!-- Nombre de jours et d'heures -->
      <div class="nbjours">
        <div class="nbjours2">
          <label for="work-days">Nombres de jours :</label>
          <input type="number" name="jours_demandes" id="work-days" min="1" step="1" class="mb-2" />
          <div id="work-days-error" class="error-message"></div>
        </div>
        <div class="nbheures">
          <label for="work-hours">Nombre d'Heures :</label>
          <input type="number" name="heures_demandes" id="work-hours" min="1" step="1" class="mb-2" />
          <div id="work-hours-error" class="error-message"></div>
        </div>
      </div>


        <button type="submit">Envoyer la demande</button>
    <!-- Close button -->
    <div class="close-button" onclick="closeContainer()">×</div>
    </div>
    </div>

    <script>
    // Fonction pour vérifier si un nombre est un entier positif
function isPositiveInteger(value) {
    return Number.isInteger(Number(value)) && value > 0;
}

// Fonction de validation des champs "Jours ouvrables" et "Heures"
function validateForm(event) {
    event.preventDefault(); // Empêche la soumission par défaut

    // Récupération des valeurs des champs
    const illnessType = document.getElementById('illness-type').value;
    const startDate = document.getElementById('start-date').value;
    const endDate = document.getElementById('end-date').value;
    const workDays = document.getElementById('work-days').value;
    const workHours = document.getElementById('work-hours').value;


    let isValid = true;

    // Réinitialiser les messages d'erreur
    document.getElementById('work-days-error').textContent = '';
    document.getElementById('work-hours-error').textContent = '';

    // Vérifier que tous les champs sont remplis
    if (!illnessType || !startDate || !endDate || !workDays || !workHours) {
        alert("Tous les champs doivent être remplis.");
        isValid = false;
    }

   
    // Si le formulaire est valide
    if (isValid) {
        alert("Formulaire validé avec succès !");
    }
     // Existing JavaScript code



}

// Ajout d'un gestionnaire d'événements pour le bouton de soumission
document.getElementById('send-button').addEventListener('click', validateForm);
 // Existing JavaScript code

 
// Fonction pour fermer la carte et masquer la croix
function closeContainer() {
    const card = document.querySelector('.card');
    const closeButton = document.querySelector('.close-button')

    // Masquer la carte et la croix
    if (card) card.style.display = 'none';
    if (closeButton) closeButton.style.display = 'none';
}


</script>
  </body>
</html>