SYP-LXC Backend
========================

[![Build Status](https://travis-ci.org/LexicForLXD/Backend.svg?branch=master)](https://travis-ci.org/LexicForLXD/Backend)
[![FOSSA Status](https://app.fossa.io/api/projects/git%2Bgithub.com%2FLexicForLXD%2FBackend.svg?type=shield)](https://app.fossa.io/projects/git%2Bgithub.com%2FLexicForLXD%2FBackend?ref=badge_shield)

[![coverage report](https://git.janrtr.de/syp-lxc/Backend/badges/master/coverage.svg)](https://git.janrtr.de/syp-lxc/Backend/commits/master)
# Requirements
- see requirements [here](docs/REQUIREMENTS.md)

# Installation from Source

### Resolve dependencies and set parameters

```
composer install
```

### Create Database schema

```php
php bin/console doctrine:schema:update --force
```

### Test password grant client erzeugen
```php
php bin/console doctrine:fixtures:load
```

### User erzeugen
```php
php bin/console app:create-user
```

# Installation via Docker
- see prod environment documentation [here](docs/DOCKER.md)
- see backend development environment documentation [here](docs/DOCKER_DEV.md)

# Documentation
### Create up to date Swagger documentation
```php
./vendor/bin/swagger -e vendor
```
### Hosted swagger docs
[here](https://lexicforlxd.github.io/Backend/?url=https://raw.githubusercontent.com/LexicForLXD/Backend/gh-pages/openapi.json)


## License
[![FOSSA Status](https://app.fossa.io/api/projects/git%2Bgithub.com%2FLexicForLXD%2FBackend.svg?type=large)](https://app.fossa.io/projects/git%2Bgithub.com%2FLexicForLXD%2FBackend?ref=badge_large)