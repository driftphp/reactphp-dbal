#!/bin/bash

while ! nc -z localhost 3306;
do
  echo "Waiting for mysql. Slepping";
  sleep 1;
done;
echo "Connected to mysql!";

while ! nc -z localhost 5432;
do
  echo "Waiting for Postgresql. Slepping";
  sleep 1;
done;
echo "Connected to Postgresql!";

php vendor/bin/phpunit