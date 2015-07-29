<?php

namespace mindplay\implant;

use MJS\TopSort\Implementations\StringSort;
use UnexpectedValueException;

class AssetManager
{
    /**
     * @var string[] list of asset package class-names
     */
    protected $class_names = array();

    /**
     * @param string $class_name fully-qualified class-name of asset package class
     */
    public function add($class_name)
    {
        $this->class_names[] = $class_name;
    }

    /**
     * @return string rendered HTML
     */
    public function render()
    {
        $packages = $this->sortPackages($this->createPackages());

        $js = new AssetList(new JavascriptAssetType());
        $css = new AssetList(new StylesheetAssetType());

        $collector = new AssetCollector($js, $css);

        foreach ($packages as $package) {
            $package->collectAssets($collector);
        }

        return $js->render() . $css->render();
    }

    /**
     * Create all packages
     *
     * @return AssetPackage[] list of asset packages
     */
    protected function createPackages()
    {
        /**
         * @var AssetPackage[] $packages
         */

        $packages = array();

        $this->createAllPackages($this->class_names, $packages);

        return $packages;
    }

    /**
     * Recursively create packages and all of their dependencies
     *
     * @param string[]       $class_names list of package class-names
     * @param AssetPackage[] &$packages   packages indexed by class-name
     *
     * @return void
     */
    protected function createAllPackages($class_names, &$packages)
    {
        /**
         * @var AssetPackage[] $created
         * @var string[]       $missing
         */

        $created = array();

        foreach ($class_names as $class_name) {
            $package = $this->createPackage($class_name);

            $created[] = $packages[$class_name] = $package;
        }

        $missing = array();

        foreach ($created as $package) {
            $dependencies = $package->listDependencies();

            foreach ($dependencies as $dependency) {
                if (!isset($packages[$dependency])) {
                    $missing[] = $dependency;
                }
            }
        }

        if (count($missing)) {
            $this->createAllPackages($missing, $packages);
        }
    }

    /**
     * Create an individual package
     *
     * @param string $class_name package class-name
     *
     * @return AssetPackage
     */
    protected function createPackage($class_name)
    {
        if (!class_exists($class_name)) {
            throw new UnexpectedValueException("undefined package class: {$class_name}");
        }

        $package = new $class_name();

        if (!$package instanceof AssetPackage) {
            throw new UnexpectedValueException("class must implement the AssetPackage interface: {$class_name}");
        }

        return $package;
    }

    /**
     * @param AssetPackage[] $packages packages indexed by class-name
     *
     * @return AssetPackage[] sorted list of packages
     */
    protected function sortPackages($packages)
    {
        /**
         * @var string[]       $order list of topologically sorted class-names
         * @var AssetPackage[] $sorted resulting ordered list of asset packages
         */

        // pre-sort packages by class-name:

        ksort($packages);

        // topologically sort packages by dependencies:

        $sorter = new StringSort();

        foreach ($packages as $class_name => $package) {
            $sorter->add($class_name, $package->listDependencies());
        }

        $order = $sorter->sort(); // TODO QA: catch and re-throw CircularDependencyException here?

        // create sorted list of packages:

        $sorted = array();

        foreach ($order as $class_name) {
            $sorted[] = $packages[$class_name];
        }

        return $sorted;
    }
}
