<?php

namespace mindplay\implant;

/**
 * Javascript asset type (emits a script-tag)
 */
class JavascriptAssetType implements AssetType
{
    public function render($url)
    {
        return '<script type="text/javascript" src="' . htmlspecialchars($url) . '"></script>';
    }
}
