<?php

require "db.php";
require "config.php";
require "support.php";
require "ts.php";


function handle_get()
{
try {
    switch ($_GET["action"]) {
        default:
            throw new Exception("unknown GET request");
            break;
    }
} catch(Exception $e) {
    $result = array('type' => 'error', 'message' => $e->getMessage());
    header('HTTP/1.1 400 Bad Request');
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode($result);
}

};


function db_add_host($dbConnection, $hostName, $macAddress)
{
    $mac = normalize_mac_address($macAddress);
    $name = normalize_name($hostName);
    $query = "INSERT INTO hosts (id, mac_address, name) VALUES (0 , :mac,  :name)";
    $stmt = $dbConnection->prepare($query);
    $stmt->bindParam(':mac', $mac, PDO::PARAM_STR);
    $stmt->bindParam(':name', $name, PDO::PARAM_STR);
    $stmt->execute();
}


function db_delete_host($dbConnection, $hostName, $macAddress)
{
    $mac = normalize_mac_address($macAddress);
    $name = normalize_name($hostName);
    $query = "DELETE FROM hosts WHERE name = :name AND mac_address = :mac";
    $stmt = $dbConnection->prepare($query);
    $stmt->bindParam(':mac', $mac, PDO::PARAM_STR);
    $stmt->bindParam(':name', $name, PDO::PARAM_STR);
    $stmt->execute();    
}


function db_add_host_to_group($dbConnection, $hostname, $groupName)
{
    $name = normalize_name($hostname);
    $group = $groupName;
    $query = "INSERT INTO group_membership (host_id, group_id) SELECT * from" .
            "(SELECT h.id FROM hosts h WHERE h.name = :name) as host_id," .
            "(SELECT g.id FROM groups g WHERE g.name = :group) as group_id";
   
    $stmt = $dbConnection->prepare($query);
    $stmt->bindParam(':name', $name, PDO::PARAM_STR);
    $stmt->bindParam(':group', $group, PDO::PARAM_STR);
    $stmt->execute();
}


function db_remove_host_from_group($dbConnection, $hostname, $groupName)
{
    $name = normalize_name($hostname);
    $query = "DELETE m FROM group_membership m ".
            "INNER JOIN hosts h ON h.id = m.host_id " .
            "INNER JOIN groups g ON g.id = m.group_id ".
            "WHERE h.name = :name";
    error_log($query);
    $stmt = $dbConnection->prepare($query);
    $stmt->bindParam(':name', $name, PDO::PARAM_STR);
    $stmt->execute();
}


function db_get_host_configuration($dbConnection, $hostname)
{
    error_log("retrieving custom config for $hostname:");
    if (strtolower($hostname) == "default") {
        $stmt = $dbConnection->prepare("SELECT 'default' as name, configuration FROM global_configuration");
    } else {
        $stmt = $dbConnection->prepare("SELECT h.name, configuration FROM hosts h LEFT JOIN " .
                        "host_configuration c on h.id = c.host_id WHERE h.name = :name");
        $stmt->bindParam(':name', $hostname, PDO::PARAM_STR);
    }
    
    $stmt->execute();
    $table_data = $stmt->fetchall(PDO::FETCH_ASSOC);
    header("content-type:application/json");
    echo json_encode($table_data);
}


function db_save_host_configuration($dbConnection, $hostname, $configurationText)
{
    error_log("saving custom config for $hostname: $configurationText");
    if (strtolower($hostname) == "default") {
        $stmt = $dbConnection->prepare("REPLACE INTO global_configuration SET configuration = :configuration");
        $stmt->bindParam(':configuration', $configurationText, PDO::PARAM_STR);
    } else {
        $stmt = $dbConnection->prepare("REPLACE INTO host_configuration (host_id, configuration) " .
                "SELECT h.id, :configuration FROM hosts h where h.name = :hostname");
        $stmt->bindParam(':configuration', $configurationText, PDO::PARAM_STR);
        $stmt->bindParam(':hostname', $hostname, PDO::PARAM_STR);
    }
        
    //error_log($query);
    $stmt->execute();
    //header("content-type:application/json");
    echo 200;
}


function write_config($dbConnection, $path)
{
    global $ts_basename;
    
    $stmt = $dbConnection->prepare("SELECT name, mac_address FROM hosts");
    $stmt->execute();
    $hostlist = $stmt->fetchall(PDO::FETCH_ASSOC);
    $configuration = ts_build_hosts($hostlist);
    $fileName = $path . "$ts_basename.hosts";
    if (@file_put_contents($fileName, $configuration) === FALSE) {
        throw new Exception("Cannot write configuration ($fileName)");
    }
    
    // Specific host configuration
    $stmt = $dbConnection->prepare("SELECT h.name, c.configuration " .
                                "FROM hosts h INNER JOIN host_configuration c " .
                                "on h.id = c.host_id");
    $stmt->execute();
    $rows = $stmt->fetchall(PDO::FETCH_ASSOC);
    foreach ($rows as $row) {
        $fileName = $path . "$ts_basename.conf-" . $row['name'];
        $configuration = $row['configuration'];
        if (@file_put_contents($fileName, $configuration) === FALSE) {
            throw new Exception("Cannot write host configuration ($fileName)");
        }
    }
    
    // TODO: Group Configuration
    
    // Default configuration
    $stmt = $dbConnection->prepare("SELECT configuration FROM global_configuration");
    $stmt->execute();
    $hostlist = $stmt->fetchall(PDO::FETCH_ASSOC);
    $fileName = $path . "$ts_basename.conf.network";
    if (@file_put_contents($fileName, $configuration) === FALSE) {
        throw new Exception("Cannot write default configuration ($fileName)");
    }
}


