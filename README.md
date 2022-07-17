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
