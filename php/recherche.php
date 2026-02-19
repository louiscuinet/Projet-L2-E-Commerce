<?php

/* ------------------------------------------------------------------------------
    Architecture de la page
    - étape 1 : vérification des paramètres reçus dans l'URL
    - étape 2 : génération du code HTML de la page
------------------------------------------------------------------------------*/

require_once '../php/bibli_generale.php';
require_once '../php/bibli_bookshop.php';

// bufferisation des sorties
ob_start();

// démarrage ou reprise de la session
session_start();

/*------------------------- Etape 1 --------------------------------------------
- vérification des paramètres reçu dans l'URL
------------------------------------------------------------------------------*/

// erreurs détectées dans l'URL
$errs = [];
$position = isset($_GET['p']) && ctype_digit($_GET['p']) ? (int)$_GET['p'] : 0;
$pagination = 4;
$totalLivres = isset($_GET['t']) && ctype_digit($_GET['t']) ? (int)$_GET['t'] : -1;
// critères de recherche si pas de paramètres reçus dans $_GET
$recherche = array('type' => 'auteur', 'quoi' => '');

if ($_GET){ // s'il y a des paramètres dans l'URL, c'est-à-dire <=> count($_GET) > 0
    if (! parametresControle('get', array('type', 'quoi'), array('p', 't'))) {
    	$errs[] = 'L\'URL doit être de la forme "' . basename($_SERVER['PHP_SELF']) .'?type=xxx&quoi=yyy".';
    }
    else{
        $oks = ['titre', 'auteur'];
        if (! in_array($_GET['type'], $oks)){
            $errs[] = 'La valeur du "type" doit être égale à "'.implode('" ou à "', $oks).'".';
        }
        $recherche['type'] = $_GET['type'];
        $recherche['quoi'] = trim($_GET['quoi']);
        $l1 = mb_strlen($recherche['quoi'], encoding:'UTF-8');
        if ($l1 < LMIN_CRITERE_RECHERCHE){
            $errs[] = 'Le critère de recherche doit contenir au moins '. LMIN_CRITERE_RECHERCHE . ' caractères.';
        }
        if ($l1 != mb_strlen(strip_tags($recherche['quoi']), encoding:'UTF-8')){
            $errs[] = 'Le critère de recherche ne doit pas contenir de tags HTML.';
        }
    }
}

/*------------------------- Etape 2 --------------------------------------------
- génération du code HTML de la page
------------------------------------------------------------------------------*/

affDebutEnseigneEntete('BookShop | Recherche');

affContenuL($recherche, $errs, $position, $pagination, $totalLivres);

affPiedFin();

// fin du script --> envoi de la page 
ob_end_flush();


// ----------  Fonctions locales au script ----------- //

/**
 *  Contenu de la page : formulaire et résultats de la recherche
 *
 * @param array  $recherche     critères de recherche (type et quoi)
 * @param array  $erreurs       erreurs détectées dans l'URL
 *
 * @return void
 */
