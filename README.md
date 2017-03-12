<p align="center">
  <img src="https://github.com/wpbones/WPBones/wiki/images/wp-bones-logo-little.png" alt="WP Bones Logo" />
</p>

<p align="center">

  <a href="https://packagist.org/packages/wpbones/wpbones">
   <img src="https://poser.pugx.org/wpbones/wpbones/v/stable" alt="Latest Stable Version" />
  </a>
  
  <a href="https://packagist.org/packages/wpbones/wpbones">
   <img src="https://poser.pugx.org/wpbones/wpbones/downloads" alt="Total Downloads" />
  </a>

  <a href="https://packagist.org/packages/wpbones/wpbones">
   <img src="https://poser.pugx.org/wpbones/wpbones/license" alt="License" />
  </a>
  
  <a href="https://packagist.org/packages/wpbones/wpbones">
   <img src="https://poser.pugx.org/wpbones/wpbones/d/monthly" alt="Monthly Downloads" />
  </a>

</p>

WP Bones is a framework for [WordPress](http://wordpress.org) written with [composer](https://getcomposer.org/).
You can use [WP Kirk](https://github.com/wpbones/WPKirk) repo as a boilerplate to create a plugin.

As you know, WordPress doesn't support composer. So, I have used a little trick to fix this issue.

## Documentation

You'll find the [complete docs here](https://github.com/wpbones/WPBones/wiki).

## Requirement

### Composer

    $ curl -sS https://getcomposer.org/installer | sudo php -- --install-dir=/usr/local/bin --filename=composer

### Nodes

    $ sudo apt-get update && sudo apt-get install nodejs && sudo apt-get install npm
    $ sudo apt-get install nodejs-legacy

### Gulp

    $ sudo npm install --global gulp

## Boilerplate

You may start from [WP Kirk](https://github.com/wpbones/WPKirk) repo as a boilerplate to create a WP Bones WordPress plugin.

## I love Laravel

First to all, this framework and the boilerplate plugin are inspired to [Laravel](http://laravel.com/) framework. Also, you will find a `bones` php shell executable like Laravel `artisan`.
After cloning the repo, you can:

Display help

    $ php bones

Change namespace

    $ php bones namespace MyPluginName

The last command is very important. You can change the namespace in anytime. However, I suggest you to make this only the first time, when the plugin is inactive.
After changing of the namespace, you can start to develop you plugin. Your namespace will be `MyPluginName`.