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

function exclude($from, $to) {
    $cnt_f = count($from);
    $cnt_t = count($to);
    $out = [];
    for ($i = $j = 0; $i < $cnt_f; ++$i) {
        list($start_f, $end_f) = $from[$i];
        while ($j < $cnt_t) {
            list($start_t, $end_t) = $to[$j];
            if ($start_f > $end_t) {
                ++$j;
                continue;
            }
            if ($start_t > $end_f) {
                $out[] = [$start_f, $end_f];
                continue 2;
            }
            if ($start_f >= $start_t) {
                if ($end_f > $end_t) {
                    $start_f = $end_t + 1;
                    ++$j;
                    continue;
                }
                continue 2;
            }
            $out[] = [$start_f, $start_t - 1];
            if ($end_f > $end_t) {
                $start_f = $end_t + 1;
                ++$j;
                continue;
            }
            continue 2;
        }
        $out[] = [$start_f, $end_f];
    }
    return $out;
}

function net2num($net, &$exclude = false) {
    $exclude = false;
    static $pattern = '/^([\+-]?)(\d+\.\d+\.\d+\.\d+)( *[,\/] *((\d+)|(\d+\.\d+\.\d+\.\d+)))?$/';
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
    $ip = ip2long($match[2]);
    if ($ip === false) return 2;

    $cnt = count($match);
    if ($cnt > 3) {
        if ($cnt > 6) {
            $mask = $match[6]; # traditional
            if (!isset($map[$mask])) 3;
            $cidr = $map[$mask];
        } else {
            $cidr = (int)$match[5]; # cidr
            if ($cidr > 32) return 4;
        }
        if (($ip & $dmap[$cidr]) !== $ip) return 5;
    } else $cidr = 32;

    if ($match[1] === '-') $exclude = true;
    return [$ip, $ip + $cmap[$cidr] - 1];
}

function num2net($num) {
    static $map = [];
    if (empty($map)) {
        for ($i = 0; $i <= 32; ++$i) {
            $map[$i] = pow(2, 32 - $i);
        }
    }
    $out = [];
    list($start, $end) = $num;
    while (true) {
        $pos = 0;
        while ($pos < 32) {
            if (($start >> $pos) & 1) break;
            ++$pos;
        }
        $min = 32 - $pos;
        for ($i = 32; $i >= $min; --$i) {
            $tmp = $start + $map[$i] - 1 - $end;
            if ($tmp > 0) {
                $out[] = [long2ip($start), $i + 1];
                $start += $map[$i + 1];
                continue 2;
            } elseif ($tmp === 0) {
                $out[] = [long2ip($start), $i];
                break 2;
            } elseif ($i === $min) {
                $out[] = [long2ip($start), $min];
                $start += $map[$min];
                continue 2;
            }
        }
    }
    return $out;
}

function main($nets) {
    $nums = [];
    $exs = [];
    foreach ($nets as $net) {
        $num = net2num($net, $ex);
        if (!is_array($num)) continue;
        if ($ex) $exs[] = $num;
        else $nums[] = $num;
    }
    $nums = merge($nums);
    $exs = merge($exs);

    if (!empty($exs)) $nums = exclude($nums, $exs);

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
