<?php
include_once 'includes/connexion.php';

/*
 * Convert the $res array to a csv string
 * $delem_elem: the delimiter between the cells
 * $delim_line: the delimiter between the lines
 */
function convert_csv($res, $delim_elem=",", $delim_line="\n"): string
{
    $header_csv = "";
    $content_csv = "";
    $keys = array_keys($res);
    $first = true;
    
    foreach($res as $line)
    {
        foreach($line as $key => $elem)
        {
            if($first)
            {
                $header_csv .= $key.$delim_elem;
            }
            $content_csv .= $elem.$delim_elem;
        }
        $first = false;
        $content_csv .= $delim_line;
    }
    
    return $header_csv.$delim_line.$content_csv;
}

/*
 * Generate the string
 * $get_params: GET parameters of the request
 */
function api($get_params): string
{
	// Store the errors
    $array_final = array('errors' => array());
    
    try
    {
		// If this is to export data or to display in the graph
        if((isset($get_params["capt"]) && (array_key_exists($get_params["capt"], ARRAY_CAPT) !== false))
            || (isset($get_params["export"]) && ($get_params["export"] == "json" || $get_params["export"] == "csv")))
        {
            $pdo = connexionDB();
            
			// Every sensors
            if(!isset($get_params["capt"]))
                $sql = "SELECT data.*, device.device_name FROM data LEFT JOIN device ON data.device_id = device.device_id";
            else
                $sql = "SELECT data.id, ".$get_params["capt"].", time_capture, data.device_id, device.device_name FROM data LEFT JOIN device ON device.device_id = data.device_id";
            $params_sql = array();
            
            // Add where clause if a device has been specified
            if(isset($get_params["device"]))
            {
				// Add where if this is a specific device
                if($get_params["device"] != "all")
                {
                    $found = false;
                    foreach(getDevices($pdo) as $device)
                    {
                        if($device["device_id"] == $device["device_id"])
                        {
                            $found = true;
                            $sql .= " WHERE data.device_id = :device_id";
                            $params_sql["device_id"] = $get_params["device"];
                            break;
                        }
                    }
                    
                    if(!$found)
                    {
                        $array_final['errors'][] = "Invalid device";
                        return json_encode($array_final);
                    }
                }
            }
            else
            {
                // Default last device which sent a message
                $sql .= " WHERE data.device_id = (SELECT `device_id` FROM data ORDER BY id DESC LIMIT 1)";
            }
            
            // Add where clause if a range has to be set
            if(isset($get_params["range"]) && array_key_exists($get_params["range"], ARRAY_RANGE))
                $range_date = ARRAY_RANGE[$get_params["range"]];
            else
                $range_date = DEFAULT_RANGE;
            
            if($range_date["sql"] != "")
            {
                $sql .= " AND time_capture > strftime('%s', datetime('now', :range_date))";
                $params_sql["range_date"] = $range_date["sql"];
            }
            $sql .= " ORDER BY time_capture ASC";
            
            
            // Execute the sql
            $req = $pdo->prepare($sql);
            $req->execute($params_sql);
            $res = $req->fetchAll();
            
            if(isset($get_params["export"]))
            {
				// Select MIME Type for the Header
				switch($get_params["export"])
                {
                    case "json":
                        header('Content-Type: application/json');
                        header('Content-Disposition: attachment; filename="data.json"');
                        $array_final['data'] = $res;
                        break;
                    case "csv":
                        header('Content-Type: text/csv');
                        header('Content-Disposition: attachment; filename="data.csv"');
                        return convert_csv($res);
                        break;
                }
            }
            else
            {
                // JSON for the chart
                $data = array();
                foreach($res as $item)
                    $data[] = array(
                        "x" => date('c', $item["time_capture"]),
                        "y" => (int)$item[$get_params["capt"]],
                        "id" => (int)$item["id"],
                        "device_id" => $item["device_id"],
                        "device_name" => $item["device_name"],
                        "type" => ARRAY_CAPT[$get_params["capt"]]
                    );
                $array_final['data'] = array('data' => $data, 'label' => ARRAY_CAPT[$get_params["capt"]]['label']);
                $array_final['info'] = ARRAY_CAPT[$get_params["capt"]];
            }
        }
        else
        {
            $array_final['errors'][] = "Invalid parameters";
        }
    }
    catch(Exception $e)
    {
        $array_final['errors'][] = "Unable to connect to the DB";
    }
    return json_encode($array_final);
}

echo api($_GET);
?>
