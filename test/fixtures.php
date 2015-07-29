<?php

use mindplay\implant\AssetPackage;

/**
 * This example class represents a sorted, collect set of embedded assets.
 *
 * This is effectively a view-model for rendering HTML tags.
 */
class AssetModel
{
    /**
     * @var string[] list of Javascript assets
     */
    public $js = array();
}

class A implements AssetPackage
{
    /** @param AssetModel $model */
    public function defineAssets($model)
    {
        $model->js[] = 'a.js';
    }

    public function listDependencies()
    {
        return array();
    }
}

class B implements AssetPackage
{
    /** @param AssetModel $model */
    public function defineAssets($model)
    {
        $model->js[] = 'b.js';
    }

    public function listDependencies()
    {
        return array(A::class);
    }
}

class C implements AssetPackage
{
    /** @param AssetModel $model */
    public function defineAssets($model)
    {
        $model->js[] = 'c.js';
    }

    public function listDependencies()
    {
        return array(B::class);
    }
}

class D implements AssetPackage
{
    /** @param AssetModel $model */
    public function defineAssets($model)
    {
        $model->js[] = 'd.js';
    }

    public function listDependencies()
    {
        return array(B::class);
    }
}

class PepperedPackage implements AssetPackage
{
    /** @var string */
    public $value;

    /** @param AssetModel $model */
    public function defineAssets($model)
    {
        $model->js[] = "{$this->value}.js";
    }

    public function listDependencies()
    {
        return array();
    }
}
