<?php
// SQLite Configuration
define('SQLITE_FILE', 'db/sigfox.db');

// Kind of measure
define('TYPES', array(
    "temperature" => array("label" => "Température", "unit" => "°C", "key" => "temperature"),
    "humidity" => array("label" => "Humidité", "unit" => "%", "key" => "humidity"),
    "pressure" => array("label" => "Pression", "unit" => "hPa", "key" => "pressure"),
    "magnetic_field" => array("label" => "Champ Magnétique", "unit" => "", "key" => "magnetic_field"),
));

// Name of the sensors
define('ARRAY_CAPT', array(
	"air_temperature" => array("label" => "Température de l'air", "type" => TYPES["temperature"]),
	"air_humidity" => array("label" => "Humidité de l'air", "type" => TYPES["humidity"]),
	"ground_temperature" => array("label" => "Température du sol", "type" => TYPES["temperature"]),
	"ground_humidity" => array("label" => "Humidité du sol", "type" => TYPES["humidity"]),
	"pressure" => array("label" => "Pression", "type" => TYPES["pressure"]),
	"magnetic_field_x" => array("label" => "Champ magnétique X", "type" => TYPES["magnetic_field"]),
	"magnetic_field_y" => array("label" => "Champ magnétique Y", "type" => TYPES["magnetic_field"]),
	"magnetic_field_z" => array("label" => "Champ magnétique Z", "type" => TYPES["magnetic_field"]),
));

// Availible range in the X axis of the chart
define('ARRAY_RANGE', array(
    "all" => array("label" => "Tout le temps", "sql" => ""),
    "month" => array("label" => "Dernier mois", "sql" => "-1 month"),
    "3months" => array("label" => "3 derniers mois", "sql" => "-3 month"),
    "6months" => array("label" => "6 derniers mois", "sql" => "-6 month"),
    "year" => array("label" => "Dernière année", "sql" => "-1 year"),
));

// Default selected range
define('DEFAULT_RANGE', ARRAY_RANGE["month"]);

// Default selected sensor
define('DEFAULT_CAPT', "air_temperature");
?>