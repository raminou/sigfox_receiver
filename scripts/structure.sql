CREATE TABLE IF NOT EXISTS `data` (
	`id`	INTEGER PRIMARY KEY AUTOINCREMENT,
	`air_temperature`	REAL,
	`air_humidity`	INTEGER,
	`ground_temperature`	REAL,
	`ground_humidity`	INTEGER,
	`pressure`	REAL,
	`magnetic_field_x`	REAL,
	`magnetic_field_y`	REAL,
	`magnetic_field_z`	REAL,
	`time`	INTEGER,
	`device_id`	INTEGER
);

CREATE TABLE IF NOT EXISTS `device` (
	`id`	INTEGER PRIMARY KEY AUTOINCREMENT,
	`device_id`	TEXT,
	`device_name`	TEXT
);