NETWORK=csr-network
DB_CONTAINER=csr-membership-db
APP_CONTAINER=csr-membership-app

build:
	sudo docker build -t $(APP_CONTAINER) .

network:
	sudo docker network create $(NETWORK) || true

db: network
	sudo docker run -d --name $(DB_CONTAINER) \
		--network $(NETWORK) \
		-p 3307:3306 \
		-e MYSQL_ROOT_PASSWORD=root \
		-e MYSQL_DATABASE=csr_membership \
		-e MYSQL_USER=user \
		-e MYSQL_PASSWORD=password \
		mysql:8.0

run: network
	sudo docker rm -f $(APP_CONTAINER) || true
	sudo docker run -d --name $(APP_CONTAINER) \
		--network $(NETWORK) \
		-p 8000:80 \
		-v $(shell pwd):/var/www/html \
		$(APP_CONTAINER)

setup-test-db:
	@sleep 10
	sudo docker exec $(DB_CONTAINER) mysql -uroot -proot -e "CREATE DATABASE IF NOT EXISTS csr_membership_test; GRANT ALL PRIVILEGES ON csr_membership_test.* TO 'user'@'%'; FLUSH PRIVILEGES;"
	@sleep 3
	sudo docker exec -e APP_ENV=test $(APP_CONTAINER) php bin/console doctrine:database:create --if-not-exists || true
	sudo docker exec -e APP_ENV=test $(APP_CONTAINER) php bin/console doctrine:migrations:migrate --no-interaction

init: clean build db run setup-test-db migrate
	@echo "✓ Environment initialized successfully"

migrate:
	sudo docker exec $(APP_CONTAINER) php bin/console doctrine:migrations:migrate --no-interaction

migrate-test:
	sudo docker exec -e APP_ENV=test $(APP_CONTAINER) php bin/console doctrine:migrations:migrate --no-interaction

stop:
	sudo docker stop $(APP_CONTAINER) $(DB_CONTAINER) || true

clean: stop
	sudo docker rm $(APP_CONTAINER) $(DB_CONTAINER) || true
	sudo docker network rm $(NETWORK) || true

logs:
	sudo docker logs -f $(APP_CONTAINER)

test:
	sudo docker exec $(APP_CONTAINER) php bin/phpunit
