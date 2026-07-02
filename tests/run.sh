#!/usr/bin/env sh
# Run the Codeception suite. Boots the built-in PHP server only if one isn't
# already listening (so it also works alongside `npm start`'s nginx/php-fpm),
# then runs Codeception against it. Used by `npm test`, `composer test`, the
# Docker image and CI so every path runs the tests identically.
#
# `Request::getMethod()` reads `$_SERVER['REQUEST_METHOD']`, so the plain
# built-in server needs no router shim. `register_argc_argv=On` is required by
# the Codeception CLI regardless of the host php.ini; the error_reporting mask
# keeps framework deprecations (PHP 8.x) from cluttering the output.
set -e

PORT="${TEST_PORT:-8081}"
HOST="127.0.0.1"
PROBE="http://$HOST:$PORT/?action=files&source=test"

# The test server is always local — never route it through an HTTP proxy
# (some dev machines have HTTP_PROXY set, which would break the localhost calls).
export NO_PROXY="127.0.0.1,localhost,${NO_PROXY:-}"
export no_proxy="127.0.0.1,localhost,${no_proxy:-}"

if ! curl -sf "$PROBE" >/dev/null 2>&1; then
	php -d display_errors=Off -S "$HOST:$PORT" tests/index-test.php \
		>/tmp/jodit-conn-server.log 2>&1 &
	SERVER_PID=$!
	# shellcheck disable=SC2064
	trap "kill $SERVER_PID 2>/dev/null || true" EXIT

	i=0
	while [ "$i" -lt 40 ]; do
		if curl -sf "$PROBE" >/dev/null 2>&1; then
			break
		fi
		i=$((i + 1))
		sleep 0.25
	done
fi

php -d register_argc_argv=On \
	-d error_reporting="E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED" \
	vendor/bin/codecept run "$@"
