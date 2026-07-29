SHELL := /bin/sh

# supports PHP versions
php81 := php:8.1-cli-alpine
php82 := php:8.2-cli-alpine
php83 := php:8.3-cli-alpine
php84 := php:8.4-cli-alpine
php85 := php:8.5-cli-alpine
php_all := $(php81) $(php82) $(php83) $(php84) $(php85)

# run command php for PHP version enabled in `.env`
docker-run := docker-compose -f docker-compose.yml run -q --rm php
docker-build := docker-compose build -q

# test command
phpunit_params ?=
phpunit-no-coverage := ./vendor/bin/phpunit --no-coverage $(phpunit_params)
phpunit-coverage := ./vendor/bin/phpunit $(phpunit_params)

# analyzer command
php-stan := ./vendor/bin/phpstan -vvv --memory-limit=256M

# code style fixer
php-cs-fixer := ./vendor/bin/php-cs-fixer fix

# clean install composer dependencies
composer-clean-prepare := rm -f composer.lock && rm -rf vendor && composer install -q -n --no-progress


.PHONY: test
test:
	$(docker-run) $(phpunit-no-coverage)

cmd_test_concrete_php := $(docker-run) $(phpunit-no-coverage); $(docker-build);

.PHONY: test-php81
test-php81:
	@$(docker-build) --build-arg PHP_IMAGE=$(php81); \
	$(cmd_test_concrete_php)

.PHONY: test-php82
test-php82:
	@$(docker-build) --build-arg PHP_IMAGE=$(php82); \
	$(cmd_test_concrete_php)

.PHONY: test-php83
test-php83:
	@$(docker-build) --build-arg PHP_IMAGE=$(php83); \
	$(cmd_test_concrete_php)

.PHONY: test-php84
test-php84:
	@$(docker-build) --build-arg PHP_IMAGE=$(php84); \
	$(cmd_test_concrete_php)

.PHONY: test-php85
test-php85:
	@$(docker-build) --build-arg PHP_IMAGE=$(php85); \
	$(cmd_test_concrete_php)

.PHONY: test-cover
test-cover:
	$(docker-run) $(phpunit-coverage)

cmd_test_coverage_concrete_php := $(docker-run) $(phpunit-coverage); $(docker-build);

.PHONY: test-cover-php81
test-cover-php81:
	@$(docker-build) --build-arg PHP_IMAGE=$(php81); \
	$(cmd_test_coverage_concrete_php)

.PHONY: test-cover-php82
test-cover-php82:
	@$(docker-build) --build-arg PHP_IMAGE=$(php82); \
	$(cmd_test_coverage_concrete_php)

.PHONY: test-cover-php83
test-cover-php83:
	@$(docker-build) --build-arg PHP_IMAGE=$(php83); \
	$(cmd_test_coverage_concrete_php)

.PHONY: test-cover-php84
test-cover-php84:
	@$(docker-build) --build-arg PHP_IMAGE=$(php84); \
	$(cmd_test_coverage_concrete_php)

.PHONY: test-cover-php85
test-cover-php85:
	@$(docker-build) --build-arg PHP_IMAGE=$(php85); \
	$(cmd_test_coverage_concrete_php)

.PHONY: stat
stat:
	$(docker-run) $(php-stan)

.PHONY: fix
fix:
	$(docker-run)  $(php-cs-fixer)

.PHONY: install
install:
	$(docker-run) composer i

.PHONY: all
all: fix stat test

.PHONY: test-supports-php
test-supports-php:
	@$(foreach php, $(php_all),\
		$(docker-build) --build-arg PHP_IMAGE=$(php); \
		$(docker-run) sh -c "$(composer-clean-prepare) && vendor/bin/phpunit --no-coverage"; \
	)

 	#build container defined in .env file as PHP_IMAGE
	@$(docker-build); \
	$(docker-run) sh -c "$(composer-clean-prepare)";
