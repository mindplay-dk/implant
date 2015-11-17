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
     * @var AssetInjection[] list of injected, anonymous asset packages
     */
    protected $injections = [];

    /**
     * @var Closure[] list of callbacks for peppering packages
     */
    protected $peppering = [];

    /**
     * @param string $class_name fully-qualified class-name of asset package class
     *
     * @return void
     */
    public function add($class_name)
    {
        $this->class_names[$class_name] = null;
    }

    /**
     * @param callable $callback     asset definition callback - function ($model) : void
     * @param string[] $dependencies list of fully-qualified class-names of package dependencies
     *
     * @return void
     */
    public function inject(callable $callback, array $dependencies = [])
    {
        $this->injections[] = new AssetInjection($callback, $dependencies);
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
     *
     * @return void
     */
    public function pepper($callback)
    {
        $this->peppering[] = $callback;
    }

    /**
     * Create all packages
     *
     * @return AssetPackage[] map of asset packages
     */
    private function createPackages()
    {
        /**
         * @var AssetPackage[] $packages
         * @var AssetPackage[] $pending
         */

        $class_names = array_keys($this->class_names);

        $packages = array_merge(
            $this->injections,
            array_combine($class_names, array_map([$this, "createPackage"], $class_names))
        );

        $pending = array_keys($packages);

        $done = [];

        while (count($pending)) {
            $index = array_pop($pending);

            if (isset($done[$index])) {
                continue;
            }

            if (!isset($packages[$index])) {
                $packages[$index] = $this->createPackage($index);
            }

            $pending = array_merge($pending, $packages[$index]->listDependencies());

            $done[$index] = true;
        }

        return $packages;
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
     * @param AssetPackage[] $packages list of packages
     *
     * @return AssetPackage[] sorted map of packages
     */
    private function sortPackages($packages)
    {
        /**
         * @var string[]       $order  list of topologically sorted class-names
         * @var AssetPackage[] $sorted resulting ordered list of asset packages
         */

        // pre-sort packages by index:

        ksort($packages, SORT_STRING);

        // topologically sort packages by dependencies:

        $sorter = new StringSort();

        foreach ($packages as $index => $package) {
            $sorter->add($index, $package->listDependencies());
        }

        $order = $sorter->sort(); // TODO QA: catch and re-throw CircularDependencyException here?

        // create sorted map of packages:

        $sorted = [];

        foreach ($order as $index) {
            $sorted[$index] = $packages[$index];
        }

        return $sorted;
    }

    /**
     * Expose packages to previously added peppering functions.
     *
     * @param AssetPackage[] $packages
     *
     * @return void
     *
     * @see pepper()
     */
    private function pepperPackages($packages)
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
