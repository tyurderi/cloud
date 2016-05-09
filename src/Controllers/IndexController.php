<?php

class IndexController extends \Slim\Mvc\Controller
{

    public function indexAction()
    {
        $fs = new \Beast\Cloud\Filesystem\Filesystem('');

        $fs->makeDir('dev/php/beast_cloud');

        $fs->cd('dev/php/beast_cloud')->write('index.html', 'Hello World');

        var_dump($fs->ls());
    }

}