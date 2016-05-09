<?php

namespace Beast\Cloud\Filesystem;

use Beast\Cloud\Interfaces\FilesystemInterface;
use Beast\Cloud\Models\File;
use Slim\Mvc\App;
use Slim\Mvc\Model\Entity\Repository;
use Slim\Mvc\Model\Entity\ResultSet;

class Filesystem implements FilesystemInterface
{

    /**
     * @var Cache
     */
    protected $fileCache   = null;

    /**
     * @var Repository
     */
    protected $repository  = null;

    protected $workingDir  = '/';

    /**
     * @var File
     */
    protected $workingFile = null;

    protected $saveDir     = '';

    public function __construct($saveDir)
    {
        $this->fileCache   = new Cache();
        $this->repository  = App::instance()->models()->repository('Beast\Cloud\Models\File');
        $this->workingDir  = '/';
        $this->workingFile = $this->root();
        $this->saveDir     = $saveDir;
    }
    
    public function cd($directory = '')
    {
        $directory = $this->normalizeFilename($directory);

        if ($file = $this->resolveFile($directory))
        {
            $this->workingDir  = $directory;
            $this->workingFile = $file;
        }

        return $file !== false;
    }
    
    public function ls($directory = '')
    {
        $directory = $this->normalizeFilename($directory);
        $files     = array();

        if ($file = $this->resolveFile($directory))
        {
            $files = $this->repository->findBy(array(
                'parentID'  => $file->id
            ));
        }

        return $files;
    }
    
    public function cwd()
    {
        return $this->workingDir;
    }
    
    public function root()
    {
        if ($this->fileCache->has('/') === false)
        {
            $file = new File();
            $file->id = -1;

            $this->fileCache->set('/', $file);
        }

        return $this->fileCache->get('/');
    }
    
    public function stat($filename)
    {
        $filename = $this->normalizeFilename($filename);

        return $this->resolveFile($filename);
    }
    
    public function cat($filename, $silent = true)
    {
        $filename = $this->normalizeFilename($filename);

        if ($file = $this->resolveFile($filename))
        {
            $filename = $this->saveDir . '/' . $file->localName;
            if (is_file($filename))
            {
                if ($silent)
                {
                    return file_get_contents($filename);
                }
                else
                {
                    readfile($filename);
                }
            }
        }

        return false;
    }
    
    public function touch($filename)
    {
        $filename = $this->normalizeFilename($filename);

        return $this->write($filename, '');
    }
    
    public function write($filename, $content)
    {
        $filename = $this->normalizeFilename($filename);

        if (!($file = $this->resolveFile($filename)))
        {
            $file = new File();
            $file->parentID  = $this->workingFile->id;
            $file->name      = basename($filename);
            $file->extension = pathinfo($filename, PATHINFO_EXTENSION);
            $file->type      = File::TYPE_FILE;
            $file->localName = md5($filename);
            $file->created   = date('Y-m-d H:i:s');
        }

        $file->changed = date('Y-m-d H:i:s');
        $file->size    = strlen($content);

        if ($file->save())
        {
            file_put_contents($this->saveDir . '/' . $file->localName, $content);

            return true;
        }

        return false;
    }
    
    public function copy($from, $to)
    {
        $from = $this->normalizeFilename($from);
        $to   = $this->normalizeFilename($to);
    }
    
    public function move($from, $to)
    {
        $from = $this->normalizeFilename($from);
        $to   = $this->normalizeFilename($to);
    }
    
    public function exists($filename)
    {
        $filename = $this->normalizeFilename($filename);

        return $this->resolveFile($filename) !== false;
    }
    
    public function remove($filename)
    {
        $filename = $this->normalizeFilename($filename);

        if ($file = $this->resolveFile($filename))
        {
            $this->fileCache->delete($filename);

            return $file->delete();
        }

        return false;
    }
    
    public function removeDir($directory, $recursive = false)
    {
        if (!($directory instanceof File))
        {
            $directory = $this->normalizeFilename($directory);
            $directory      = $this->resolveFile($directory);
        }

        if ($file = $directory)
        {
            if ($recursive)
            {
                /** @var File[]|ResultSet $children */
                $children = $this->repository->findBy(array(
                    'parentID'  => $file->id,
                ));

                foreach ($children as $child)
                {
                    if ($child->type == File::TYPE_FILE)
                    {
                        $child->delete();
                    }
                    else
                    {
                        $this->removeDir($child, true);
                    }
                }
                
                $this->fileCache->clear();
            }
            else
            {
                $this->remove($directory); // okay, because we're caching models
            }
        }
    }
    
    public function makeDir($directory, $recursive = false)
    {
        $directory = $this->normalizeFilename($directory);
        $file      = $this->root();
        $parts    = explode('/', $directory);

        if ($recursive)
        {
            foreach($parts as $part)
            {
                $records = $this->repository->findBy(array(
                    'parentID'  => $file->id,
                    'name'      => $part
                ));

                if ($records->count() === 0)
                {
                    $tempFile = $this->createFolder($part, $file->id);

                    if ($tempFile->save())
                    {
                        $file = $tempFile;
                    }
                }
                else
                {
                    $file = $records->first();
                }
            }
        }
        else
        {
            $parentDirectory = dirname($directory);
            if ($file = $this->resolveFile($parentDirectory))
            {
                $tempFile = $this->createFolder(end($parts), $file->id);
                $tempFile->save();
            }
        }
    }

    private function createFolder($name, $parentID)
    {
        $file = new File();
        $file->parentID = $parentID;
        $file->name     = $name;
        $file->type     = File::TYPE_FOLDER;
        $file->created  = date('Y-m-d H:i:s');
        $file->changed  = date('Y-m-d H:i:s');

        return $file;
    }

    private function normalizeFilename($filename)
    {
        $filename = str_replace('//', '/', $filename);
        if ($filename[0] !== '/')
        {
            $filename = $this->workingDir . $filename;
        }

        return $filename;
    }

    private function resolveFile($filename)
    {
        if ($this->fileCache->has($filename) === false)
        {
            $file  = $this->root();
            $parts = explode('/', $filename);

            foreach ($parts as $part)
            {
                $records = $this->repository->findBy(array(
                    'parentID'  => $file->id,
                    'name'      => $part
                ));

                if ($records->count() === 0)
                {
                    $file = false;
                    break;
                }

                $file = $records->first();
            }
        }
        else
        {
            $file = $this->fileCache->get($filename);
        }

        return $file;
    }
    
}