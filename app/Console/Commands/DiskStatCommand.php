<?php

namespace App\Console\Commands;

use App\Monitors\MonitorConfig;
use App\Stats\DiskSpace;
use Illuminate\Console\Command;
use Illuminate\Console\Scheduling\Schedule;

class DiskStatCommand extends Command
{
    use InteractsWithCli;

    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'stat:disk';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Sample the disk space.';

    /**
     * The monitors for the disk type.
     *
     * @var \Illuminate\Support\Collection
     */
    protected $diskMonitors;

    /**
     * Whether the sample has been taken.
     *
     * @var bool
     */
    protected $sampleTaken;

    /**
     * Create a new Disk Stat Command instance.
     *
     * @return void
     */
    public function __construct(MonitorConfig $monitorConfig)
    {
        parent::__construct();

        $this->diskMonitors = $monitorConfig->forType('disk');
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        // Don't run when no monitors are configured.
        if ($this->diskMonitors->isEmpty()) {
            $this->verboseInfo("No disk monitors configured...");

            return;
        }

        $this->diskMonitors->each(function ($monitor) {
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
