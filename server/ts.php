<?php

function ts_build_hosts(array $hosts)
{
    $result = "#Automatically generated file\n";
    $configLine = array();
    for ($i = 0; $i < count($hosts); $i++) {
        $line = $hosts[$i];
        $configLine = $line["name"] . "\t\t\t" . $line["mac_address"];
        $result = $result . "\n" . $configLine;
    }

    return $result;
}


function ts_parse_hosts($lines)
{
    $hosts = array();
    foreach ($lines as $line) {
        if ($line[0] != '#') {
            $line = preg_replace('!\s+!', ' ', $line);
            $rows = array_map(trim, explode(' ', $line));
            $name = $rows[0];
            $mac = $rows[1];
            $host = array("hostname" => $name, "mac_address"=> $mac);
            array_push($hosts, $host);
        }
    }
    
    return $hosts;
}


?>