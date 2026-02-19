<?php
require_once '../php/bibli_generale.php';
require_once '../php/bibli_bookshop.php';

ob_start(); // Démarre la bufferisation de la sortie

affDebutEnseigneEntete('BookShop | Inscription', false);

$errors = [];

// Vérification des paramètres reçus via POST
$clesObligatoires = ['nom', 'prenom', 'email', 'telephone', 'naissance', 'pass1', 'pass2'];
$clesFacultatives = ['cbCGU'];

if (!parametresControle('post', $clesObligatoires, $clesFacultatives)) {
    $errors[] = "Certains paramètres obligatoires sont manquants ou incorrects.";
}

// Récupération et nettoyage des données
if (empty($errors)) {
    $nom = trim($_POST['nom']);
    $prenom = trim($_POST['prenom']);
    $email = trim($_POST['email']);
    $telephone = trim($_POST['telephone']);
    $naissance = trim($_POST['naissance']);
    $pass1 = $_POST['pass1'];
    $pass2 = $_POST['pass2'];
    $cbCGU = isset($_POST['cbCGU']) ? $_POST['cbCGU'] : null;

    // Validation des données
    validerNomPrenom($nom, $prenom, $errors);
    validerEmail($email, $errors);
    validerTelephone($telephone, $errors);
    validerNaissance($naissance, $errors);
    validerMotDePasse($pass1, $pass2, $errors);
    validerCGU($cbCGU, $errors);

    // Si des erreurs existent
    if (!empty($errors)) {
        error($errors);
    }
}

affPiedFin();
ob_end_flush();

/**
 * Fonction pour vérifier le nom et prénom
 */
function validerNomPrenom(string $nom, string $prenom, array $errors): void {
    if (empty($nom) || !preg_match("/^[a-zA-Zà-ùÀ-Ù\-']+$/", $nom)) {
        $errors[] = "Le nom est invalide.";
    }
    if (empty($prenom) || !preg_match("/^[a-zA-Zà-ùÀ-Ù\-']+$/", $prenom)) {
        $errors[] = "Le prénom est invalide.";
    }
}

/**
 * Fonction pour vérifier l'email
 */
function validerEmail(string $email, array $errors): void {
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "L'email n'est pas valide.";
    } else {
        $bd = bdConnect();
        $emailEscaped = mysqli_real_escape_string($bd, $email);
        $sql = "SELECT 1 FROM clients WHERE email = '$emailEscaped'";
        $res = bdSendRequest($bd, $sql);
        if (mysqli_num_rows($res) > 0) {
            $errors[] = "Cette adresse email est déjà utilisée.";
        }
        mysqli_free_result($res);
    }
}

/**
 * Fonction pour vérifier le numéro de téléphone
 */
function validerTelephone(string $telephone, array $errors): void {
    if (!preg_match("/^\d{10}$/", $telephone)) {
        $errors[] = "Le numéro de téléphone doit comporter 10 chiffres.";
    }
}

/**
 * Fonction pour vérifier la date de naissance et l'âge
 */
function validerNaissance(string $naissance, array $errors): void {
    $birthDate = explode('-', $naissance);
    if (count($birthDate) === 3 && !checkdate($birthDate[1], $birthDate[2], $birthDate[0])) {
        $errors[] = "La date de naissance n'est pas valide.";
    }

    $age = date_diff(date_create($naissance), date_create('today'))->y;
    if ($age < 15) {
        $errors[] = "Vous devez avoir au moins 15 ans.";
    }
}

/**
 * Fonction pour vérifier le mot de passe
 */
function validerMotDePasse(string $pass1, string $pass2, array $errors): void {
    if (strlen($pass1) < 4) {
        $errors[] = "Le mot de passe doit comporter au moins 4 caractères.";
    }
    if ($pass1 !== $pass2) {
        $errors[] = "Les mots de passe ne sont pas identiques.";
    }
}

/**
 * Fonction pour vérifier l'acceptation des CGU
 */
function validerCGU($cbCGU, array $errors): void {
    if (is_null($cbCGU)) {
        $errors[] = "Vous devez accepter les conditions générales d'utilisation.";
    }
}

/**
 * Affiche les erreurs d'inscription
 */
function error(array $errors): void {
    // Titre de la section des erreurs
    echo '<h2>Vérification des données reçues</h2>';

    // Cadre délimité en rouge avec un message en gras
    echo '<div style="border: 2px solid red; padding: 10px; margin: 20px 0;">';
    echo '<strong style="color: red;">Les erreurs suivantes ont été relevées lors de votre inscription :</strong><br>';

    // Affichage des erreurs sous forme de liste avec des tirets
    echo '<ul>';
    foreach ($errors as $error) {
        echo '<li>- ' , htmlspecialchars($error) , '</li>';
    }
    echo '</ul>';

    echo '</div>';
}
?>
