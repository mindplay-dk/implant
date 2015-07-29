<?php

use mindplay\implant\AssetManager;

require dirname(__DIR__) . '/vendor/autoload.php';
require __DIR__ . '/fixtures.php';

test(
    'Can create Javascript tags',
    function () {
        $manager = new AssetManager();

        $manager->add(B::class);
        $manager->add(C::class);
        $manager->add(A::class);
        $manager->add(D::class);

        echo $manager->render();
    }
);

exit(run());
