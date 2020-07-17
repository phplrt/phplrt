# Installation

Phplrt can be installed into any PHP application 
using `composer` dependency mananger.

  * [About Composer](https://getcomposer.org/doc/00-intro.md)
  * [Download Composer](https://getcomposer.org/download/) 

## Requirements

  * PHP 7.4+
  * [SPL Extension](https://www.php.net/manual/en/book.spl.php)
  * [PCRE Extension](https://php.net/manual/en/book.pcre.php)
  * [Mbstring Extension](https://www.php.net/manual/en/mbstring.installation.php)


## Installation

Phplrt is available as composer repository and can be 
installed using the following command in a root of your project:

```bash
$ composer require phplrt/phplrt
```

> Note: This command installs the entire project, including dependencies that 
> are **not required** at runtime.

### Runtime Only

However, to eliminate unnecessary dependencies, you can use:

```bash
$ composer require phplrt/runtime
```

...and dependencies for development

```bash
$ composer require phplrt/compiler --dev
```

In order to access phplrt classes make sure to include `vendor/autoload.php` in your file.

```php
<?php

require __DIR__ . '/vendor/autoload.php';
```
