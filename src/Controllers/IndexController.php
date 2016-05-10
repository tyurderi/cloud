<?php

namespace Beast\Cloud\Controllers;

class IndexController extends \Slim\Mvc\Controller
{

    public function indexAction()
    {
        $fs = new \Beast\Cloud\Filesystem\Filesystem(APP_DIR . '/files/');

        $fs->makeDir('dev/php/cloud/src', true);
        $fs->cd('dev/php/cloud/src');

        $fs->touch('Cache.php');

        $fs->remove('Cache.php');

        $fs->removeDir('/dev', true);

        $fs->cd('/');
        $fs->makeDir('cpp files');
        $fs->cd('cpp files');

        $fs->touch('pathfind.cpp');
        $fs->touch('pathfind.h');

        var_dump($fs->ls());

        var_dump($fs->good());

        //$fs->makeDir('dev/php/beast_cloud');

        //$fs->cd('dev/php/beast_cloud')->write('index.html', 'Hello World');

        //var_dump($fs->ls());
    }

}