function import_config($dbConnection, $path)
{
    global $ts_basename;
    
    $fileName = $path . "$ts_basename.hosts";
    
    $configuration = file($fileName, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $hosts = ts_parse_hosts($configuration);
    
    db_empty_db($dbConnection);
    
    $errors = array();
    foreach($hosts as $lines) {
        $name = $lines["hostname"];
        $mac = $lines["mac_address"];
        // TODO: Handle groups
        try {
            db_add_host($dbConnection, $name, $mac);
            // Add specific host configuration
            $fileName = $path . $ts_basename . ".conf-" . $name;
            $configuration = @file_get_contents($fileName);
            if ($configuration != NULL) {
                db_save_host_configuration($dbConnection, $name, $configuration);
            }
            
        } catch (Exception $e) {
            // TODO: Report errors to client.
            // Could happen if there are duplicated MAC or hostnames
            array_push($errors, $e->getMessage());
        }
    }
}


function handle_post()
{
    global $ts_path;
    global $ts_basename;
    
try {
    $conn = db_connect();

    switch ($_POST["action"]) {
        case "gethosts":
            $stmt = $conn->prepare("SELECT mac_address, name FROM hosts");
            $stmt->execute();
            $table_data = $stmt->fetchall(PDO::FETCH_ASSOC);
            header("content-type:application/json");
            echo json_encode($table_data);
            break;
            
        case "getgroups":
            $stmt = $conn->prepare("SELECT id, name FROM groups");
            $stmt->execute();

            $table_data = $stmt->fetchall(PDO::FETCH_ASSOC);
    
            header("content-type:application/json");
            echo json_encode($table_data);
            break;    
            
        case "addhost":
            db_add_host($conn, $_POST["name"], $_POST["mac"]);
            break;
            
        case "deletehost":
            db_delete_host($conn, $_POST["name"], $_POST["mac"]);
            break;
        
        case "addhosttogroup":
            db_add_host_to_group($conn, $_POST["name"], $_POST["group"]);
            break;
        
        case "removehostfromgroup":
            db_remove_host_from_group($conn, $_POST["name"], $_POST["group"]);
            break;
            
        case "importconfig":
            import_config($conn, $ts_path);
            break;
            
        case "writeconfig":
            write_config($conn, $ts_path);
            break;
            
        case "downloadfullconfiguration":
            $ini_val = ini_get('upload_tmp_dir');
            $tmpDir = get_temp_dir();
            write_config($conn, $tmpDir . '/');
            
            $zip = new ZipArchive();
            $archiveFileName = $ts_basename . '.conf.zip';
            $archiveFilePath = $tmpDir . '/' . $archiveFileName;
            if ($zip->open($archiveFilePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
                throw new Exception('Cannot create configuration archive.');
            }
        
            $file_names = array();
            array_push($file_names, $fileName);
            //add each files of $file_name array to archive
            foreach($file_names as $files) {
                error_log($files);
                $zip->addFile($files, "thinstation/" . pathinfo($files, PATHINFO_BASENAME));
            }
            $zip->close();
            
            ob_clean();
            flush();
            
            $fileSize = filesize("/tmp/$archiveFileName");
            
            // TODO: How to send the file name ?
            // Client treats this as binary data
            readfile($archiveFilePath);
            
            break;
        case 'getcustomconfig':
            $hostname = normalize_name($_POST["name"]);
            db_get_host_configuration($conn, $hostname);
            break;
            
        case 'savecustomconfig':
            $hostname = normalize_name($_POST["name"]);
            $configurationText = $_POST["configuration"];
            db_save_host_configuration($conn, $hostname, $configurationText);
            break;
            
        default:
            throw new Exception("unknown POST request");
            break;
        
    }
} catch(Exception $e) {
    $result = array('type' => 'error', 'message' => $e->getMessage());
    header('HTTP/1.1 400 Bad Request');
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode($result);
}

   db_disconnect($conn);
}


error_log("Server Received Request " . $_SERVER['REQUEST_URI'] . " " .
    $_SERVER['QUERY_STRING'] . "(" . $_SERVER['REQUEST_METHOD'] . ")");
switch ($_SERVER['REQUEST_METHOD']) {
    case 'POST':
        handle_post();
        break;
    case 'GET':
        handle_get();
        break;
    default:
        error_log("bad request");
        break;
    
}

?>
