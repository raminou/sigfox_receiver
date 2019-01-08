<?php
include_once "configuration.php";

/*
 * Connect to the database
 * Return the PDO Object
 */
function connexionDB(): PDO
{
	try{
		$pdo = new PDO('sqlite:'.SQLITE_FILE);
		$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
		$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); // ERRMODE_WARNING | ERRMODE_EXCEPTION | ERRMODE_SILENT
		
		return $pdo;
	} catch(Exception $e) {
		echo "Impossible d'accéder à la base de données SQLite : ".$e->getMessage();
		die();
	}
}

/*
 * Request the distinct devices registred in the database
 * Return an Array {"device_id" => device_id, "device_name" => device_name}
 */
function getDevices($pdo): array
{
    $req = $pdo->query("SELECT DISTINCT data.device_id, device.device_name FROM data LEFT JOIN device ON data.device_id = device.device_id");
    return $req->fetchAll();
}
?>