<?php

use Doctrine\Common\Annotations\AnnotationRegistry;
use Composer\Autoload\ClassLoader;

/**
 * @var ClassLoader $loader
 */
$loader = require __DIR__.'/../vendor/autoload.php';

AnnotationRegistry::registerLoader(array($loader, 'loadClass'
// 			'Sonata' => __DIR__.'/../vendor/bundles',
// 			'Exporter' => __DIR__.'/../vendor/exporter/lib',
// 			'Knp\Bundle' => __DIR__.'/../vendor/bundles',
// 			'Knp\Menu' => __DIR__.'/../vendor/KnpMenu/src',
));

return $loader;
