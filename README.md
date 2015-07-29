mindplay/implant
----------------

Simple packaging and dependency sorting for embedded JS and CSS assets.


## Introduction

This library provides a simple, open mechanism for packaging assets, e.g. Javascript
and CSS files, and managing dependencies between them.

Asset packages are (singleton) classes, which define their dependencies on other asset
packages, and populate a view-model with lists of JS and CSS asset URLs.

This library *does not* define any fixed set of asset types or locations - it's not
limited to any specific model shape, which means you can use it not only for JS and CSS
assets, but for anything you can imagine as an asset, such as inline scripts, menus,
images, locations in a layout, whatever.

It also does not output HTML tags or render anything, as this is actually the easy part,
as you will see in the examples below.


## Installation

With composer:

    composer require mindplay/implant


## Tutorial

Asset packages are classes implementing the [AssetPackage](src/AssetPackage) interface,
which enables them to declare their dependencies on other package types, and to define
the assets associated with the package, by populating an (arbitrary) model object.

Package classes must have an empty constructor, because the [AssetManager](src/AssetManager)
constructs packages automatically as needed.

As a case example, let's say you wanted to package JQuery and Bootstrap - first off, you're
going to need a model that supports JS, CSS and web-fonts:

```PHP
class AssetModel
{
    public $js = array();
    public $css = array();
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
        return array(); // JQuery has no dependencies
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
        return array(JQueryPackage::class);
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


## Peppering

TODO...
