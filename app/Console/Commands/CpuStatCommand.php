<?php

namespace App\Console\Commands;

use App\Monitors\MonitorConfig;
use Illuminate\Console\Command;
use Illuminate\Console\Scheduling\Schedule;

class CpuStatCommand extends Command
{
    use InteractsWithCli;

    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'stat:cpu';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Sample the CPU.';

    /**
     * The monitors for the cpu load.
     *
     * @var \Illuminate\Support\Collection
     */
    protected $cpuMonitors;

    /**
     * Whether the sample has been taken.
     *
     * @var bool
     */
    protected $sampleTaken;

    /**
     * Create a new Cpu Stat Command instance.
     *
     * @return void
     */
    public function __construct(MonitorConfig $monitorConfig)
    {
        parent::__construct();

        $this->cpuMonitors = $monitorConfig->forType('cpu_load');
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        // Don't run when no monitors are configured.
        if ($this->cpuMonitors->isEmpty()) {
            $this->verboseInfo("No CPU monitors configured...");

            return;
        }

        $this->cpuMonitors->each(function ($monitor) {
            // Take the sample if we haven't done so already.
            if (!$this->sampleTaken) {
                $this->sampleTaken = true;

                return $monitor->stat()->sample();
            }
        })->each(function ($monitor) {
            $this->verboseInfo("Testing {$monitor->key}...");

            $monitor->stat()->test();
        });
    }
}
