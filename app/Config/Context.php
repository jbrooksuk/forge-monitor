<?php

namespace App\Config;

class Context
{
    /**
     * The default endpoint to ping Forge.
     *
     * @var string
     */
    const DEFAULT_MONITOR_ENDPOINT = 'https://forge.laravel.com/monitors/ping';

    /**
     * The endpoint to ping Forge.
     *
     * @var null|string
     */
    protected $monitorEndpoint;

    /**
     * Set the monitor endpoint.
     *
     * @param  string  $endpoint
     * @return $this
     */
    public function setMonitorEndpoint(string $endpoint)
    {
        $this->monitorEndpoint = $endpoint;

        return $this;
    }

    /**
     * Get the monitor endpoint.
     *
     * @return string
     */
    public function getMonitorEndpoint(): string
    {
        if ($this->monitorEndpoint) {
            return $this->monitorEndpoint;
        }

        return self::DEFAULT_MONITOR_ENDPOINT;
    }
}
