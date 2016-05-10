<?php

namespace Beast\Cloud\Controllers;

class IndexController extends \Slim\Mvc\Controller
{

    public function indexAction()
    {
        $fs = new \Beast\Cloud\Filesystem\Filesystem(APP_DIR . '/files/');

        $fs->touch('test.cpp');

        $fs->copy('test.cpp', 'test.h');

        var_dump($fs->good());

        //$fs->makeDir('dev/php/beast_cloud');

        //$fs->cd('dev/php/beast_cloud')->write('index.html', 'Hello World');

        //var_dump($fs->ls());
    }

}