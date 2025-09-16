# application-domain-clarus

Project for SWE 4713 Application Domain

## Github Setup

Install VS Code - https://code.visualstudio.com/download
Install PHP Locally - https://www.php.net/downloads.php

Within your project directory of choice run the following commands
git clone https://github.com/kyaagoggins/application-domain-clarus.git

In VS Code -> File (top left corner) and open the application-domain-clarus folder.
Install extensions for PHP as recommended by VS Code

git fetch - This command resyncs your local folder with the github repo, this is necessary for all changes and syncing.

Run the following to select a new branch.
git checkout origin <branch-name>

### do not make changes on the main branch unless necessary!

Feel free to create your own branch in the github and use this local branch.

To post or commit changes to the repo from your local folder
git commit
git commit -m "Your commit message"

## Local Environment

Run the following command:
php -S localhost:8000

This will start the development server on http://localhost:8000

direct to ex. http://localhost:8000/testStart.php to direct to your specific file.

## General Need to Knows

For all files, it should end with .php
Include header.php and footer.php on every file to be able to use the attached libraries.
