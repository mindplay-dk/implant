<?php

namespace mindplay\implant;

/**
 * This class is exposed to asset packages when collecting assets.
 *
 * @see AssetPackage::collectAssets()
 */
class AssetCollector
{
    /**
     * @var AssetList
     */
    protected $js;

    /**
     * @var AssetList
     */
    protected $css;

    /**
     * @param AssetList $js
     * @param AssetList $css
     */
    public function __construct(AssetList $js, AssetList $css)
    {
        $this->js = $js;
        $this->css = $css;
    }

    public function addJS($url)
    {
        $this->js->assets[] = $url;
    }

    public function addCSS($url)
    {
        $this->css->assets[] = $url;
    }
}
