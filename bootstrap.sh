#!/bin/bash
echo "Composer update..."
composer update
echo "Npm install..."
cd ./web
npm install