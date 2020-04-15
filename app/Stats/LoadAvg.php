<?php

namespace App\Stats;

use App\LoadAvg as LoadAvgModel;
use App\Monitors\Monitor;
use Illuminate\Support\Facades\DB;

class LoadAvg extends AbstractStat implements Stat
{
    /**
     * Create a new Stat instance.
     *
     * @param  \App\Monitors\Monitor $monitor
     * @return void
     */
    public function __construct(Monitor $monitor)
    {
        $this->monitor = $monitor;
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

            LoadAvgModel::create([
                'load_avg' => $loads[0],
                'load_avg_percent' => $this->getServerLoadPercent(),
                'cpus' => (int) $cpuNb,
            ]);
        }
    }

    /**
     * Calculate the server load as a percentage.
     *
     * @return float|null
     */
    protected function getServerLoadPercent()
    {
        $stat1 = $this->getServerLoad();
        sleep(1);
        $stat2 = $this->getServerLoad();

        if (!$stat1 || !$stat2) {
            return null;
        }

        $stat2[0] -= $stat1[0];
        $stat2[1] -= $stat1[1];
        $stat2[2] -= $stat1[2];
        $stat2[3] -= $stat1[3];

        // Sum User, Nice, System and Idle
        $cpuTime = $stat2[0] + $stat2[1] + $stat2[2] + $stat2[3];

        $load = 100 - ($stat2[3] * 100 / $cpuTime);

        return $load;
    }

    /**
     * Gets the User, Nice, System and Idle values.
     *
     * @return array|null
     */
    protected function getServerLoad()
    {
        if (is_readable('/proc/stat')) {
            $stats = file_get_contents('/proc/stat');

            if (!$stats) {
                return null;
            }

            $stats = preg_replace('/[[:blank:]]+/', ' ', $stats);
            $stats = str_replace(["\r\n", "\n\r", "\r"], "\n", $stats);
            $lines = explode("\n", $stats);

            foreach ($lines as $stat) {
                $data = explode(" ", trim($stat));

                if (count($data) >= 5 && $data[0] == 'cpu') {
                    return [
                        $data[1],
                        $data[2],
                        $data[3],
                        $data[4],
                    ];
                }
            }
        }

        return null;
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
    CASE WHEN load_avg_percent {$op} ? THEN 'ALERT' ELSE 'OK' END AS currentState,
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
