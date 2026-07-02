.PHONY: test test-docker install

# Run the API test suite against a local PHP (needs php + ext-gd on the host).
test:
	sh tests/run.sh

# Run the API test suite in Docker — no PHP needed on the host.
test-docker:
	docker compose run --rm test

install:
	composer install
