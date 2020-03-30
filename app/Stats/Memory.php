<?php

namespace App\Stats;

use App\MemoryUsage;

class Memory implements Stat
{
    /**
     * Sample the stat.
     *
     * @return void
     */
    public function sample()
    {
        $memory = $this->getMemoryInfo();

        $total = (float) $memory[0][0];
        $used = round($memory[0][6] / $total, 2) * 100;
        $free = 100 - $used;

        MemoryUsage::create([
            'total' => $total,
            'available' => $memory[0][2],
            'used' => $used,
            'free' => $free,
        ]);
    }

    /**
     * Test the stat.
     *
     * @return bool
     */
    public function test()
    {
        //
    }

    /**
     * Get the memory info.
     *
     * @return array
     */
    protected function getMemoryInfo()
    {
        return once(function () {
            if (is_readable('/proc/meminfo')) {
                $fh = fopen('/proc/meminfo', 'r');
                $lines = '';

                while ($line = fgets($fh)) {
                    $lines .= $line;
                }
                fclose($fh);
            }

            preg_match_all('/(\d+)/', $lines, $pieces);

            return $pieces;
        });
    }
}
