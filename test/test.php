<?php

use mindplay\implant\AssetManager;

require dirname(__DIR__) . '/vendor/autoload.php';

require __DIR__ . '/fixtures.php';

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

        eq($model->js, array('a.js', 'b.js', 'c.js', 'd.js'), 'it should sort the assets');
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

        eq($model->js, array('foo.js'), 'pepper function was applied');
    }
);

exit(run());
