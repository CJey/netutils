<?php

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

if (!isset($argv[2])) {
    echo "Usage: php ${argv[0]} <ip_start> <ip_end>\n";
    exit;
}

$ip_start = ip2long($argv[1]);
$ip_end = ip2long($argv[2]);

if ($ip_start === false or $ip_end === false) {
    if ($ip_start === false) echo "ERROR: Invalid <ip_start>: ${argv[1]}\n";
    if ($ip_end === false) echo "ERROR: Invalid <ip_end>: ${argv[2]}\n";
    exit(1);
}

if ($ip_end < $ip_start) {
    echo "ERROR: <ip_end> should not less than <ip_start>\n";
    exit(1);
}

$out = num2net([$ip_start, $ip_end]);

foreach ($out as list($net, $cidr)) {
    echo "$net/$cidr\n";
}
