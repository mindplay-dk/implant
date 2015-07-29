<?php

namespace mindplay\implant;

/**
 * This class represents a sorted list of a specific type of assets.
 */
class AssetList
{
    /**
     * @var string[] list of asset URLs
     */
    public $assets = array();

    /**
     * @var AssetType asset type
     */
    public $type;

    /**
     * @param AssetType $type asset type
     */
    public function __construct(AssetType $type)
    {
        $this->type = $type;
    }

    /**
     * @return string rendered HTML tags to embed the assets
     */
    public function render()
    {
        return implode(
            "\n",
            array_map(
                array($this->type, 'render'),
                $this->assets
            )
        );
    }
}
