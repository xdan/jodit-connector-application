# Jodit backend

Default application for project [Jodit Application](https://github.com/xdan/jodit-connectors)

* [Read more about Jodit](https://xdsoft.net/jodit/)
* [Jodit PRO](https://xdsoft.net/jodit/pro/)
* [Chagelog](./CHANGELOG.md)


> This module is a framework for building a Jodit backend.
Its installation is best done via https://github.com/xdan/jodit-connectors

### Run tests

Install full requires including dev

```bash
composer install
```

Start PHP server
```bash
npm start
```
or
```bash
cd ./docker && docker-compose up
```

Run tests
```bash
npm test
```

But before add your internal ip `tests/index-test.php` and `tests/TestApplication.php`

Run only API tests
```bash
./vendor/bin/codecept run api
./vendor/bin/codecept run api getFilesOnlyImagesCept
```

Run only unit tests
```bash
./vendor/bin/codecept run unit
```
