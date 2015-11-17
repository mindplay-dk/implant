<?php

namespace mindplay\implant;

/**
 * This class represents an injected, anonymous asset package.
 *
 * This is useful for one-shot asset injections, for example from a controller or view.
 *
 * Injected assets can have dependencies, but unlike asset package classes, they do not
 * have a name - and therefore, other packages cannot depend upon injected assets.
 */
class AssetInjection implements AssetPackage
{
    /**
     * @var callable
     */
    private $callback;

    /**
     * @var string[] list of fully-qualified class-names of package dependencies
     */
    private $dependencies;

    /**
     * @param callable $callback     asset definition callback - function ($model) : void
     * @param string[] $dependencies list of fully-qualified class-names of package dependencies
     */
    public function __construct(callable $callback, array $dependencies)
    {
        $this->callback = $callback;
        $this->dependencies = $dependencies;
    }

    public function defineAssets($model)
    {
        call_user_func($this->callback, $model);
    }

    public function listDependencies()
    {
        return $this->dependencies;
    }
}
