<?php

namespace mindplay\implant;

interface AssetPackage
{
    /**
     * @param AssetCollector $collector
     *
     * @return void
     */
    public function collectAssets(AssetCollector $collector);

    /**
     * @return string[] list of fully-qualified class-names of package dependencies
     */
    public function listDependencies();
}
