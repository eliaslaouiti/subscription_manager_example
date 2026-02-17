install:
	composer install

start:
	symfony serve

db:
	php bin/console doctrine:migrations:migrate --no-interaction

db-test:
	php bin/console doctrine:schema:update --force --env=test

test: db-test
	./vendor/bin/phpunit --colors=always

test-unit:
	./vendor/bin/phpunit --colors=always --testsuite=unit

test-app: db-test
	./vendor/bin/phpunit --colors=always --testsuite=application

lint:
	composer analyse
	composer cs-check

cs-fix:
	composer cs-fix

fixtures:
	composer load-fixtures
