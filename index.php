<?php

error_reporting(E_ALL);
ini_set("display_errors", "on");

define('APP_DIR', __DIR__);

require_once APP_DIR . '/vendor/autoload.php';

/** @var Slim\Mvc\App $app */
$app = Slim\Mvc\App::instance(array(
    'config' => array(
        'controller.namespace'     => 'Beast\\Cloud\\Controllers\\',
        'controller.class_suffix'  => 'Controller',
        'controller.method_suffix' => 'Action',

        'view.path'           => '',
        'view.cache_path'     => '',
        'view.resources_path' => 'resources/',

        'database.host'   => 'localhost',
        'database.shem'   => 'cloud',
        'database.user'   => 'root',
        'database.pass'   => '',

        'cache.path'      => APP_DIR . '/cache'
    ),
    'settings' => array(
        'displayErrorDetails' => true
    )
));

$app->get('/', 'Index:index');

$app->run();