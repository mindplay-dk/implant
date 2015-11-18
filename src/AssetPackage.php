<?php

namespace mindplay\implant;

/**
 * This interface must be implemented by asset package classes.
 */
interface AssetPackage
{
    /**
     * @param object $model the model being populated with assets
     *
     * @return void
     */
    public function defineAssets($model);

    /**
     * @return string[] list of fully-qualified class-names of package dependencies
     */
    public function listDependencies();
}
