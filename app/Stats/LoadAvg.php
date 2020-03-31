<?php

namespace App\Stats;

use App\LoadAvg;
use Illuminate\Support\Facades\DB;

class LoadAvg extends AbstractStat implements Stat
{
    protected $period;

    /**
     * Create a new Stat instance.
     *
     * @param  \App\Monitors\Monitor $monitor
     * @return void
     */
    public function __construct(Monitor $monitor, $period)
    {
        $this->monitor = $monitor;
        $this->period = $period;
    }

    /**
     * Sample the stat.
     *
     * @return void
     */
    public function sample()
    {
        if (is_readable("/proc/cpuinfo")) {
            $cpuNb = $this->executeCommand('cat /proc/cpuinfo | grep "^processor" | wc -l');
            $load = $this->executeCommand("cat /proc/loadavg | awk '{print $1, $2, $3}'");

            $loads = explode(' ', $load);

            LoadAvg::create([
                'period_1' => $loads[0],
                'period_2' => $loads[1],
                'period_3' => $loads[2],
                'cpus' => (int) $cpuNb,
            ]);
        }
    }

    /**
     * Test the stat.
     *
     * @return bool
     */
    public function test()
    {
        $op = $this->getOperator();

        $results = DB::select("SELECT
    CASE WHEN period_{$this->period} {$op} ? THEN 'ALERT' ELSE 'OK' END AS currentState,
    IFNULL(alerts.monitor_state, 'UNKNOWN') AS lastState
FROM (
    SELECT * FROM load_avgs WHERE created_at >= DATETIME('NOW', ?) ORDER BY created_at DESC LIMIT ?
) _samples
LEFT JOIN (SELECT * FROM alerts WHERE monitor_id = ? AND monitor_type = ? ORDER BY created_at DESC LIMIT 1) alerts", [
            $this->monitor->threshold,
            '-'.($this->monitor->minutes + 1).' minutes',
            $this->monitor->minutes + 1,
            $this->monitor->key,
            $this->monitor->type,
        ]);

        return $this->testResults($results);
    }
}
