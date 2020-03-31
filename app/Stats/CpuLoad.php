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
        // https://www.php.net/manual/en/function.sys-getloadavg.php#118673
        if (is_readable("/proc/stat")) {
            // Collect 2 samples - each with 1 second period
            $statData1 = $this->getServerLoadData();
            sleep(1);
            $statData2 = $this->getServerLoadData();

            if (!is_null($statData1) && !is_null($statData2)) {
                $statData2[0] -= $statData1[0];
                $statData2[1] -= $statData1[1];
                $statData2[2] -= $statData1[2];
                $statData2[3] -= $statData1[3];

                // Sum up the 4 values for User, Nice, System and Idle and calculate
                // the percentage of idle time (which is part of the 4 values!)
                $cpuTime = $statData2[0] + $statData2[1] + $statData2[2] + $statData2[3];

                // Invert percentage to get CPU time, not idle time
                $load = 100 - ($statData2[3] * 100 / $cpuTime);

                CpuUsage::create([
                    'load' => $load,
                ]);
            }
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

    /**
     * Read the /proc/stat value.
     *
     * @return array|null
     */
    protected function getServerLoadData()
    {
        if (is_readable("/proc/stat")) {
            $stats = @file_get_contents("/proc/stat");

            if ($stats !== false) {
                $stats = preg_replace("/[[:blank:]]+/", " ", $stats);

                $stats = str_replace(array("\r\n", "\n\r", "\r"), "\n", $stats);
                $stats = explode("\n", $stats);

                foreach ($stats as $statLine) {
                    $statLineData = explode(" ", trim($statLine));

                    // Found!
                    if ((count($statLineData) >= 5) && ($statLineData[0] == "cpu")) {
                        return [
                            $statLineData[1],
                            $statLineData[2],
                            $statLineData[3],
                            $statLineData[4],
                        ];
                    }
                }
            }
        }

        return null;
    }
}
