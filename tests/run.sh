#!/usr/bin/env sh
# Start the built-in PHP server and run the Codeception API suite against it.
# Used locally, in Docker, and in CI so all three run the tests identically.
#
# `Request::getMethod()` reads `$_SERVER['REQUEST_METHOD']`, so the plain
# built-in server works with no router shim. `display_errors=Off` keeps PHP
# notices from leaking into JSON responses; the CLI needs `register_argc_argv`.
set -e

PORT="${TEST_PORT:-8081}"
HOST="127.0.0.1"

php -d display_errors=Off -S "$HOST:$PORT" tests/index-test.php >/tmp/jodit-conn-server.log 2>&1 &
SERVER_PID=$!
# shellcheck disable=SC2064
trap "kill $SERVER_PID 2>/dev/null || true" EXIT

# Wait until the server answers before running the suite.
i=0
while [ "$i" -lt 40 ]; do
	if curl -sf "http://$HOST:$PORT/?action=files&source=test" >/dev/null 2>&1; then
		break
	fi
	i=$((i + 1))
	sleep 0.25
done

php -d register_argc_argv=On vendor/bin/codecept run api "$@"
