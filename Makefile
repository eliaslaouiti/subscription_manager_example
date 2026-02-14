test_application:
	php bin/console doctrine:schema:update --force --env=test
	composer test:application --testsuite=application

test_unit:
	composer test:application --testsuite=unit
