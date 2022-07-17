# composer.yaml support for Composer

This Composer plugin will make your composer project use `composer.yaml` as it's
Composer config instead of `composer.json`. It does so fully transparently
without the need to run additional commands.

`composer.json` is still required, (to bootstrap this plugin, among other 
things) however it is now generated automatically when a change in
`composer.yaml` is detected. Think of this similarly to how `composer.lock` 
is generated.

## Installation

```bash
composer require empaphy/composer-yaml 
```

## Usage

Just use composer as you usually would! The first time you run any Composer CLI
command after installing this plugin, it will generate a composer.yaml based on
your existing composer.json.

Just remember to modify composer.yaml from now on, and to _not_ modify
composer.json, since any changes in that file will be overridden.

### YAML vs JSON

YAML allows for many features not available in JSON, like... Comments! For 
example, if you take a typical composer.json like this:
```json
{
    "name":              "empaphy/foo",
    "description":       "Foo library for PHP",
    "license":           "MIT",
    "minimum-stability": "stable",
    "prefer-stable":     true,

    "require": {
        "php": ">=7.4",

        "symfony/config":         "~5.4.0",
        "symfony/console":        "~5.4.0",
        "symfony/filesystem":     "~5.4.0",
        "symfony/process":        "~5.4.0",
        "symfony/yaml":           "~5.4.0",
        "yogarine/composer-yaml": "~5.4.0"
    },

    "require-dev": {
        "phpunit/phpunit": "^9.0",
        "phploc/phploc":   "^7.0",
        "phpstan/phpstan": "^1.0"
    },

    "autoload": {
        "psr-4": { "Empaphy\\Foo\\": "src/" }
    },
    
    "config": {
        "platform": {
            "php": "8.0.20"
        }
    },

    "scripts": {
        "pre-install-cmd": [
            "if [ -d .git -o -f .git -a -d ../.git ]; then git submodule update; fi"
        ]
    }
}
```

You can turn it into something like this:
```yaml
##
# Composer configuration for the Foo package.
#
# Use composer to install dependencies for this package:
#
#     composer install --no-dev --optimize-autoloader
#

name:        empaphy/foo            # The name of the package.
description: Foo library for PHP    # A short description of the package.
license:     MIT                    # The license of the package.

# This defines the default behavior for filtering packages by stability.
minimum-stability: stable

# Prefer more stable packages over unstable ones when finding compatible stable
# packages is possible.
#
# If you require a dev version or only alphas are available for a package, those
# will still be selected granted that the minimum-stability allows for it.
prefer-stable: true

# Map of packages required by this package.
#
# The package will not be installed unless those requirements can be met.
require:
    php:                    '>=7.4'  # We depend on property types. 

    # Symfony dependencies:
    symfony/config:         &symfony-version '~5.4.0'
    symfony/console:        *symfony-version
    symfony/filesystem:     *symfony-version
    symfony/process:        *symfony-version
    symfony/yaml:           *symfony-version

    yogarine/composer-yaml: 'dev-main'  # Adds support for this file. :-)

require-dev:
    phpunit/phpunit: '^9.0'
    phploc/phploc:   '^7.0'
    phpstan/phpstan: '^1.0'

autoload:
    psr-4: [ Empaphy\Foo: src/ ]
    
config:
    platform:
        php: '8.0.20'  # We currently run 8.0.20 on all our production servers,
                       # so ensure we're forward-compatible with PHP 8.
    allow-plugins:
        yogarine/composer-yaml: true  # Required to for composer.yaml support.

scripts:
    # Occurs before the `install` command is executed with a lock file present.
    pre-install-cmd:
      - |  # Ensure submodules are updated.
        if [ -d .git -o -f .git -a -d ../.git ]; then
          git submodule update
        fi
```

As you can see, YAML allows for a lot more expressive Composer configuration.
  - Comments allow you to clarify your configuration.
  - Not needing to use quotes makes the file much more readable.
  - Not requiring trailing comma's reduces the chance at syntax errors.
  - 

## Known Issues / Roadmap

  - **Using Composer CLI commands** that would previously modify the
    composer.json will still work and properly modify the `composer.yaml`, _but_
    they **will remove any custom formatting changes and comments** you have
    made to your `composer.yaml` file.
    
    I plan to fix this in a future version, but it involves writing a custom
    YAML Manipulator, so it might take a while. For now, just refrain from
    using the CLI commands to modify the composer config. I mean, why even
    would you, when you can now use fancy YAML markup to pimp up your
    composer.yaml? ;-)
