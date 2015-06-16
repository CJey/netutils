<?php
# php version >= 5.6

function merge($nums) {
    if (empty($nums)) return [];
    $first = [];
    foreach ($nums as list($start, $end)) {
        if (isset($first[$start]) and $first[$start] >= $end) continue;
        $first[$start] = $end;
    }
    ksort($first);
    foreach ($first as $start => $end) {
        $c_start = $start;
        $c_end = $end;
        break;
    }
    $out = [];
    foreach ($first as $start => $end) {
        if ($start > $c_end + 1) {
            $out[] = [$c_start, $c_end];
            $c_start = $start;
            $c_end = $end;
        } elseif ($end > $c_end) $c_end = $end;
    }
    $out[] = [$c_start, $c_end];
    return $out;
}

function net2num($net) {
    static $pattern = '/^(\d+\.\d+\.\d+\.\d+)( *[,\/ ] *((\d+)|(\d+\.\d+\.\d+\.\d+)))?$/';
    static $map = [];
    static $cmap = [];
    static $dmap = [];
    if (empty($map)) {
        for ($i = 0; $i <= 32; ++$i) {
            $x = pow(2, 32 - $i);
            $cmap[$i] = $x;
            $x = 0xFFFFFFFF ^ ($x - 1);
            $dmap[$i] = $x;
            $map[long2ip($x)] = $i;
        }
    }
    if (!preg_match($pattern, trim($net), $match)) return 1;
    $ip = ip2long($match[1]);
    if ($ip === false) return 2;

    $cnt = count($match);
    if ($cnt > 2) {
        if ($cnt > 5) {
            $mask = $match[5]; # traditional
            if (!isset($map[$mask])) 3;
            $cidr = $map[$mask];
        } else {
            $cidr = (int)$match[4]; # cidr
            if ($cidr > 32) return 4;
        }
        if (($ip & $dmap[$cidr]) !== $ip) return 5;
    } else $cidr = 32;

    return [$ip, $ip + $cmap[$cidr] - 1];
}

function num2net($num) {
    static $map;
    if ($map === NULL) {
        for ($i = 0; $i <= 32; ++$i) {
            $map[$i] = pow(2, 32 - $i);
        }
    }
    $out = [];
    list($start, $end) = $num;
    while (true) {
        for ($i = 32; $i >= 0; --$i) {
            $tmp = $start + $map[$i] - 1 - $end;
            if ($tmp > 0) {
                $out[] = [long2ip($start), $i + 1];
                $start = $start + $map[$i + 1];
                break;
            } elseif ($tmp == 0) {
                $out[] = [long2ip($start), $i];
                break 2;
            }
        }
    }
    return $out;
}

function main($nets) {
    $nums = [];
    foreach ($nets as $net) {
        $num = net2num($net);
        if (!is_array($num)) continue;
        $nums[] = $num;
    }
    $nums = merge($nums);

    $out = [];
    foreach ($nums as $num) {
        $out = array_merge($out, num2net($num));
    }
    foreach ($out as list($net, $cidr)) {
        echo "$net/$cidr\n";
    }
}

function get_nets() {
    global $argv;
    if (isset($argv[1])) {
        array_shift($argv);
        return $argv;
    }
    return explode("\n", file_get_contents('php://stdin'));
}

main(get_nets());
