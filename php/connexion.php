<?php

require_once '../php/bibli_generale.php';
require_once ('../php/bibli_bookshop.php');

ob_start();

session_start();

// Redirige l'utilisateur déjà connecté vers compte.php
if (isset($_SESSION['cliID'])) {
    header('Location: compte.php');
    exit();
}


// Sauvegarde de l’URL précédente si ce n’est pas déjà fait et qu’on ne vient pas de la page de connexion
if (!isset($_SESSION['previous_page']) && !str_contains($_SERVER['HTTP_REFERER'] ?? '', 'connexion.php')) {
    $_SESSION['previous_page'] = $_SERVER['HTTP_REFERER'] ?? '../index.php';
}

// Erreurs
$errs = [];

// Vérification des paramètres reçus dans l'URL ou le formulaire
if ($_POST) {
    if (!isset($_POST['email']) || !isset($_POST['password'])) {
        $errs[] = 'L\'adresse e-mail et le mot de passe doivent être fournis.';
    } else {
        $email = trim($_POST['email']);
        $password = $_POST['password'];

        // Connexion à la base de données
        $bd = bdConnect();

        // Sécurisation des entrées
        $email = mysqli_real_escape_string($bd, $email);
        
        // Vérification si le client est enregistré
        $sql = "SELECT cliID, cliPassword FROM client WHERE cliEmail = '$email'";
        $res = bdSendRequest($bd, $sql);

        if (mysqli_num_rows($res) == 0) {
            $errs[] = 'Aucun client trouvé avec cet e-mail.';
        } else {
            // Le client existe, vérification du mot de passe
            $client = mysqli_fetch_assoc($res);
            $hashedPassword = $client['cliPassword'];

            // Comparaison du mot de passe hashé
            if (!password_verify($password, $hashedPassword)) {
                $errs[] = 'Le mot de passe est incorrect.';
            } else {
                // Le mot de passe est correct, l'utilisateur est authentifié
                $_SESSION['cliID'] = $client['cliID'];
                $_SESSION['cliEmail'] = $email;
                $redirect = $_SESSION['previous_page'] ?? '../index.php';
		unset($_SESSION['previous_page']); // Nettoyage
		header("Location: $redirect");
		exit();
                
            }
        }

        // Libération des ressources
        mysqli_free_result($res);
        // Déconnexion de la base de données
        mysqli_close($bd);
    }
}



affDebutEnseigneEntete('BookShop | Connexion');


// Affichage des erreurs ou du formulaire
if ($errs) {
    $emailSaisi = isset($email) ? $email : ''; // définir $emailSaisi avec ce que l'utilisateur a saisi
    echo '<div style="margin: 50px auto; width: 60%; text-align: center;">',
    '<p class="error" style="color:red;"><strong>Erreur(s) détectée(s) :</strong></p>';
    foreach ($errs as $err) {
        echo '<p style="color:red;">' , htmlspecialchars($err) , '</p>';
    }
    echo '</div>';
	affContenuCo($emailSaisi); // <-- pré-remplir email
    }else {
    affContenuCo(); // formulaire vide au premier chargement

}


affPiedFin();

function affContenuCo(string $emailSaisi = ''): void {
    // Échapper pour éviter injection HTML
    $emailSafe = htmlspecialchars($emailSaisi);
     echo '
      <div style="display: flex; justify-content: center; align-items: flex-start; gap: 150px; margin-top: 50px;">',
        '<form method="POST" action="connexion.php" style="display: flex; flex-direction: column; width: 100px;">',
            '<input type="email" name="email" placeholder="Adresse e-mail" required style="margin-bottom: 15px; padding: 3px;" value="' , $emailSafe , '">',
            '<input type="password" name="password" placeholder="Mot de passe" required style="margin-bottom: 15px; padding: 3px;">',
            '<button type="submit" style="padding: 3px;">Se connecter</button>',
        '</form>',
        '<div style="text-align: center;">',
            '<h1>Pas encore de compte?</h1>',
            '<a href="inscription.php">',
                '<button type="button" style="padding: 3px;">S\'inscrire</button>',
            '</a>',
        '</div>',
    '</div>';
}
ob_end_flush();
