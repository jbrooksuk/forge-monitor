<?php

namespace App\Console\Commands;

use App\Monitors\MonitorConfig;
use App\Stats\Memory;
use Illuminate\Console\Command;
use Illuminate\Console\Scheduling\Schedule;

class MemoryStatCommand extends Command
{
    use InteractsWithCli;

    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'stat:mem';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Sample the memory.';

    /**
     * The monitors for the free memory type.
     *
     * @var \Illuminate\Support\Collection
     */
    protected $freeMemMonitors;

    /**
     * The monitors for the used memory type.
     *
     * @var \Illuminate\Support\Collection
     */
    protected $usedMemMonitors;

    /**
     * Create a new Memory Stat Command instance.
     *
     * @return void
     */
    public function __construct(MonitorConfig $monitorConfig)
    {
        parent::__construct();

        $this->freeMemMonitors = $monitorConfig->forType('free_memory');
        $this->usedMemMonitors = $monitorConfig->forType('used_memory');
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        if ($this->freeMemMonitors->isEmpty() && $this->usedMemMonitors->isEmpty()) {
            $this->verboseInfo("No memory monitors configured...");

            return;
        }

        // Sample the memory stat.
        app(Memory::class)->sample();

        $this->handleMonitors($this->freeMemMonitors);
        $this->handleMonitors($this->usedMemMonitors);
    }

    /**
     * Handle the monitors.
     *
     * @param  \Illuminate\Support\Collection $monitors
     * @param  string $type
     * @return void
     */
    protected function handleMonitors($monitors)
    {
        if ($monitors->isEmpty()) {
            return;
        }

        // Filter monitors where they failed the test.
        $monitors->each(function ($monitor) {
            $this->verboseInfo("Testing {$monitor->key}...");

            return $monitor->stat()->test();
        });
    }
}
