mindplay/implant
----------------

Simple packaging and dependency sorting for embedded JS and CSS assets.

[![Build Status](https://travis-ci.org/mindplay-dk/implant.svg)](https://travis-ci.org/mindplay-dk/implant)

[![Code Coverage](https://scrutinizer-ci.com/g/mindplay-dk/implant/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/mindplay-dk/implant/?branch=master)

[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/mindplay-dk/implant/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/mindplay-dk/implant/?branch=master)


## Introduction

This library provides a simple, open mechanism for packaging assets, e.g. Javascript
and CSS files, and managing dependencies between them.

Asset packages are (singleton) classes, which define their dependencies on other asset
packages, and populate a view-model with lists of JS and CSS asset URLs.

This library *does not* define any fixed set of asset types or locations - it's not
limited to any specific model shape, which means you can use it not only for JS and CSS
assets, but for anything you can imagine as an asset, such as inline scripts, web-fonts
menus, images, locations in a layout, whatever.

Package granularity is also your choice - for example, you could choose to package related
scripts with required CSS files as a combined asset package, or you could choose to package
them individually, say, if the dependency order of scripts and CSS files differ.

It also does not output HTML tags or render anything, as this is actually the easy part,
as you will see in the examples below.


## Installation

With composer:

    composer require mindplay/implant


## Tutorial

Asset packages are classes implementing the [AssetPackage](src/AssetPackage.php) interface,
which enables them to declare their dependencies on other package types, and to define
the assets associated with the package, by populating an (arbitrary) model object.

Package classes must have an empty constructor, because the [AssetManager](src/AssetManager.php)
constructs packages automatically as needed. (note that this doesn't mean you can't inject
dependencies into you package classes - see "peppering" explained below.)

As a case example, let's say you wanted to package JQuery and Bootstrap - first off, you're
going to need a model that supports JS and CSS:

```PHP
class AssetModel
{
    public $js = [];
    public $css = [];
}
```

Let's assume you wanted to use JQuery from the CDN, rather than hosting it on your server:

```PHP
class JQueryPackage implements AssetPackage
{
    /**
     * @param AssetModel $model
     */
    public function defineAssets($model)
    {
        $model->js[] = 'https://code.jquery.com/jquery-1.11.3.min.js';
    }

    public function listDependencies()
    {
        return []; // JQuery has no dependencies
    }
}
```

Note that we cannot use a static type-hint in `defineAssets()` because this would violate
the interface signature - we use `@param` to type-hint for IDE support instead.

Also note that `listDependencies()` must be implemented, and must return an empty array,
to explicitly define that this package has no dependencies.

Next, let's package your locally-hosted bootstrap assets:

```PHP
class BootstrapPackage implements AssetPackage
{
    /**
     * @param AssetModel $model
     */
    public function defineAssets($model)
    {
        $root = '/assets/bootstrap';

        $model->js[] =  "{$root}/js/bootstrap.min.js";
        $model->css[] = "{$root}/css/bootstrap.min.css";
        $model->css[] = "{$root}/css/bootstrap-theme.min.css";
    }

    public function listDependencies()
    {
        return [JQueryPackage::class];
    }
}
```

Pay attention to `listDependencies()`, which defines a dependency on our `JQueryPackage`,
which must always be loaded *before* our `BootstrapPackage`.

Now that you assets are packaged, you're ready to roll:

```PHP
$manager = new AssetManager();

$manager->add(BootstrapPackage::class);

$model = new AssetModel();

$manager->populate($model);
```

Notice that we didn't need to manually add the `JQueryPackage` - and more importantly, if
you *had* added it manually, the order in which you add things makes no difference; the
order in which the packages are applied to your model is based on defined dependencies,
not on the order in which your packages are added. Sweet!

Finally, take your model to a view/template somewhere and render it:

```PHP
<?php

/**
 * @var AssetModel $model
 */

?>
<head>
    <?php foreach ($model->js as $js): ?>
        <script src="<?= htmlspecialchars($js) ?>"></script>
    <?php endforeach ?>
    <?php foreach ($model->css as $css): ?>
        <link rel="stylesheet" type="text/css" href="<?= htmlspecialchars($css) ?>"/>
    <?php endforeach ?>
</head>
```

Note the (of course, optional) type-hint for IDE support.

The final output is something like:

```HTML
<head>
    <script src="https://code.jquery.com/jquery-1.11.3.min.js"></script>
    <script src="/assets/bootstrap/js/bootstrap.min.js"></script>
    <link rel="stylesheet" type="text/css" href="/assets/bootstrap/css/bootstrap.min.css"/>
    <link rel="stylesheet" type="text/css" href="/assets/bootstrap/css/bootstrap-theme.min.css"/>
</head>
```

And you're done!

The [fixtures](test/fixtures.php) and [unit test](test/test.php) provide a specification,
and a [running example](test/example.php) is also provided.


## Injections

You can directly inject assets, in the form of an "anonymous" asset package, e.g. without having
to declare a class. This has the benefit of being able to do it on the fly, with the disadvantage
of being unable to reference the package - in other words, a directly injected asset package may
have dependencies, but other packages cannot depend upon it; in some situations, such as adding
assets directly from a controller or view, this is perfectly acceptable.

Example:

```php
$manager->inject(
    function ($model) {
        $model->js[] = "/assets/js/page_init.js"
    },
    [JQueryPackage::class]
);
```

In this example, we add a page-specific initialization script, which requires JQuery - this
injected package depends upon `JQueryPackage`, which will be applied before it; note again that
this anonymously injected package cannot be identified, which implies that no other package
may depend upon it.


## Peppering

Sometimes your package classes are going to have external dependencies, maybe even just
simple things like a root path or a flag - while a package class is required to have an
empty constructor, you can use property or setter injection, by "peppering" your package
classes; for example, let's say you added a `$minified` flag to your `BootstrapPackage`
(from before) to toggle using the minified scripts - you can switch it on/off, like so:

```PHP
$manager->pepper(function (BootstrapPackage $package) {
    $package->minified = true;
});
```

Any callback functions added in this way, will be applied when the packages are created.

Note that type-hints not matching any added package will be quietly ignored.
