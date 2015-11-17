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

test(
    'can inject anonymous assets',
    function () {
        $manager = new AssetManager();

        $manager->inject(
            function (AssetModel $model) {
                $model->js[] = 'injected.js';
            },
            [B::class]
        );

        $manager->add(D::class);
        $manager->add(C::class);

        $model = new AssetModel();

        $manager->populate($model);

        eq($model->js, ['a.js', 'b.js', 'injected.js', 'c.js', 'd.js'], 'it should add dependencies and sort assets');
    }
);

test(
    'throws for invalid package name',
    function () {
        $manager = new AssetManager();

        $manager->add("not_a_class");

        $model = new AssetModel();

        expect(
            UnexpectedValueException::class,
            "should throw for invalid package name",
            function () use ($manager, $model) {
                $manager->populate($model);
            }
        );
    }
);

test(
    'throws for non-package class-name',
    function () {
        $manager = new AssetManager();

        $manager->add(NotAPackage::class);

        $model = new AssetModel();

        expect(
            UnexpectedValueException::class,
            "should throw for invalid package name",
            function () use ($manager, $model) {
                $manager->populate($model);
            }
        );
    }
);

test(
    'throws for invalid pepper function',
    function () {
        $manager = new AssetManager();

        $manager->add(A::class);

        $manager->pepper(function ($foo) {});

        $model = new AssetModel();

        expect(
            UnexpectedValueException::class,
            "should throw for invalid pepper function",
            function () use ($manager, $model) {
                $manager->populate($model);
            }
        );
    }
);

test(
    'short-circuits on empty asset manager',
    function () {
        $manager = new AssetManager();

        $model = new AssetModel();

        $manager->populate($model);
    }
);

exit(run());
