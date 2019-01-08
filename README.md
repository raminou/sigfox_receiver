# ST Connect Backend

Collect some data from the ST-Connect system.

## Structure

 - css/: contains the css files
 - includes/: contains some PHP functions in common with few files
 - js/: contains some the JS files
 - *scripts/: contains the script to deploy the application
 - index.php: file which matches with the received data and redirect to the graph
 - display.php: file which contains the display of the graph
 - api.php: file which contains the API functions
 - *README.md: Readme

(*) BE CAREFUL THOSE DIRECTORIES/FILES SHOULD NOT BE DOWNLOADABLE ! (MAKE SURE TO PUT THE DATABASE OUTSIDE OF THE PATH SERVER, OR PROTECTING IT WITH RIGHTS)

## Deployement

To deploy the application, you have to:

 - Clone the repository to your server path.
 - Create a sqlite database with the structure gave in script/structure.sql (Be careful, the database should not be accessible from an attacker !)
 - Put the path of the database in the includes/configuration.php file.
 - Delete the files that should not be accessible from an attacker such as the directory scripts/, the README.md file and the repository data (.git/ and .gitignore or whatever you use)

## Tested on

Working on PHP 7.2 but should work on PHP 5.