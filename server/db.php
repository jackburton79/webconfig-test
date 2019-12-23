<?php

require "config.php";

function db_connect()
{
    global $db_server;
    global $db_username;
    global $db_password;
    global $db_name;
    
    $connection = new PDO("mysql:host=$db_server;dbname=$db_name", $db_username, $db_password);
    // set the PDO error mode to exception
    $connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    return $connection;
}


function db_disconnect(&$connection)
{
    $connection = NULL;
}


function db_empty_db($connection)
{
    $stmt = $connection->prepare("DELETE FROM group_membership");
    $stmt->execute();
    
    $stmt = $connection->prepare("DELETE FROM groups");
    $stmt->execute();
    
    $stmt = $connection->prepare("DELETE FROM host_configuration");
    $stmt->execute();
    
    $stmt = $connection->prepare("DELETE FROM hosts");
    $stmt->execute();
}

?>