function affContenuL(array $recherche, array $erreurs, int $position, int $pagination, int &$totalLivres) : void {
    echo '<h3>Recherche par nom d\'auteur ou titre</h3>';
    echo '<form action="', basename($_SERVER['PHP_SELF']), '" method="get">',
         '<p class="center">Rechercher <input type="text" name="quoi" minlength="2" value="', htmlProtegerSorties($recherche['quoi']), '">',
         ' dans ',
         '<select name="type">',
             '<option value="auteur" ', $recherche['type'] == 'auteur' ? 'selected' : '', '>auteurs</option>',
             '<option value="titre" ', $recherche['type'] == 'titre' ? 'selected' : '', '>titre</option>',
         '</select>',
         '<input type="submit" value="Rechercher">',
         '</p>',
         '</form>';

    if ($erreurs) {
        echo '<p class="error"><strong>Erreur(s) :</strong><br>', implode('<br>', $erreurs), '</p>';
        return;
    }

    if ($recherche['quoi']) {
        $bd = bdConnect();
        $q = mysqli_real_escape_string($bd, $recherche['quoi']);
        $critere = $recherche['type'] == 'auteur'
            ? "WHERE liID in (SELECT al_IDLivre FROM aut_livre INNER JOIN auteur ON al_IDAuteur = auID WHERE auNom LIKE '%$q%')"
            : "WHERE liTitre LIKE '%$q%'";

        $sqlTotal = "SELECT COUNT(DISTINCT liID) FROM ((livre INNER JOIN editeur ON liIDEditeur = edID)
                        INNER JOIN aut_livre ON al_IDLivre = liID)
                        INNER JOIN auteur ON al_IDAuteur = auID $critere";

        if ($totalLivres <= 0) {
            $resTotal = bdSendRequest($bd, $sqlTotal);
            $row = mysqli_fetch_row($resTotal);
            $totalLivres = (int)$row[0];
            mysqli_free_result($resTotal);
        }

        $sql = "SELECT liID, liTitre, liPrix, liNbPages, liISBN13, edNom, edWeb,
                       GROUP_CONCAT(CONCAT(auPrenom, '|', auNom) SEPARATOR '@') AS auteurs
                FROM ((livre INNER JOIN editeur ON liIDEditeur = edID)
                      INNER JOIN aut_livre ON al_IDLivre = liID)
                      INNER JOIN auteur ON al_IDAuteur = auID
                $critere
                GROUP BY liID
                ORDER BY liID
                LIMIT $position, $pagination";

        $res = bdSendRequest($bd, $sql);
        if (mysqli_num_rows($res) == 0){
            echo '<p>Aucun livre trouvé.</p>';
        } else {
            while ($t = mysqli_fetch_assoc($res)) {
                affLivreL($t);
            }

            // Affichage de la pagination
            echo '<div class="pagination" style="text-align: center;">';
            for ($i = 0; $i < $totalLivres; $i += $pagination) {
                $numPage = ($i / $pagination) + 1;
                if ($i == $position) {
                    echo " <strong>$numPage</strong> ";
                } else {
                    echo ' <a href="', basename($_SERVER['PHP_SELF']),
                        '?type=', $recherche['type'],
                        '&quoi=', urlencode($recherche['quoi']),
                        '&p=', $i,
                        '&t=', $totalLivres,
                        '">', $numPage, '</a> ';
                }
            }
            echo '</div>';
        }

        mysqli_free_result($res);
        mysqli_close($bd);
    }
}

/**
 * Affichage d'un livre.
 *
 * @param  array       $livre      tableau associatif les infos sur un livre (liID, liTitre, liPrix, liNbPages, liISBN13, edNom, edWeb, auteurs)
 *
 * @return void
 */
function affLivreL(array $livre) : void {
    // Le nom de l'auteur doit être encodé avec urlencode() avant d'être placé dans une URL, sans être passé auparavant par htmlentities()
    $auteurs = explode('@', $livre['auteurs']);
    unset($livre['auteurs']);
    $livre['edWeb'] = trim($livre['edWeb']);
    $livre = htmlProtegerSorties($livre);
    echo
        '<article class="arRecherche">',
            '<a class="addToCart" href="./panier.php?action=ajouter&id=', htmlspecialchars($livre['liID']), '" title="Ajouter au panier"></a>',
            '<a class="addToWishlist" href="#" title="Ajouter à la liste de cadeaux"></a>',
            '<a href="details.php?article=', $livre['liID'], '" title="Voir détails"><img src="../images/livres/', $livre['liID'], '_mini.jpg" alt="',
            $livre['liTitre'],'"></a>',
            '<h5>', $livre['liTitre'], '</h5>',
            'Écrit par : ';
    $i = 0;
    foreach ($auteurs as $auteur) {
        list($prenom, $nom) = explode('|', $auteur);
        echo $i > 0 ? ', ' : '', '<a href="', basename($_SERVER['PHP_SELF']),'?type=auteur&amp;quoi=', urlencode($nom), '">',
        htmlProtegerSorties($prenom), ' ', htmlProtegerSorties($nom) ,'</a>';
        ++$i;
    }

    echo    '<br>Éditeur : <a class="lienExterne" href="http://', $livre['edWeb'], '" target="_blank">', $livre['edNom'], '</a><br>',
            'Prix : ', $livre['liPrix'], ' &euro;<br>',
            'Pages : ', $livre['liNbPages'], '<br>',
            'ISBN13 : ', $livre['liISBN13'],
        '</article>';
}

