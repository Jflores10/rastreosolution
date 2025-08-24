#!/bin/bash

echo "Initializing MongoDB with data..."

# Import JSON files into MongoDB
mongoimport --host localhost --port 27017 --db dbtrackingsystem --collection users --file /docker-entrypoint-initdb.d/users.json --jsonArray
mongoimport --host localhost --port 27017 --db dbtrackingsystem --collection punto_controls --file /docker-entrypoint-initdb.d/punto_controls.json --jsonArray
mongoimport --host localhost --port 27017 --db dbtrackingsystem --collection punto_rutas --file /docker-entrypoint-initdb.d/punto_rutas.json --jsonArray
mongoimport --host localhost --port 27017 --db dbtrackingsystem --collection rutas --file /docker-entrypoint-initdb.d/rutas.json --jsonArray
mongoimport --host localhost --port 27017 --db dbtrackingsystem --collection tipo_usuarios --file /docker-entrypoint-initdb.d/tipo_usuarios.json --jsonArray
mongoimport --host localhost --port 27017 --db dbtrackingsystem --collection unidads --file /docker-entrypoint-initdb.d/unidads.json --jsonArray
mongoimport --host localhost --port 27017 --db dbtrackingsystem --collection cooperativas --file /docker-entrypoint-initdb.d/cooperativas.json --jsonArray
mongoimport --host localhost --port 27017 --db dbtrackingsystem --collection conductors --file /docker-entrypoint-initdb.d/conductors.json --jsonArray

echo "Data import completed."