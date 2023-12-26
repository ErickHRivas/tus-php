#!/bin/sh
echo "------------------ Checking for Composer ------------------"
composer -n install
composer -n update

echo "------------------ Starting php ------------------"
exec "php-fpm"