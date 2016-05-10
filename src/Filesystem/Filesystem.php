<?php

namespace Beast\Cloud\Filesystem;

use Beast\Cloud\Interfaces\FilesystemInterface;
use Beast\Cloud\Models\File;
use Slim\Mvc\App;
use Slim\Mvc\Model\Entity\Repository;
use Slim\Mvc\Model\Entity\ResultSet;

class Filesystem extends \Slim\Mvc\DI\Injectable implements FilesystemInterface
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

    protected $good        = false;

    public function __construct($saveDir)
    {
	parent::__construct();

        $this->fileCache   = new Cache();
        $this->repository  = $this->models()->repository('Beast\Cloud\Models\File');
        $this->workingDir  = '/';
        $this->workingFile = $this->root();
        $this->saveDir     = $saveDir;
    }

    public function good()
    {
        return $this->good;
    }
    
    public function cd($directory = '')
    {
        $directory = $this->normalizeFilename($directory);

        if ($file = $this->resolveFile($directory))
        {
            $this->workingDir  = $directory;
            $this->workingFile = $file;
        }

        $this->good = $file !== false;

        return $this;
    }
    
    public function ls($directory = '')
    {
        if (empty($directory))
        {
            $directory = $this->workingDir;
        }

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
            $file->created   = date('Y-m-d H:i:s');
            $file->localName = $this->generateFilename($file);
        }

        $file->changed = date('Y-m-d H:i:s');
        $file->size    = strlen($content);

        if ($fileId = $file->save())
        {
            $filename = $this->saveDir . '/' . $file->localName;
            file_put_contents($filename, $content);

            $this->good = true;
        }
        else
        {
            $this->good = false;
        }

        return $this;
    }
    
    public function copy($from, $to)
    {
        $from = $this->normalizeFilename($from);
        $to   = $this->normalizeFilename($to);

        if ($file = $this->resolveFile($from))
        {
            $targetDirectory = dirname($to);
            if (!($directory = $this->resolveFile($targetDirectory)))
            {
                $directory = $this->makeDir($targetDirectory, true);
            }

            $file->id        = 0;
            $file->parentID  = $directory->id;
            $file->name      = basename($to);
            $file->extension = pathinfo($file->name, PATHINFO_EXTENSION);

            $this->good = $file->save() !== 0;
        }
        else
        {
            $this->good = false;
        }

        return $this;
    }
    
    public function move($from, $to)
    {
        $from = $this->normalizeFilename($from);
        $to   = $this->normalizeFilename($to);

        if ($file = $this->resolveFile($from))
        {
            var_dump($from, $file);
            $targetDirectory = dirname($to);
            if (!($directory = $this->resolveFile($targetDirectory)))
            {
                $directory = $this->makeDir($targetDirectory, true);
            }

            $file->parentID  = $directory->id;
            $file->name      = basename($to);
            $file->extension = pathinfo($file->name, PATHINFO_EXTENSION);

            $this->good = $file->save() !== 0;
        }
        else
        {
            $this->good = false;
        }

        return $this;
    }
    
    public function exists($filename)
    {
        $filename   = $this->normalizeFilename($filename);
        $this->good = $this->resolveFile($filename) !== false;

        return $this;
    }
    
    public function remove($filename)
    {
        $filename = $this->normalizeFilename($filename);

        if ($file = $this->resolveFile($filename))
        {
            $this->fileCache->delete($filename);

            if ($file->type == File::TYPE_FILE)
            {
                $filename = $this->saveDir . '/' . $file->localName;
                if (is_file($filename))
                {
                    unlink($filename);
                }
            }

            $this->good = $file->delete() !== false;
        }
        else
        {
            $this->good = false;
        }

        return $this;
    }
    
    public function removeDir($directory, $recursive = false)
    {
        if (!($directory instanceof File))
        {
            $directory = $this->normalizeFilename($directory);
            $directory = $this->resolveFile($directory);
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
                        if ($child->type == File::TYPE_FILE)
                        {
                            $filename = $this->saveDir . '/' . $child->localName;
                            if (is_file($filename))
                            {
                                unlink($filename);
                            }
                        }

                        $child->delete();
                    }
                    else
                    {
                        $this->removeDir($child, true);
                    }
                }

                $file->delete();
                
                $this->fileCache->clear();
                $this->good = true;
            }
            else
            {
                $this->remove($directory); // okay, because we're caching models
            }
        }
        else
        {
            $this->good = false;
        }

        return $this;
    }
    
    public function makeDir($directory, $recursive = false)
    {
        $directory = $this->normalizeFilename($directory);
        $file      = $this->root();
        $parts    = explode('/', $directory);

        if ($this->resolveFile($directory))
        {
            $this->good = true;
        }
        else if ($recursive)
        {
            foreach($parts as $part)
            {
                if (empty($part))
                {
                    continue;
                }

                $records = $this->repository->findBy(array(
                    'parentID'  => $file->id,
                    'name'      => $part
                ));

                if ($records->count() === 0)
                {
                    $tempFile = $this->createFolder($part, $file->id);

                    if ($fileId = $tempFile->save())
                    {
                        $file     = $tempFile;
                        $file->id = $fileId;
                    }
                }
                else
                {
                    $file = $records->first();
                }
            }

            $this->good = true;

            return $file;
        }
        else
        {
            $parentDirectory = dirname($directory);
            if ($file = $this->resolveFile($parentDirectory))
            {
                $tempFile = $this->createFolder(end($parts), $file->id);
                $this->good = $tempFile->save() !== false;

                return $tempFile;
            }
        }

        return false;
    }

    private function generateFilename(File $file)
    {
        return hash('sha256', $file->name . $file->parentID);
    }

    private function createFolder($name, $parentID)
    {
        $file = new File();
        $file->parentID  = $parentID;
        $file->name      = $name;
        $file->type      = File::TYPE_FOLDER;
        $file->created   = date('Y-m-d H:i:s');
        $file->changed   = date('Y-m-d H:i:s');

        $file->extension = '';
        $file->size      = 0;
        $file->localName = '';

        return $file;
    }

    private function checkFilename($filename)
    {
        $disallowedChars = '<>:"\\|?*';
        $valid           = true;

        for ($i = 0; $i < 8; $i++)
        {
            if (strpos($filename, $disallowedChars[$i]) !== false)
            {
                $valid = false;
                break;
            }
        }

        return $valid;
    }

    private function normalizeFilename($filename)
    {
        $filename = str_replace('//', '/', $filename);

        if ($filename[0] !== '/')
        {
            $filename = $this->workingDir . '/' . $filename;
        }

        $filename = '/' . trim($filename, '/');

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
                if (empty($part))
                {
                    continue;
                }

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
