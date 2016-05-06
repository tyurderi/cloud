<?php

namespace Beast\Cloud\Filesystem;

class Cache
{

    protected $files = array();

    public function set($filename, $file)
    {
        $this->files[$filename] = $file;
    }

    public function has($filename)
    {
        return isset($this->files[$filename]);
    }

    public function get($filename)
    {
        if($this->has($filename))
        {
            return $this->files[$filename];
        }

        return false;
    }

    public function delete($filename)
    {
        if($this->has($filename))
        {
            unset($this->files[$filename]);
        }
    }

}