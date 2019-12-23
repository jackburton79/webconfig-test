<?php

function get_temp_dir()
{
    return $ini_val ? $ini_val : sys_get_temp_dir();
}


function normalize_mac_address($macAddress)
{
    $symbols = array();
    $symbols = array_merge($symbols, range('a', 'f'));
    $symbols = array_merge($symbols, range('A', 'F'));
    $symbols = array_merge($symbols, range('0', '9'));
    $macAddress = trim($macAddress);
    $macAddress = preg_replace("/[^" . preg_quote(implode('', $symbols), '/') . "]/i", "", $macAddress);
    
    return strtolower($macAddress);
}


function normalize_name($name)
{
    $symbols = array();
    $symbols = array_merge($symbols, range('a', 'z'));
    $symbols = array_merge($symbols, range('A', 'Z'));
    $symbols = array_merge($symbols, range('0', '9'));
    $symbols = array_merge($symbols, array('-', '_'));
    
    $name = trim($name);
    $name = preg_replace("/[^" . preg_quote(implode('', $symbols), '/') . "]/i", "", $name);
    
    return $name;
}


?>