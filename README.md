# Composer Scaffolder

Interactive project scaffolding for Composer.

This plugin adds a `composer scaffold` command that generates a new Composer
project from a specially designed **scaffold-template** package.

## Template package vs generated payload

A **scaffold-template package** is a distribution package that contains:

* Its own outer `composer.json` describing the template.
* A `scaffold.json` manifest that declares questions and file operations.
* A payload directory with the stub files that become the generated project.

The outer `composer.json`, manifest and other template implementation files are
**not** copied into the generated project unless explicitly declared.

For example, a template package might look like:

```text
example-template/
├── composer.json
├── scaffold.json
└── template/
    ├── composer.json.stub
    ├── README.md.stub
    └── src/
        └── Application.php.stub
```

Running `composer scaffold example-template ./generated-project` produces only
the contents of `template/` in `./generated-project`.

## Current limitation: local templates only

The first vertical slice only supports **local paths** to template packages.
Remote Packagist resolution and automatic dependency installation are
deliberately deferred.

## Installation as a Composer plugin

Require the plugin in your project:

```shell
composer require jascha030/composer-scaffolder
```

Because the package is a Composer plugin, you must allow it:

```shell
composer config allow-plugins.jascha030/composer-scaffolder true
```

### Loading from a local path repository

To develop the plugin without publishing it, create a test project and add a
path repository:

```shell
mkdir scaffold-consumer && cd scaffold-consumer
composer init --name=acme/consumer --no-interaction
composer config minimum-stability dev
composer config prefer-stable true
composer config repositories.local path /path/to/composer-scaffolder
composer config allow-plugins.jascha030/composer-scaffolder true
composer require jascha030/composer-scaffolder
```

The plugin command will then be available in `scaffold-consumer`.

## Template package format

A valid template package is identified by **both** its Composer package type and
its namespaced `extra` metadata.

```json
{
    "name": "acme/example-template",
    "description": "Example scaffold template",
    "type": "jascha030-scaffold-template",
    "license": "MIT",
    "extra": {
        "jascha030-scaffold": {
            "schema": 1,
            "manifest": "scaffold.json",
            "payload": "template/"
        }
    }
}
```

Requirements:

* `type` must be exactly `jascha030-scaffold-template`.
* `extra.jascha030-scaffold` must be an object.
* `schema` must be the integer `1`.
* `manifest` and `payload` must be non-empty relative paths without `..` or
  symlinks that escape the template package.
* The manifest file and payload directory must exist.

## Manifest version 1

`scaffold.json` uses schema version `1`.

```json
{
    "schema": 1,
    "questions": [
        {
            "key": "package.name",
            "type": "text",
            "prompt": "Composer package name",
            "required": true,
            "validation": {
                "pattern": "^[a-z0-9_.-]+/[a-z0-9_.-]+$"
            }
        },
        {
            "key": "project.namespace",
            "type": "text",
            "prompt": "Root PHP namespace",
            "default": "App"
        }
    ],
    "files": [
        {
            "source": "composer.json.stub",
            "target": "composer.json",
            "mode": "render"
        },
        {
            "source": "README.md.stub",
            "target": "README.md",
            "mode": "render"
        },
        {
            "source": "src/Application.php.stub",
            "target": "src/Application.php",
            "mode": "render"
        }
    ]
}
```

Supported features:

* `schema: 1`
* Text questions (`"type": "text"`)
* String defaults
* Required questions
* Optional regular-expression validation
* `render` and `copy` file modes

When rendering files whose target path ends with `.json`, token values are
JSON-escaped automatically so values such as `Acme\GeneratedExample` produce
valid JSON.

### Token syntax

Stub files use simple tokens:

```text
{{ package.name }}
{{ project.namespace }}
```

Only files declared with `"mode": "render"` are processed. Files declared with
`"mode": "copy"` are copied byte-for-byte.

## The `composer scaffold` command

```text
composer scaffold <template> <directory>
```

Arguments:

* `template`: local path to a scaffold-template package.
* `directory`: destination directory for the generated project.

Options:

* `--set=key=value`: predefined answer; repeatable.
* `--dry-run`: print the operation plan without writing files.
* `--force`: allow replacing an existing empty destination.
* `--no-install`: accepted for forward compatibility; dependency installation is
  not implemented yet.
* `--no-interaction`: skip interactive prompts; all required answers must be
  predefined or have defaults.

### Noninteractive example

```shell
composer scaffold ./tests/Fixtures/Templates/minimal ./build/example \
    --set=package.name=acme/generated-example \
    --set=project.namespace='Acme\GeneratedExample' \
    --no-interaction \
    --no-install
```

This produces:

```text
build/example/
├── composer.json
├── README.md
└── src/
    └── Application.php
```

## Development setup

Install dependencies:

```shell
composer install
```

Run validation:

```shell
composer validate --strict
```

Run tests:

```shell
composer test
```

Run static analysis:

```shell
composer analyze
```

Format code:

```shell
composer format
```

## Intentionally deferred features

The following items are left for future iterations:

* Remote Packagist package resolution.
* Automatic `composer install` in the generated project.
* Choice, multiselect and hidden questions.
* Template lifecycle hooks and arbitrary executable configuration.
* Shell commands defined by templates.
* Complex condition expressions.
* A separate core package or monorepo layout.

## License

This package is open-sourced software licensed under the [MIT License](LICENSE.md).
