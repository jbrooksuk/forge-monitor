<?php

namespace App\Stats;

use App\CpuUsage;
use Illuminate\Support\Facades\DB;

class CpuLoad extends AbstractStat implements Stat
{
    /**
     * Sample the stat.
     *
     * @return void
     */
    public function sample()
    {
        if (is_readable("/proc/cpuinfo")) {
            $cpuNb = (float) $this->executeCommand('cat /proc/cpuinfo | grep "^processor" | wc -l');
            $load = (float) $this->executeCommand('cat /proc/loadavg | awk "{print $1}"');
            $loadPercent = (100 * $load) / $cpuNb;

            if ($loadPercent >= 100.0) {
                $loadPercent = 100.0;
            }

            CpuUsage::create([
                'load' => $loadPercent,
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
    CASE WHEN load {$op} ? THEN 'ALERT' ELSE 'OK' END AS currentState,
    IFNULL(alerts.monitor_state, 'UNKNOWN') AS lastState
FROM (
    SELECT * FROM cpu_usages WHERE created_at >= DATETIME('NOW', ?) ORDER BY created_at DESC LIMIT ?
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
