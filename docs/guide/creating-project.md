# Creating Project

This guide tells you how to write your own JSON parser from scratch. Although we have
already decided in advance on the grammar that we want to recognize, by analogy with
it, you can create any other implementations.

Before starting, you should create a new `composer.json` file and add the
necessary dependencies (see [installation](/docs/installation)) to it. As a result,
the file will look something like this:

```json
{
    "name": "app/json-parser",
    "require": {
        "php": ">=8.0",
        "phplrt/runtime": "^3.2"
    },
    "autoload": {
        "psr-4": {
            "JsonParser\\": "src"
        }
    },
    "require-dev": {
        "phplrt/phplrt": "^3.2"
    },
    "minimum-stability": "dev",
    "prefer-stable": true
}
```
