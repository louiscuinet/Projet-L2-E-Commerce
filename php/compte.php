<?php

require_once 'bibli_generale.php';
require_once ('bibli_bookshop.php');

// bufferisation des sorties
ob_start();

// démarrage ou reprise de la session
session_start();

// Redirection si l'utilisateur pas connecté
if (!isset($_SESSION['cliID'])) {
    header('Location: connexion.php');
    exit();
}

$cliID = $_SESSION['cliID'];
$bd = bdConnect();
$messages = ['perso' => '', 'livraison' => ''];
$erreurs = ['perso' => [], 'livraison' => []];

// Récupération des données du client
$sql = "SELECT cliNom, cliPrenom, cliEmail, cliTelephone, cliDateNaissance, cliAdresse, cliCP, cliVille, cliPays FROM client WHERE cliID = $cliID";
$res = bdSendRequest($bd, $sql);
$client = mysqli_fetch_assoc($res);
mysqli_free_result($res);

// Initialisation des variables
$nom = $client['cliNom'];
$prenom = $client['cliPrenom'];
$email = $client['cliEmail'];
$telephone = $client['cliTelephone'];
$dateNaissance = $client['cliDateNaissance'];


if ($dateNaissance !== '') {
    // On tente de la convertir au format YYYY-MM-DD si besoin
    $timestamp = strtotime($dateNaissance);
    if ($timestamp !== false) {
        $dateNaissance = date('Y-m-d', $timestamp);
    } else {
        $dateNaissance = ''; // au cas ou elle est illisible
    }
}



$adresse = $client['cliAdresse'] ?? '';
$cp = $client['cliCP'] ?? '';
$ville = $client['cliVille'] ?? '';
$pays = $client['cliPays'] ?? '';

// Traitement formulaire informations personnelles
if (isset($_POST['form_perso'])) {
    $nom = trim($_POST['nom']);
    $prenom = trim($_POST['prenom']);

    if ($nom === '') {
        $erreurs['perso'][] = 'Le nom est requis.';
    }
    if ($prenom === '') {
        $erreurs['perso'][] = 'Le prénom est requis.';
    }
    $email = trim($_POST['email']);
    $telephone = trim($_POST['telephone']);
    $mdp = $_POST['mdp'];
    $mdp_confirm = $_POST['mdp_confirm'];

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erreurs['perso'][] = 'Adresse email invalide.';
    }
if ($telephone !== '' && !preg_match('/^\+?[0-9\s\-.]+$/', $telephone)) {
    $erreurs['perso'][] = 'Numéro de téléphone invalide.';
}

    if ($mdp !== '') {
        if (strlen($mdp) < 4) {
            $erreurs['perso'][] = 'Le mot de passe doit contenir au moins 4 caractères.';
        }
        if ($mdp !== $mdp_confirm) {
            $erreurs['perso'][] = 'Les mots de passe ne correspondent pas.';
        }
    }

    if (empty($erreurs['perso'])) {
    $nom_esc = mysqli_real_escape_string($bd, $nom);
    $prenom_esc = mysqli_real_escape_string($bd, $prenom);
    $email_esc = mysqli_real_escape_string($bd, $email);
    $tel_esc = mysqli_real_escape_string($bd, $telephone);

    $sql = "UPDATE client SET cliNom='$nom_esc', cliPrenom='$prenom_esc', cliEmail='$email_esc', cliTelephone='$tel_esc'";

    if ($mdp !== '') {
        $mdp_hash = password_hash($mdp, PASSWORD_DEFAULT);
        $mdp_esc = mysqli_real_escape_string($bd, $mdp_hash);
        $sql .= ", cliPassword='$mdp_esc'";
    }
    $sql .= " WHERE cliID=$cliID";

    if (bdSendRequest($bd, $sql)) {
        $messages['perso'] = 'Vos informations personnelles ont été mises à jour.';
    } else {
        $erreurs['perso'][] = 'Erreur lors de la mise à jour.';
    }
}
}

