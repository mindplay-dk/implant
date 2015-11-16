<?php

use mindplay\implant\AssetManager;
use mindplay\implant\AssetPackage;

header('Content-type: text/plain');

require dirname(__DIR__) . '/vendor/autoload.php';

// Define the asset model for your project:

class AssetModel
{
    public $js = [];
    public $css = [];
}

// Define your asset packages:

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

class BootstrapPackage implements AssetPackage
{
    /**
     * @param AssetModel $model
     */
    public function defineAssets($model)
    {
        $root = '/assets/bootstrap';

        $model->js[] =    "{$root}/js/bootstrap.min.js";
        $model->css[] =   "{$root}/css/bootstrap.min.css";
        $model->css[] =   "{$root}/css/bootstrap-theme.min.css";
    }

    public function listDependencies()
    {
        return [JQueryPackage::class];
    }
}

// Use an asset manager centrally in your project: (typically a DI container)

$manager = new AssetManager();

$manager->add(BootstrapPackage::class);

$model = new AssetModel();

$manager->populate($model);

// Take your resulting asset model and output it from your layout template:

?>
<head>
    <?php foreach ($model->js as $js): ?>
        <script src="<?= htmlspecialchars($js) ?>"></script>
    <?php endforeach ?>
    <?php foreach ($model->css as $css): ?>
        <link rel="stylesheet" type="text/css" href="<?= htmlspecialchars($css) ?>"/>
    <?php endforeach ?>
</head>
