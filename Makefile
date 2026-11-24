SHELL := /bin/sh

docker-run := docker-compose -f docker-compose.yml run -P -q --rm --remove-orphans node
docker-build := docker-compose build
BUILD_PARAMS ?=

.DEFAULT_GOAL := no_default

define HELP_TEXT
******************************
*                            *
*   Run make with target:    *
*     make build             *
*     make install           *
*     make sh                *
*     make docs-dev          *
*     make docs-preview      *
*     make docs-build        *
*                            *
******************************
endef

export HELP_TEXT
no_default:
	@echo "$$HELP_TEXT"
	@exit 1

.PHONY: build
build:
	$(docker-build) $(BUILD_PARAMS)

.PHONY: install
install:
	$(docker-run) npm install

.PHONY: sh
sh:
	$(docker-run) sh

.PHONY: docs-dev
docs-dev:
	$(docker-run) npm run docs:dev-host

.PHONY: docs-preview
docs-preview:
	$(docker-run) npm run docs:preview

.PHONY: docs-build
docs-build:
	$(docker-run) npm run docs:build