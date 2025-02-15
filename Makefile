SHELL := /bin/sh

test:
	@docker-compose -f docker-compose.yml run --rm php ./vendor/bin/phpunit --no-coverage

test-cover:
	@docker-compose -f docker-compose.yml run --rm php ./vendor/bin/phpunit

stat:
	@docker-compose -f docker-compose.yml run --rm php vendor/bin/phpstan

fix:
	@docker-compose -f docker-compose.yml run --rm php composer fix

install:
	@docker-compose -f docker-compose.yml run --rm php composer i

.PHONY: all
all: fix stat test
