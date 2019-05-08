# Installation

Phplrt can be installed into any PHP application 
using `composer` dependency mananger.

  * [About Composer](https://getcomposer.org/doc/00-intro.md)
  * [Download Composer](https://getcomposer.org/download/) 

## Requirements

  * PHP 7.1+
  * [Spl Extension](https://www.php.net/manual/en/book.spl.php)
  * [Pcre Extension](https://php.net/manual/en/book.pcre.php)
  * [Mbstring Extension](https://www.php.net/manual/en/mbstring.installation.php)
  * [Dom Extension](https://php.net/manual/en/book.dom.php) (optional)


## Installation

Phplrt is available as composer repository and can be 
installed using the following command in a root of your project:

```bash
$ composer require phplrt/phplrt
```

However, to eliminate unnecessary dependencies, you can use:

```bash
$ composer require phplrt/parser
$ composer require phplrt/compiler --dev
```

  * `phplrt/parser` provides execution runtime library.
  * `phplrt/compiler` provides a compiler-compiler library and set of symfony-based commands.


In order to access Phplrt classes make sure to include `vendor/autoload.php` in your file.

```php
<?php

require __DIR__ . '/vendor/autoload.php';
```
