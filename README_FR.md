# EcoGarden

EcoGarden est une API permettant de récupérer des conseils de jardinage en fonction du mois choisi ou de la saison en cours.
Elle est également connecté à une API de météo pour récupérer les données de la météo de la ville choisie ou de la ville de l'utilisateur

## Prérequis

* **Docker** 
* **Make**

## Cloner le projet

## Installation

Suivez ces étapes pour mettre en place l'environnement de développement local :

1. Configurer le fichier .env.docker avec les ports que vous souhaitez utiliser pour votre projet

2. Installer le projet avec Make
   * ```Make up```: Démarre le projet dans un docker suivant les configurations du fichier env .env.docker
   * ```Make down``` : Arrête les containers

3. Installez les dépendances :

   ```docker-compose --env-file .env.docker exec php composer install```

    ou

   Autoriser les executables ./symfony et ./symfony-composer et ```./symfony-composer install```
4. Remplir le .env ou copier les valeurs dans un .env.dev avec les valeurs choisies dans .env.docker
5. Créer la base de données et ses données initiales :

    ```./symfony doctrine:migrations:migrate```
6. Générer les clés privés et public pour JWT dans le dossier :
   * config/jwt/private.pem
   * config/jwt/public.pem
   
     ( ```./symfony lexik:jwt:generate-keypair``` )
7. Optionnel : Modifier le Factory AdministratorFactory pour utiliser des données personnalisé pour le compte admin
8. Générer les fixtures : ```./symfony doctrine:fixtures:load```

## Utilisation

POSTMAN : 
    Utilisez localhost:{PORT} -- *{PORT} = Port configuré dans le .env.docker*

* Executables : 
    * ```./symfony``` : Raccourci pour la commande ```php bin/console``` dans le container php
    * ```./symfony-composer``` : Raccourci pour la commande ```composer``` dans le container php

