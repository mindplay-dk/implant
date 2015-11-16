<?php

use mindplay\implant\AssetManager;

require dirname(__DIR__) . '/vendor/autoload.php';

require __DIR__ . '/fixtures.php';

configure()->enableCodeCoverage(__DIR__ . '/build/clover.xml', dirname(__DIR__) . '/src');

test(
    'Can populate asset model',
    function () {
        $manager = new AssetManager();

        $manager->add(B::class);
        $manager->add(C::class);
        $manager->add(A::class);
        $manager->add(D::class);

        $model = new AssetModel();

        $manager->populate($model);

        eq($model->js, ['a.js', 'b.js', 'c.js', 'd.js'], 'it should sort the assets');
    }
);

test(
    'Can add missing dependencies',
    function () {
        $manager = new AssetManager();

        $manager->add(B::class);

        $model = new AssetModel();

        $manager->populate($model);

        eq($model->js, ['a.js', 'b.js'], 'it should add the missing asset');
    }
);

test(
    'can pepper packages with callback functions',
    function () {
        $manager = new AssetManager();

        $manager->add(PepperedPackage::class);

        $manager->pepper(function (PepperedPackage $package) {
            $package->value = "foo";
        });

        $model = new AssetModel();

        $manager->populate($model);

        eq($model->js, ['foo.js'], 'pepper function was applied');
    }
);

exit(run());
