# Projet BookShop - L3 Informatique

## Présentation
Ce projet est une librairie en ligne développée en PHP/MySQL. Il permet de parcourir un catalogue, d'effectuer des recherches par auteur ou titre, et de gérer un panier.

## Note technique : Migration de l'environnement
Le projet a été migré d'un environnement universitaire vers un environnement local (Ubuntu/WSL). 

**Important :** Suite à cette migration, la base de données originale n'était pas exportable. Je l'ai donc reconstruite structurellement du mieux que je peux à partir du code source PHP :
- Le schéma relationnel (`livre`, `auteur`, `editeur`, `aut_livre`) est 100% conforme.
- Un jeu de données de test (8 livres) a été injecté pour valider le fonctionnement des fonctionnalités (Dernières nouveautés, Top Ventes, Recherche).

## Installation
1. Importer le fichier `database.sql` dans MySQL.
2. Configurer les accès dans `php/bibli_bookshop.php`.
3. Lancer le serveur : `php -S localhost:8000`.
