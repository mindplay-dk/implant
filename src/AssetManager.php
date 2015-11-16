<?php

namespace mindplay\implant;

use Closure;
use ReflectionFunction;
use ReflectionParameter;
use UnexpectedValueException;
use MJS\TopSort\Implementations\StringSort;

class AssetManager
{
    /**
     * @var null[] map where asset package class-name => NULL
     */
    protected $class_names = [];

    /**
     * @var Closure[] list of callbacks for peppering packages
     */
    protected $peppering = [];

    /**
     * @param string $class_name fully-qualified class-name of asset package class
     */
    public function add($class_name)
    {
        $this->class_names[$class_name] = null;
    }

    /**
     * Populate the given asset model by creating and sorting packages, and then
     * applying {@see AssetPackage::defineAssets()} of every package to the given model.
     *
     * @param object $model asset model
     *
     * @return void
     */
    public function populate($model)
    {
        $packages = $this->sortPackages($this->createPackages());

        $this->pepperPackages($packages);

        foreach ($packages as $package) {
            $package->defineAssets($model);
        }
    }

    /**
     * Pepper a created AssetPackage using a callback function - this will be called
     * when you {@see populate()} your asset model, before calling the
     * {@see AssetPackage::defineAssets()} functions of every added package.
     *
     * The given function must accept precisely one argument (and should return nothing)
     * and must be type-hinted to specify which package you wish to pepper.
     *
     * @param Closure $callback function (PackageType $package) : void
     */
    public function pepper($callback)
    {
        $this->peppering[] = $callback;
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

        $packages = [];

        $this->createAllPackages(array_keys($this->class_names), $packages);

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

        $created = [];

        foreach ($class_names as $class_name) {
            $package = $this->createPackage($class_name);

            $created[] = $packages[$class_name] = $package;
        }

        $missing = [];

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
         * @var string[]       $order  list of topologically sorted class-names
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

        $sorted = [];

        foreach ($order as $class_name) {
            $sorted[$class_name] = $packages[$class_name];
        }

        return $sorted;
    }

    /**
     * Expose packages to previously added peppering functions.
     *
     * @param AssetPackage[] $packages
     *
     * @see pepper()
     */
    protected function pepperPackages($packages)
    {
        foreach ($this->peppering as $pepper) {
            $function = new ReflectionFunction($pepper);

            if ($function->getNumberOfParameters() === 1) {
                $param = new ReflectionParameter($pepper, 0);

                $class = $param->getClass();

                if ($class !== null) {
                    $name = $class->getName();

                    if (isset($packages[$name])) {
                        call_user_func($pepper, $packages[$name]);
                    }

                    continue;
                }
            }

            $file = $function->getFileName();
            $line = $function->getStartLine();

            throw new UnexpectedValueException(
                "unexpected function signature at: {$file}, line {$line} " .
                "(pepper functions must accept precisely one argument, and must provide a type-hint)"
            );
        }
    }
}
