<?php

use mindplay\implant\AssetCollector;
use mindplay\implant\AssetPackage;

class A implements AssetPackage
{
    public function collectAssets(AssetCollector $collector)
    {
        $collector->addJS('a.js');
    }

    public function listDependencies()
    {
        return array();
    }
}

class B implements AssetPackage
{
    public function collectAssets(AssetCollector $collector)
    {
        $collector->addJS('b.js');
    }

    public function listDependencies()
    {
        return array(A::class);
    }
}

class C implements AssetPackage
{
    public function collectAssets(AssetCollector $collector)
    {
        $collector->addJS('c.js');
    }

    public function listDependencies()
    {
        return array(B::class);
    }
}

class D implements AssetPackage
{
    public function collectAssets(AssetCollector $collector)
    {
        $collector->addJS('d.js');
    }

    public function listDependencies()
    {
        return array(B::class);
    }
}