// Traitement formulaire livraison
if (isset($_POST['form_livraison'])) {
    $adresse = trim($_POST['adresse']);
    $cp = trim($_POST['cp']);
    $ville = trim($_POST['ville']);
    $pays = trim($_POST['pays']);

    if ($adresse === '') $erreurs['livraison'][] = 'L\'adresse est requise.';
    if (!preg_match('/^\d{5}$/', $cp)) $erreurs['livraison'][] = 'Code postal invalide.';
    if ($ville === '') $erreurs['livraison'][] = 'La ville est requise.';
    if ($pays === '') $erreurs['livraison'][] = 'Le pays est requis.';

    if (empty($erreurs['livraison'])) {
        $adresse_esc = mysqli_real_escape_string($bd, $adresse);
        $cp_esc = mysqli_real_escape_string($bd, $cp);
        $ville_esc = mysqli_real_escape_string($bd, $ville);
        $pays_esc = mysqli_real_escape_string($bd, $pays);

        $sql = "UPDATE client SET cliAdresse='$adresse_esc', cliCP='$cp_esc', cliVille='$ville_esc', cliPays='$pays_esc' WHERE cliID=$cliID";
        if (bdSendRequest($bd, $sql)) {
            $messages['livraison'] = 'Vos informations de livraison ont été mises à jour.';
        } else {
            $erreurs['livraison'][] = 'Erreur lors de la mise à jour.';
        }
    }
}

mysqli_close($bd);

// Affichage HTML
affDebutEnseigneEntete('BookShop | Mon Compte');
echo '<h1>Mon Compte</h1>';

// --- Bloc Informations personnelles ---
echo '<h2>Informations personnelles</h2>';
if ($messages['perso']) echo '<p style="color:green;">' , htmlspecialchars($messages['perso']) , '</p>';
foreach ($erreurs['perso'] as $err) echo '<p style="color:red;">' , htmlspecialchars($err) , '</p>';

// Centre uniquement le formulaire infos perso
echo '<div style="width: 400px; margin: 0 auto;">';

echo '
<form method="post" action="compte.php">
    <input type="hidden" name="form_perso" value="1">
    <label>Nom : <input type="text" name="nom" value="' , htmlspecialchars($nom) , '" required></label><br>
    <label>Prénom : <input type="text" name="prenom" value="' , htmlspecialchars($prenom) , '" required></label><br>
    <label>Email : <input type="email" name="email" value="' , htmlspecialchars($email) , '" required></label><br>
    <label>Téléphone : <input type="text" name="telephone" value="' , htmlspecialchars($telephone) , '" required></label><br>
    <label>Date de naissance : <input type="date" name="dateNaissance" value="' , htmlspecialchars($dateNaissance) , '" required></label><br>',
    '<label>Nouveau mot de passe : <input type="password" name="mdp"></label><br>',
    '<label>Confirmation mot de passe : <input type="password" name="mdp_confirm"></label><br>',
    '<button type="submit">Mettre à jour</button>',
'</form>',

'</div>', // Fin div centrée pour formulaire perso

// --- Bloc Livraison ---
'<h2>Adresse de livraison</h2>';
if ($messages['livraison']) echo '<p style="color:green;">' , htmlspecialchars($messages['livraison']) , '</p>';
foreach ($erreurs['livraison'] as $err) echo '<p style="color:red;">' , htmlspecialchars($err) , '</p>';

// Centre uniquement le formulaire Livraison
echo '<div style="width: 400px; margin: 0 auto;">',

'<form method="post" action="compte.php">',
    '<input type="hidden" name="form_livraison" value="1">',
    '<label>Adresse : <input type="text" name="adresse" value="' , htmlspecialchars($adresse) , '" required></label><br>',
    '<label>Code postal : <input type="text" name="cp" value="' , htmlspecialchars($cp) , '" required></label><br>',
    '<label>Ville : <input type="text" name="ville" value="' , htmlspecialchars($ville) , '" required></label><br>',
    '<label>Pays : <input type="text" name="pays" value="' , htmlspecialchars($pays) , '" required></label><br>',
    '<button type="submit">Mettre à jour</button>',
'</form>',

'</div>'; // Fin div centrée pour formulaire livraison

echo    '<h2>Historique des commandes</h2>',
        '<div style="text-align: center; margin-bottom: 20px;">',
        '<form action="commande.php" method="get">',
        '<button type="submit" style="padding: 10px 20px; background-color: #007BFF; color: white; border: none; border-radius: 5px; cursor: pointer;">Voir mon historique de commandes</button>',
        '</form>',
        '</div>';


affPiedFin();
ob_end_flush();
