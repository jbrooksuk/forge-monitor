<?php

namespace App\Console\Commands;

use App\Config\Context;
use App\Monitors\MonitorConfig;
use Illuminate\Console\Command;

abstract class AbstractStatCommand extends Command
{
    /**
     * The context instance.
     *
     * @var \App\Context
     */
    protected $context;

    /**
     * The configured monitors for the stat.
     *
     * @var \Illuminate\Support\Collection
     */
    protected $monitors;

    /**
     * The stat type to look for when running the command.
     *
     * @var array|string
     */
    protected $statType;

    /**
     * Create a new Disk Stat Command instance.
     *
     * @param  \App\Config\Context  $context
     * @param  \App\Monitors\MonitorConfig  $monitorConfig
     * @return void
     */
    public function __construct(Context $context, MonitorConfig $monitorConfig)
    {
        parent::__construct();

        if (!$this->statType) {
            throw new Exception('No statType defined.');
        }

        $this->context = $context;
        $this->monitors = $monitorConfig->forType($this->statType);
    }

    /**
     * Set context settings.
     *
     * @return void
     */
    protected function handleContext() : void
    {
        if ($endpoint = $this->option('endpoint')) {
            $this->context->setMonitorEndpoint($this->option('endpoint'));
        }
    }
}
