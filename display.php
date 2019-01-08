<?php
include_once "includes/connexion.php";

$pdo = connexionDB();

$res_devices = getDevices($pdo);

// Redirect to the default sensor
if(!isset($_GET["capteur"]))
    Header('Location: display.php?capteur='.DEFAULT_CAPT);

?>
<!doctype html>
<html>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
        <title>Affichage des donnees</title>
        <link href="css/bootstrap.min.css" rel="stylesheet">
        <link href="css/style.css" rel="stylesheet">
    </head>
    <body>
        <script src="js/jquery.min.js"></script>
        <script src="js/popper.js"></script>
        <script src="js/moment.js"></script>
        <script src="js/bootstrap.min.js"></script>
        <script src="js/Chart.min.js"></script>
		<!--<script src="js/chart-zoom.min.js"></script>!-->
        <div class="container-fluid">
            <div class="sidebar-sticky">
                <div class="row">
                    <nav class="col-md-2">
                        <h4><span>Données</span></h4>
                        <div id="accordion">
                            <?php
                            // Display the different availible sensors
                            $first = true;
                            foreach(TYPES as $type)
                            {
                                echo '<div class="card">';
                                echo '<div class="card-header" id="heading'.htmlspecialchars($type['key']).'">';
                                echo '<h5 class="mb-0">';
                                echo '<button class="btn btn-link collapsed" data-toggle="collapse" data-target="#collapse'.htmlspecialchars($type['key']).'" aria-expanded="false" aria-controls="collapse'.htmlspecialchars($type['key']).'">';
                                echo $type["label"];
                                echo '</button>';
                                echo '</h5>';
                                echo '</div>';
                                echo '<div id="collapse'.htmlspecialchars($type['key']).'" class="collapse" aria-labelledby="heading'.htmlspecialchars($type['key']).'" data-parent="#accordion">';
                                echo '<div class="card-body">';
                                foreach(ARRAY_CAPT as $key_capt => $capt)
                                {
                                    if($capt["type"] == $type)
                                    {
                                        echo '<a href="?capteur='.htmlspecialchars($key_capt).'">'.$capt['label'].'</a><br/>';
                                    }
                                }
                                echo '</div>';
                                echo '</div>';
                                echo '</div>';
                            }
                            ?>
                        </div>
                    </nav>
                    <main class="col-md-9">
                        <h1 id="main_title" style="text-align:left; float: left;">Capteur 1</h1>
                        <div class="btn-group" style="text-align: right; float:right">
                            <button type="button" class="btn btn-light dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            Exporter
                            </button>
                            <div class="dropdown-menu">
                                <h6 class="dropdown-header">Données affichées</h6>
                                <a class="dropdown-item" href="#" id="link_export_json">En JSON</a>
                                <a class="dropdown-item" href="#" id="link_export_csv">En CSV</a>
                                <h6 class="dropdown-header">Données globales</h6>
                                <a class="dropdown-item" href="api.php?range=all&export=json&device=all" id="link_export_json">En JSON</a>
                                <a class="dropdown-item" href="api.php?range=all&export=csv&device=all" id="link_export_csv">En CSV</a>
                            </div>
                        </div>
                        <hr style="clear: both;">
                        <div>
                            <!-- Chart -->
                            <canvas id="myChart" height="100%"></canvas>
                            <div class="form-inline">
                                <div class="form-group mb-2">
                                    <select class="custom-select" name="choices_range" id="select_range">
                                        <?php
                                        // Display the different options for the range
                                        foreach(ARRAY_RANGE as $key_range => $range)
                                        {
                                            if($key_range == DEFAULT_RANGE)
                                                echo "<option name='".htmlspecialchars($key_range)."' selected='selected'>".htmlspecialchars($range['label'])."</option>";
                                            else
                                                echo "<option name='".htmlspecialchars($key_range)."'>".htmlspecialchars($range['label'])."</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="form-group mb-2">
                                    <select class="custom-select" name="choices_devices" id="select_device" multiple>
                                        <?php
                                        // Display the availible devices
                                        foreach($res_devices as $device)
                                        {
                                            echo "<option name='".htmlspecialchars($device['device_id'])."'>";
                                            if(!(strlen($device['device_name']) == 0))
                                                echo htmlspecialchars($device['device_name'])." (".htmlspecialchars($device['device_id']).")";
                                            else
                                                echo htmlspecialchars($device['device_id']);
                                            echo "</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>
                            <script>
                            $(function() {
								// Reload a chart when we change the range
                                $("#select_range").change(function(){
                                    load_data();
                                });
                                
								// Reload a chart when we change the device
                                $("#select_device").change(function(){
                                    load_data();
                                });
                                
								// French format for the date
                                moment.locale('fr');
								
								// Getting the canvas context
                                var ctx = document.getElementById("myChart").getContext('2d');
                                var datasets = [];
                                var init = true;
                                var colors = [
                                    {backgroundColor: 'rgba(255, 128, 128, 0.3)', borderColor: 'rgba(255, 48, 48, 0.3)'},   // red
                                    {backgroundColor: 'rgba(128, 176, 255, 0.3)', borderColor: 'rgba(77, 148, 255, 0.3)'},  // blue
                                    {backgroundColor: 'rgba(51, 204, 51, 0.3)', borderColor: 'rgba(47, 182, 47, 0.3)'},  // green
                                    {backgroundColor: 'rgba(184, 77, 255, 0.3)', borderColor: 'rgba(214, 153, 255.3)'},  // purple
                                ];
                                var id_next_color = 0;
                                var myChart = false;
                                
								// Load data at the init
                                load_data();
								
								/*
								 * Create the table of requests to with specific parameters
								 */
                                function load_data()
                                {
                                    datasets = [];
                                    let requests = [];
                                    let range = $("#select_range").find(":selected").attr("name");
                                    let current_url = new URL(window.location.href);
                                    let capt = current_url.searchParams.get("capteur");
                                    
									// A request for each device
                                    $("#select_device").find(":selected").each(function(index, elem){
                                        requests.push(createAJAX(capt, range, $(elem).attr('name')));
                                    });
                                    
                                    if(requests.length == 0)
                                        requests.push(createAJAX(capt, range));
                                    // $.when.apply(null, requests).then(function() {
                                        // console.log("end");
                                    // });
                                }
                                
								/*
								 * Create the AJAX Request and call the createChart function
								 */
                                function createAJAX(capt, range, device="")
                                {
                                    let current_url = new URL(window.location.href);
                                    let url = "api.php?capt=" + capt + "&range=" + range;
                                    
                                    if(device != "")
                                        url += "&device=" + device;
                                    
                                    return $.ajax({
                                        url: url,
                                        dataType: "json",
                                        timeout: 10000,
                                        success: function(json) {
                                            // Detect error
                                            if(json.errors.length > 0)
                                            {
                                                alert('Erreurs:\n' + json.errors.join("\n"));
                                                return;
                                            }
                                            
                                            // Convert to Date object
                                            for(let i = 0; i < json.data.data.length; i++)
                                                json.data.data[i].x = new Date(json.data.data[i].x);
                                            
                                            datasets.push(json.data);
                                            createChart();
                                            
                                            $("#link_export_json").attr("href", url + "&export=json");
                                            $("#link_export_csv").attr("href", url + "&export=csv");
                                            $("#collapse" + json.info.type.key).collapse('show');
                                        },
                                        error: function(jqXHR, textStatus, errorThrown) {
                                            console.log("textStatus:", textStatus);
                                            console.log("errorThrown:", errorThrown);
                                        }
                                    });
                                }
                                
								/*
								 * Create the chart object with the configuration
								 */
                                function createChart()
                                {
                                    if(myChart !== false)
                                        myChart.destroy();
                                    
                                    for(let i = 0; i < datasets.length; i++)
                                    {
                                        datasets[i].borderColor = colors[id_next_color].borderColor;
                                        datasets[i].backgroundColor = colors[id_next_color].backgroundColor;
                                        id_next_color = (id_next_color + 1) % colors.length;
                                    }
                                    
                                    myChart = new Chart(ctx, {
                                        type: 'line',
                                        data: {
                                            datasets: datasets
                                        },
                                        options: {
                                            responsive: true,
                                            // maintainAspectRatio: true,
                                            scales: {
                                                xAxes: [{
                                                    type: 'time',
                                                    time: {
                                                        displayFormats: {
                                                            hour: 'MMM D'
                                                        }
                                                    }
                                                }],
                                                yAxes: [{
                                                    ticks: {
                                                        beginAtZero:true
                                                    }
                                                }]
                                            },
                                            // pan: {
                                                // enabled: true,
                                                // mode: 'xy'
                                            // },
                                            // zoom: {
                                                // enabled: true,
                                                // mode: 'xy'
                                            // },
                                            legend: {
                                                display: true,
                                                position: 'bottom'
                                            },
                                            tooltips: {
                                                callbacks: {
                                                    label: function(tooltipItem, data) {
                                                        let point = data.datasets[tooltipItem.datasetIndex].data[tooltipItem.index];
                                                        return tooltipItem.yLabel + point.type.type.unit;
                                                    },
                                                    beforeBody: function(tooltipItems, data) {
                                                        let point = data.datasets[tooltipItems[0].datasetIndex].data[tooltipItems[0].index];
                                                        return tooltipItems[0].xLabel;
                                                    },
                                                    title: function(tooltipItems, data) {
                                                        let point = data.datasets[tooltipItems[0].datasetIndex].data[tooltipItems[0].index];
                                                        if(point.device_name !== null)
                                                            return point.device_name + " (" + point.device_id + ")";
                                                        return point.device_id;
                                                    }
                                                }
                                            }
                                        }
                                    });
                                    
                                    // myChart.resetZoom();
                                    
                                    if(datasets[0].data.length > 0)
                                        $("#main_title").html(datasets[0].data[0].type.label);
                                }
                            });
                            </script>
                        </div>
                    </main>
                </div>
            </div>
        </div>
    </body>
</html>
