# EcoGarden

EcoGarden is an API for retrieving gardening tips based on the chosen month or the current season.
It is also connected to a weather API to retrieve weather data for the chosen city or the user's city.

## Prerequisites

* **Docker** 
* **Make**

## Clone the project

## Installation

Follow these steps to set up the local development environment:

1. Configure the .env.docker file with the ports you want to use for your project

2. Install the project with Make
   * ```Make up```: Starts the project in a docker according to the configurations of the .env.docker env file
   * ```Make down``` : Stops the containers

3. Install the dependencies:

   ```docker-compose --env-file .env.docker exec php composer install```

    or

   Allow the executables ./symfony and ./symfony-composer and ```./symfony-composer install```
4. Fill the .env or copy the values into a .env.dev
5. Create the database and its initial data:

    ```./symfony doctrine:migrations:migrate```
6. Generate the private and public keys for JWT in the folder:
   * config/jwt/private.pem
   * config/jwt/public.pem
   
     ( ```./symfony lexik:jwt:generate-keypair``` )
7. Optional: Modify the AdministratorFactory Factory to use custom data for the admin account
8. Generate the fixtures: ```./symfony doctrine:fixtures:load```

## USAGE

POSTMAN : 
    Use localhost:{PORT} -- *{PORT} = Port configuré dans le .env.docker*

* Executables : 
    * ```./symfony``` : Shortcut for the ```php bin/console``` command in the php container
    * ```./symfony-composer``` : Shortcut for the ```composer``` command in the php container

