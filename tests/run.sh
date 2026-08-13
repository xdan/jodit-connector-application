#!/usr/bin/env sh
# Run the Codeception suite. Boots the built-in PHP servers only if they aren't
# already listening (so it also works alongside `npm start`'s nginx/php-fpm),
# then runs Codeception against them. Used by `npm test`, `composer test`, the
# Docker image and CI so every path runs the tests identically.
#
# Two servers are started:
#   * the connector itself (TEST_PORT, default 8081)
#   * a plain static server over tests/fixtures (TEST_FIXTURE_PORT, default
#     8082) that stands in for the "remote site" of the download and PDF/DOCX
#     tests. It has to be a second server because the built-in one is
#     single-threaded and cannot answer a request made from within a request.
#
# `Request::getMethod()` reads `$_SERVER['REQUEST_METHOD']`, so the plain
# built-in server needs no router shim. `register_argc_argv=On` is required by
# the Codeception CLI regardless of the host php.ini; the error_reporting mask
# keeps framework deprecations (PHP 8.x) from cluttering the output.
set -e

PORT="${TEST_PORT:-8081}"
FIXTURE_PORT="${TEST_FIXTURE_PORT:-8082}"
HOST="127.0.0.1"
PROBE="http://$HOST:$PORT/?action=files&source=test"
FIXTURE_PROBE="http://$HOST:$FIXTURE_PORT/artio.jpg"

SERVER_PID=""
FIXTURE_PID=""

cleanup() {
	[ -n "$SERVER_PID" ] && kill "$SERVER_PID" 2>/dev/null || true
	[ -n "$FIXTURE_PID" ] && kill "$FIXTURE_PID" 2>/dev/null || true
}
trap cleanup EXIT

# The test servers are always local — never route them through an HTTP proxy
# (some dev machines have HTTP_PROXY set, which would break the localhost calls).
export NO_PROXY="127.0.0.1,localhost,${NO_PROXY:-}"
export no_proxy="127.0.0.1,localhost,${no_proxy:-}"

wait_for() {
	i=0
	while [ "$i" -lt 40 ]; do
		if curl -sf "$1" >/dev/null 2>&1; then
			return 0
		fi
		i=$((i + 1))
		sleep 0.25
	done
	return 1
}

if ! curl -sf "$PROBE" >/dev/null 2>&1; then
	php -d display_errors=Off -S "$HOST:$PORT" tests/index-test.php \
		>/tmp/jodit-conn-server.log 2>&1 &
	SERVER_PID=$!
	wait_for "$PROBE" || true
fi

if ! curl -sf "$FIXTURE_PROBE" >/dev/null 2>&1; then
	php -d display_errors=Off -S "$HOST:$FIXTURE_PORT" -t tests/fixtures \
		>/tmp/jodit-conn-fixtures.log 2>&1 &
	FIXTURE_PID=$!
	wait_for "$FIXTURE_PROBE" || true
fi

php -d register_argc_argv=On \
	-d error_reporting="E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED" \
	vendor/bin/codecept run "$@"
