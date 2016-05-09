<?php

namespace Beast\Cloud\Interfaces;

interface FilesystemInterface
{

    public function cd($directory = '');
    public function ls($directory = '');
    public function move($from, $to);
    public function copy($from, $to);
    public function touch($filename);
    public function write($filename, $content);
    public function stat($filename);
    public function cat($filename);
    public function remove($filename);
    public function removeDir($directory, $recursive = false);
    public function makeDir($directory, $recursive = false);
    public function exists($filename);
    public function cwd();
    public function root();
    public function good();

}