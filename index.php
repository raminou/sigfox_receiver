<?php
date_default_timezone_set("Europe/Paris");

function unsigned_to_signed($data, int $size): int
{
	if(($data >> ($size-1)) & 0x1 == 1)
	{
		$res = 0;
		for($i=0; $i < $size-1; $i++)
		{
			// $res += ((($data >> $i) & 0x1) ^ 0x1) * pow(2, $i);
			$res |= ((($data >> $i) & 0x1) ^ 0x1) << $i;
		}
			
		return -($res+1);
	}
	else
		return $data;
}

try{
	$pdo = new PDO('sqlite:db/sigfox.db');
	$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
	$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); // ERRMODE_WARNING | ERRMODE_EXCEPTION | ERRMODE_SILENT
} catch(Exception $e) {
	echo "Impossible d'accéder à la base de données SQLite : ".$e->getMessage();
	die();
}

if(isset($_GET["display"]))
{
	$res = $pdo->query("SELECT * FROM data ORDER BY id DESC LIMIT 50");
	echo "<table>";
	echo "<tr><th>Temperature de l'air</th><th>Humidité de l'air</th><th>Température du sol</th><th>Humidité du sol</th><th>Pressure</th><th>Champ Magnétique X</th><th>Champ Magnetique Y</th><th>Champ Magnetique Z</th><th>Temps</th></tr>";
	foreach($res->fetchAll() as $item)
	{
		echo "<tr>";
		echo "<td>".$item["air_temperature"]."</td>";
		echo "<td>".$item["air_humidity"]."</td>";
		echo "<td>".$item["ground_temperature"]."</td>";
		echo "<td>".$item["ground_humidity"]."</td>";
		echo "<td>".$item["pressure"]."</td>";
		echo "<td>".$item["magnetic_field_x"]."</td>";
		echo "<td>".$item["magnetic_field_y"]."</td>";
		echo "<td>".$item["magnetic_field_z"]."</td>";
		echo "<td>".date("H:i d/m/Y", $item["time"])."</td>";
		echo "</tr>";
	}
	echo "</table>";
}
elseif(isset($_GET["data"]) && isset($_GET["data2"]) && isset($_GET["id"]) && isset($_GET["time"]))
{
	echo "request received<br/>";
	$data = $_GET["data"];	// $data sur 64 bits
	$data2 = $_GET["data2"]; // $data2 sur 32 bits

	// Signed Ground temperature 10 bits
	$ground_temperature = unsigned_to_signed(($data >> (64-10)) / 10, 10);

	// Signed Air temperature 10 bits
	$air_temperature = unsigned_to_signed((($data >> (64-20)) & 0x3ff) / 10, 10);

	// Unsigned Ground humidity 7 bits
	$ground_humidity = ($data >> (64-27)) & 0x7f;

	// Unsigned Air humidity 7 bits
	$air_humidity = ($data >> (64 - 34)) & 0x7f;

	// Unsigned Pressure 17 bits
	$pressure = (($data >> (64 - 51)) & 0x1ffff)/ 100;

	// Signed Magnetic field X 14 bits
	$magnetic_field_x = unsigned_to_signed((($data & 0x1fff) << 1) | ($data2 >> (32 - 1)), 14);

	// Signed Magnetic field Y 14 bits
	$magnetic_field_y = unsigned_to_signed(($data2 >> (32 - 15)) & 0x3fff, 14);

	// Signed Magnetic field Z 14 bits
	$magnetic_field_z = unsigned_to_signed(($data2 >> (32 - 29)) & 0x3fff, 14);

	// Status X
	$status_x = ($data2 >> 2) & 0x1;

	// Status Y
	$status_y = ($data2 >> 1) & 0x1;

	// Status Z
	$status_z = $data2 & 0x1;

	echo "Ground temperature $ground_temperature<br/>Air temperature $air_temperature<br/>Ground humidity $ground_humidity<br/>Air humidity $air_humidity<br/>Pressure $pressure<br/>Magnetic X $magnetic_field_x<br/>Magnetic Y $magnetic_field_y<br/>Magnetic Z $magnetic_field_z<br/>";
	
	/*$ground_temperature = 0;
	$ground_humidity = 0;
	$pressure = 0;
	*/
	$magnetic_field = 0;

	$req = $pdo->prepare("INSERT INTO data (air_temperature, air_humidity, ground_temperature, ground_humidity, pressure, magnetic_field_x, magnetic_field_y, magnetic_field_z, time, device_id) VALUES (:air_temperature, :air_humidity, :ground_temperature, :ground_humidity, :pressure, :magnetic_field_x, :magnetic_field_y, :magnetic_field_z, :time, :device_id)");
	$req->execute(array(
		'air_temperature' => $air_temperature,
		'air_humidity' => $air_humidity,
		'ground_temperature' => $ground_temperature,
		'ground_humidity' => $ground_humidity,
		'pressure' => $pressure,
		'magnetic_field_x' => $magnetic_field_x,
		'magnetic_field_y' => $magnetic_field_y,
		'magnetic_field_z' => $magnetic_field_z,
		'time' => $_GET["time"],
		'device_id' => $_GET["id"]
	));
	echo "SAVED";
}
echo "<a href='index.php?display=true'>Affichage</a>";
?>
