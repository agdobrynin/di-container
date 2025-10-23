SHELL := /bin/sh

.PHONY: test
test:
	@docker-compose -f docker-compose.yml run --rm php ./vendor/bin/phpunit --no-coverage

.PHONY: test-cover
test-cover:
	@docker-compose -f docker-compose.yml run --rm php ./vendor/bin/phpunit

.PHONY: stat
stat:
	@docker-compose -f docker-compose.yml run --rm php vendor/bin/phpstan

.PHONY: fix
fix:
	@docker-compose -f docker-compose.yml run --rm php composer fix

.PHONY: install
install:
	@docker-compose -f docker-compose.yml run --rm php composer i

.PHONY: all
all:
	@docker-compose -f docker-compose.yml run --rm php sh -c "vendor/bin/php-cs-fixer fix && vendor/bin/phpstan && vendor/bin/phpunit --no-coverage"

.PHONY: test-supports-php
test-supports-php:
	@docker-compose build --build-arg PHP_IMAGE=php:8.1-cli-alpine
	@docker-compose -f docker-compose.yml run --rm php ./vendor/bin/phpunit --no-coverage


	@docker-compose build --build-arg PHP_IMAGE=php:8.2-cli-alpine
	@docker-compose -f docker-compose.yml run --rm php ./vendor/bin/phpunit --no-coverage

	@docker-compose build --build-arg PHP_IMAGE=php:8.3-cli-alpine
	@docker-compose -f docker-compose.yml run --rm php ./vendor/bin/phpunit --no-coverage


	@docker-compose build --build-arg PHP_IMAGE=php:8.4-cli-alpine
	@docker-compose -f docker-compose.yml run --rm php ./vendor/bin/phpunit --no-coverage

	@docker-compose build #build container defined in .env file as PHP_IMAGE
