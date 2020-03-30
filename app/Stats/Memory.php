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
            } else {
                $lines = <<<MEMINFO
MemTotal:        8174812 kB
MemFree:          833988 kB
MemAvailable:    7046932 kB
Buffers:          974380 kB
Cached:          4305628 kB
SwapCached:            0 kB
Active:          3470896 kB
Inactive:        2328828 kB
Active(anon):     555384 kB
Inactive(anon):    82336 kB
Active(file):    2915512 kB
Inactive(file):  2246492 kB
Unevictable:        3652 kB
Mlocked:            3652 kB
SwapTotal:       1048572 kB
SwapFree:        1048572 kB
Dirty:                92 kB
Writeback:             0 kB
AnonPages:        523408 kB
Mapped:            87244 kB
Shmem:            115584 kB
Slab:            1456040 kB
SReclaimable:    1354864 kB
SUnreclaim:       101176 kB
KernelStack:        3648 kB
PageTables:        40516 kB
NFS_Unstable:          0 kB
Bounce:                0 kB
WritebackTmp:          0 kB
CommitLimit:     5135976 kB
Committed_AS:    2223072 kB
VmallocTotal:   34359738367 kB
VmallocUsed:           0 kB
VmallocChunk:          0 kB
HardwareCorrupted:     0 kB
AnonHugePages:         0 kB
CmaTotal:              0 kB
CmaFree:               0 kB
HugePages_Total:       0
HugePages_Free:        0
HugePages_Rsvd:        0
HugePages_Surp:        0
Hugepagesize:       2048 kB
DirectMap4k:      210924 kB
DirectMap2M:     6080512 kB
DirectMap1G:     2097152 kB
MEMINFO;
            }

            preg_match_all('/(\d+)/', $lines, $pieces);

            return $pieces;
        });
    }
}
