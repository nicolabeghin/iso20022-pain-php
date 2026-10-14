# All targets run inside Docker so no local PHP is required.
IMAGE ?= iso20022-pain-php-dev
DOCKER_RUN = docker run --rm -v "$(CURDIR)":/app -w /app $(IMAGE)

.PHONY: help docker-build install test test-filter phpstan cs cs-fix check shell clean

help: ## Show this help
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[36m%-14s\033[0m %s\n", $$1, $$2}'

docker-build: ## Build the dev/test Docker image (run once, or after Dockerfile changes)
	docker build -t $(IMAGE) .

install: ## composer install inside Docker
	$(DOCKER_RUN) composer install --no-interaction --prefer-dist

update: ## composer update inside Docker
	$(DOCKER_RUN) composer update --no-interaction --prefer-dist

test: ## Run the PHPUnit suite
	$(DOCKER_RUN) vendor/bin/phpunit

test-filter: ## Run a subset: make test-filter FILTER=CreditTransferV09Test
	$(DOCKER_RUN) vendor/bin/phpunit --filter "$(FILTER)"

phpstan: ## Static analysis (level 8)
	$(DOCKER_RUN) vendor/bin/phpstan analyse --no-progress --memory-limit=512M

cs: ## PSR-12 code style check
	$(DOCKER_RUN) vendor/bin/phpcs

cs-fix: ## PSR-12 auto-fix
	$(DOCKER_RUN) vendor/bin/phpcbf || true

check: cs phpstan test ## Code style + static analysis + tests

shell: ## Interactive shell in the dev container
	docker run --rm -it -v "$(CURDIR)":/app -w /app $(IMAGE) sh

clean: ## Remove vendor and caches
	rm -rf vendor .phpunit.cache
