## Project Setup

- Open terminal

```
cd back-end/
composer install
./bin/console doctrine:database:create
./bin/console doctrine:schema:update --force
./bin/console rabbitmq:setup-fabric
symfony serve --port=8001
```

- Open new terminal tab

```
cd back-end/
./bin/console rabbitmq:consumer messaging
```

- Open new terminal tab

```
cd front-end/
composer install
./bin/console rabbitmq:setup-fabric
symfony serve --port=8000
```

- Open new terminal tab

```
cd front-end/
./bin/console rabbitmq:consumer messaging
```

- Open new terminal tab

```
# create a book
curl -H "X-API-KEY: 0123456789" -X POST -d '{"title":"The Parent Agency","author":"David Baddiel","pages":50,"releaseDate":"28-09-2004"}' https://localhost:8000/books

# get book list
curl https://localhost:8000/books
```
