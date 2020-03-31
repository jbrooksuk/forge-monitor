<?php

namespace App\Monitors;

use Illuminate\Support\Arr;
use Yosymfony\Toml\Toml;

class MonitorConfig
{
    /**
     * The config file finder instance.
     *
     * @var \App\Monitors\ConfigFileFinder
     */
    protected $configFileFinder;

    /**
     * The base config collection.
     *
     * @var \Illuminate\Support\Collection
     */
    protected $config;

    /**
     * Create a new monitor config instance.
     *
     * @return void
     */
    public function __construct(ConfigFileFinder $configFileFinder)
    {
        $this->configFileFinder = $configFileFinder;

        $this->config = $this->parseConfig();
    }

    /**
     * Return the configurations for a given stat type.
     *
     * @param  array|string $types
     * @return mixed
     */
    public function forType($types)
    {
        $types = Arr::wrap($type);

        if ($this->config) {
            return $this->config->whereIn('type', $types);
        }
    }

    /**
     * Parse the config file into a collection.
     *
     * @return \Illuminate\Support\Collection|void
     */
    protected function parseConfig()
    {
        $configFile = $this->configFileFinder->find();

        if (!$configFile) {
            return;
        }

        return collect(Toml::ParseFile($configFile))->transform(function ($monitor, $key) {
            return new Monitor(
                $key,
                Arr::get($monitor, 'type'),
                Arr::get($monitor, 'operator'),
                Arr::get($monitor, 'threshold'),
                Arr::get($monitor, 'minutes', 0), // Disk requires no minutes.
                Arr::get($monitor, 'token')
            );
        });
    }
}
