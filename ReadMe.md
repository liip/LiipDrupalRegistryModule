# Liip Drupal Registry Module
This module provides an API to store key/value pairs of information.
The Idea came when we had to change the persistence layer of how custom data is stored to be available to the system later.
We started with the standard Drupal 7 way to cache data (see: [http://api.drupal.org/api/drupal/includes!bootstrap.inc/function/variable_set/7])
by implementing a facade to the `variable_get()`, `variable_set()`, `variable_del()` functions. This implementation is
provided as the default and example implementation for the registry.

##Current Travis Status

[![Build Status](https://secure.travis-ci.org/liip/drupalregistrymodule.png?branch=master)](http://travis-ci.org/liip/drupalregistrymodule)


## Installation
The source is now PSR-0 compatible. There is no specific installation routine to be followed. Just clone or checkout the source into to your project
and use it.
In case you don't use a [PSR-0](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-0.md) compatible autoloader, you only have to add the `bootstrap.php` into your bootstrap or
autoloader.

###Composer
Add the following lines to your `composer.json` file and update your project's composer installation.

```json
{
    "require": {
       "liip/liipdrupalregistrymodule": "dev-master"
    }
}
```

This composer configuration will checkout the 'cutting eadge' version ('dev-master') of the project. Be alarmed that this might be broken sometimes.


**NOTE:**
In case you do not know what this means the [composer project website](http://getcomposer.org) is a good place to start.


### Github
Thus I recommend the composer way to make LiipDrupalRegistryModule a dependency to your project.
The sources are also available via github. Just clone it as you might be familiar with.

```bash
$ git clone git://github.com/liip/liipdrupalregistrymodule.git
```

## Dependencies

- LiipDrupalConnector (http://github.com/liip/liipdrupalconnector)
- Assert (http://github.com/beberlei/assert)

## Usage
A good place to find examples of how this library works is always the Tests folder.
For those not familiar with PHPUnit a short intro:

```php

$factory = new \Liip\Drupal\Modules\DrupalConnector\ConnectorFactory();
$connector = $factory->getCommonConnector();

$assertions = new \Assert\Assertion();

$registry = new D7Config('myEvents', $connector, $assertions);

// put stuff in to the registry.
$registry->register('eventItemRegister', $item);

// get as single element out of the registry or an empty array if it oes not exist.
$item = $registry->getContentById('eventItemRegister', array());

// get complete register
$items = $register->getContent();

// replace content of an item with a new value
$register->replace('eventItemRegister', $newItem);

// determine if an element is already registered
$inRegister = $register->isRegistered('eventItemRegister');


// get rid of a single element
$registry->unregister('eventItemRegister');

// destroy the complete registry
$registry->destroy();

```
