SHELL := /bin/sh

test:
	@docker-compose -f docker-compose.yml run --rm php ./vendor/bin/phpunit --no-coverage

stat:
	@docker-compose -f docker-compose.yml run --rm php vendor/bin/phan

fix:
	@docker-compose -f docker-compose.yml run --rm php composer fix

install:
	@docker-compose -f docker-compose.yml run --rm php composer i

.PHONY: all
all: fix stat test
