<?php

namespace App\Monitors;

use Illuminate\Support\Arr;
use Symfony\Component\Finder\Finder;

class ConfigFileFinder
{
    /**
     * The name of the config file to use.
     *
     * @var string
     */
    const FILE_NAME = '/\.monitor$/';

    /**
     * The directories to look in.
     *
     * @var array
     */
    protected $directories = [];

    /**
     * The symfony file finder instance.
     *
     * @var \Symfony\Component\Finder\Finder
     */
    protected $finder;

    /**
     * Create a new config file finder instance.
     *
     * @param  \Symfony\Component\Finder\Finder  $finder
     * @return  void
     */
    public function __construct(Finder $finder)
    {
        $this->finder = $finder;

        $this->finder
             ->ignoreDotFiles(false)
             ->depth('== 0')
             ->ignoreVCS(false)
             ->files()
             ->name(self::FILE_NAME);
    }

    /**
     * Add a directory to source for files.
     *
     * @param  string  $directory
     * @return $this
     */
    public function addDirectory($directory)
    {
        $this->directories[] = $directory;

        return $this;
    }

    /**
     * Find the files.
     *
     * @return array
     */
    public function find()
    {
        $files = $this->finder->in($this->directories);

        return Arr::first($files);
    }
}
