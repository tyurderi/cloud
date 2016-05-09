<?php

namespace Beast\Cloud\Models;

use Slim\Mvc\Model\ModelAbstract;

class File extends ModelAbstract
{

    const TYPE_FILE   = 1;

    const TYPE_FOLDER = 2;

    public $id;

    public $parentID;

    public $name;

    public $localName;

    public $extension;

    public $type;

    public $size;

    public $created;

    public $changed;

    public static function getSource()
    {
        return 'file';
    }

}