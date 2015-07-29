<?php

namespace mindplay\implant;

/**
 * This interface defines a means of rendering an HTML tag to embed an asset.
 */
interface AssetType
{
    /**
     * @param string $url asset URL
     *
     * @return string HTML tag to embed the asset
     */
    public function render($url);
}
