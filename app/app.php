<?php

use Symfony\Component\HttpFoundation\Response;
use Silex\Provider\FormServiceProvider;

require_once(__DIR__ . '/bootstrap.php');
require_once(__DIR__ . '/config.php');

$app = new Silex\Application();

$app['debug'] = $config['debug'];
$app['currency'] = $config['currency'];

// allow for translation
$app->register(new Silex\Provider\TranslationServiceProvider(), array(
    'locale_fallback' => 'en',
));

$app->register(new Silex\Provider\DoctrineServiceProvider(), $config['db']);

// form provider
$app->register(new FormServiceProvider());
$app->register(new Silex\Provider\ValidatorServiceProvider());

//$app['translator'] = $app->share($app->extend('translator', function($translator, $app) {
//
//    $translator->addResource('xliff', __DIR__.'/../translations/en.xliff', 'en');
//    $translator->addResource('xliff', __DIR__.'/../translations/fi.xliff', 'fi');
//
//    return $translator;
//}));

// register template engine
$app->register(new Silex\Provider\TwigServiceProvider(), array(
    'twig.path' => __DIR__.'/../views',
));
//$app['twig']->addExtension(new Marketplace\Twig\Extensions\TimeAgoExtension($app['translator']));

return $app;

?>