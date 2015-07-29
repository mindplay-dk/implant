<?php

namespace mindplay\implant;

/**
 * Stylesheet asset type (emits a link-tag)
 */
class StylesheetAssetType implements AssetType
{
    public function render($url)
    {
        return '<link rel="stylesheet" type="text/css" href="' . $url . '"/>';
    }
}
