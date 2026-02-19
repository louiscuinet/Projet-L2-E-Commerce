<?php

require_once './php/bibli_generale.php';
require_once ('./php/bibli_bookshop.php');

// bufferisation des sorties
ob_start();

// démarrage ou reprise de la session
session_start();

affDebutEnseigneEntete('BookShop | Bienvenue', '.');

affContenuL();

affPiedFin();

// ----------  Fonctions locales au script ----------- //

/**
 *  Affichage du contenu de la page
 *
 * @return void
 */
function affContenuL() : void {
    if (isset($_SESSION['message_panier'])) {
        echo '<p style="color:green; font-weight:bold;">' . $_SESSION['message_panier'] . ' <a href="php/panier.php">Voir le panier</a></p>';
        unset($_SESSION['message_panier']);
    }

    echo
        '<h1>Bienvenue sur BookShop !</h1>',
        '<p>Passez la souris sur le logo et laissez-vous guider pour découvrir les dernières exclusivités de notre site.</p>',
        '<p>Nouveau venu sur BookShop ? Consultez notre <a href="./php/presentation.php">page de présentation</a> !</p>';

    $bd = bdConnect();
    if (!$bd) {
        echo '<p style="color:red;">Erreur de connexion à la base de données.</p>';
        return;
    }

    // Derniers livres ajoutés (exemple, par année ou par ID décroissant)
    $sqlDerniers = "
        SELECT l.liID, l.liTitre, a.auID, a.auPrenom, a.auNom
        FROM livre l
        JOIN aut_livre al ON l.liID = al.al_IDLivre
        JOIN auteur a ON al.al_IDAuteur = a.auID
        ORDER BY l.liID DESC
        LIMIT 20"; // on prend 20 pour récupérer les auteurs, on limitera à 4 apres

    $livres = [];
    $result = bdSendRequest($bd, $sqlDerniers);
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $id = $row['liID'];
            if (!isset($livres[$id])) {
                $livres[$id] = [
                    'id' => $id,
                    'titre' => $row['liTitre'],
                    'auteurs' => []
                ];
            }
            $livres[$id]['auteurs'][] = ['prenom' => $row['auPrenom'], 'nom' => $row['auNom']];
        }
        mysqli_free_result($result);
    }
    // Ne garder que  les 4 livres les plus recents
    $livres = array_slice(array_values($livres), 0, 4);

    affSectionLivresL('Dernières nouveautés', 'Voici les 4 derniers livres ajoutés :', $livres);



// Requête top ventes (plus vendus)
    $sqlTop = "
        SELECT l.liID, l.liTitre, a.auID, a.auPrenom, a.auNom, SUM(cc.ccQuantite) AS total_vendu
        FROM livre l
        JOIN aut_livre al ON l.liID = al.al_IDLivre
        JOIN auteur a ON al.al_IDAuteur = a.auID
        JOIN compo_commande cc ON l.liID = cc.ccIDLivre
        JOIN commande co ON cc.ccIDCommande = co.coID
        GROUP BY l.liID, l.liTitre, a.auID, a.auPrenom, a.auNom
        ORDER BY total_vendu DESC
        LIMIT 20";

    $topLivres = [];
    $result = bdSendRequest($bd, $sqlTop);
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $id = $row['liID'];
            if (!isset($topLivres[$id])) {
                $topLivres[$id] = [
                    'id' => $id,
                    'titre' => $row['liTitre'],
                    'auteurs' => [],
                    'total_vendu' => $row['total_vendu']
                ];
            }
            $topLivres[$id]['auteurs'][] = ['prenom' => $row['auPrenom'], 'nom' => $row['auNom']];
        }
        mysqli_free_result($result);
    }
    $topLivres = array_slice(array_values($topLivres), 0, 4);

    affSectionLivresL('Top des ventes', 'Voici les 4 livres les plus vendus de notre boutique :', $topLivres);

    mysqli_close($bd);
}

/**
 * Affichage d'une section de livres
 *
 * @param  string  $h2         titre de la section (contenu de l'élément h2)
 * @param  string  $p          contenu de l'élément p
 * @param  array   $livres     tableau contenant un élément pour chaque livre (tableau associatif avec id, auteurs(nom, prenom), titre)
 *
 * @return void
 */
function affSectionLivresL(string $h2, string $p, array $livres): void {
    echo
        '<section>',
            '<h2>', htmlspecialchars($h2), '</h2>',
            '<p>', htmlspecialchars($p), '</p>';

    foreach ($livres as $livre) {
        echo
            '<figure>',
                '<a class="addToCart" href="php/panier.php?action=ajouter&id=', htmlspecialchars($livre['id']), '" title="Ajouter au panier"></a>',
                '<a class="addToWishlist" href="#" title="Ajouter à la liste de cadeaux"></a>',
                '<a href="php/details.php?article=', htmlspecialchars($livre['id']), '" title="Voir détails"><img src="./images/livres/',
                htmlspecialchars($livre['id']), '_mini.jpg" alt="', htmlspecialchars($livre['titre']), '"></a>',
                '<figcaption>';
        $i = 0;
        foreach ($livre['auteurs'] as $auteur) {
            if ($i > 0) {
                echo ', ';
            }
            ++$i;
            echo    '<a title="Rechercher l\'auteur" href="php/recherche.php?type=auteur&amp;quoi=', urlencode($auteur['nom']), '">',
                    mb_substr($auteur['prenom'], 0, 1, 'UTF-8'), '. ', htmlspecialchars($auteur['nom']), '</a>';
        }
        echo        '<br>',
                    '<strong>', htmlspecialchars($livre['titre']), '</strong>',
                '</figcaption>',
            '</figure> ';
    }
    echo
        '</section>';
}
