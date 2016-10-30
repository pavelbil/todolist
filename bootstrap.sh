#!/bin/bash
echo "Composer update..."
composer update
echo "Npm install..."
cd ./web
npm install
cp ./App/config/config.php.dist ./App/config/config.php