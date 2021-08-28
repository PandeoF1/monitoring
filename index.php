<?php
require "conf/mysql.conf.php";

$db_conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);


?>

<!DOCTYPE HTML>
<HTML>

<script src="js/jquery.min.js"></script>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" name="viewport" content="width=device-width, initial-scale=1">
<link href="style/stats.css" rel="stylesheet" type="text/css">
<script src="js/canvasjs.min.js"></script>
<?php

$dates = new DateTime();
$date = $dates->format("d/m/Y H:i:s");
if (isset($_POST["select_machine"])) {
    $host = $_POST["select_machine"];
    $query_result = mysqli_query($db_conn, "SELECT * FROM `log_$host` ORDER BY `id` DESC LIMIT 1");
    while ($row = mysqli_fetch_row($query_result)) {
        $date_last = strtotime($row[1]);
        $date_diff = strtotime($date) - $date_last;
    }
    $query_result = mysqli_query($db_conn, "SELECT * FROM `log_$host` ORDER BY `id` DESC LIMIT 41000");
    $temp_query_result = $query_result;
    $cpu_usage = array();
    $ram_usage = array();
    $disk_usage = array();
    $disk_usage_total = array();
    while ($row = mysqli_fetch_row($temp_query_result)) { //Tout
        $items[] = $row;
    }
    $items = array_reverse($items, true);
    foreach ($items as $query_row) {
        // CPU //
        $cpu_explode = explode("%", $query_row[2]);
        foreach ($cpu_explode as $value) {
            $cpu_explode_ = explode("|", $value);
            if ($cpu_explode_[1] == 0) $cpu_usage[$cpu_explode_[0]][] = array("y" => 1, "label" => $query_row[1]);
            else $cpu_usage[$cpu_explode_[0]][] = array("y" => $cpu_explode_[1], "label" => $query_row[1]);
        }
        // CPU //
        // RAM //
        $ram_explode = explode("|", $query_row[3]);
        $ram_max = $ram_explode[0];
        $ram_usage[] = array("y" => round(($ram_max - $ram_explode[1]) / 1000000, "2"), "label" => $query_row[1]);
        $ram_usage_total[] = array("y" => round(($ram_explode[1]) / 1000000, "2"), "label" => $query_row[1]);
        // RAM //
        // Network //
        $network_explode = explode("|", $query_row[5]);
        $network_in_usage[] = array("y" => round($network_explode[0], "2"), "label" => $query_row[1]);
        $network_out_usage[] = array("y" => round($network_explode[1], "2"), "label" => $query_row[1]);
        // Network //
        // Disk //
        $disk_explode = explode("%", $query_row[4]);
        $i = 0;
        $disk_list = "";
        foreach ($disk_explode as $value) {
            $disk_explode_ = explode("|", $value);
            $disk_hs = strpos($disk_list, "| " . $disk_explode_[0] . " |");
            if ($disk_hs !== false) {
            } else {
                $disk_list .= "| " . $disk_explode_[0] . " |";
            }
            $disk_usage[$disk_explode_[0]][] = array("y" => 100 - round(($disk_explode_[1] / $disk_explode_[2]) * 100, "2"), "label" => $query_row[1]);
            $disk_usage_total[$disk_explode_[0]][] = array("y" => round(($disk_explode_[1] / $disk_explode_[2]) * 100, "2"), "label" => $query_row[1]);
            $i++;
        }

        // Disk //
    }
    if ($date_diff <= 60) {


?>

        <body>
        <?php } else { ?>

            <body style="background-color:red;">

            <?php } ?>
            <div class="graph_title">
                <form action="" method="POST">
                    <button type="submit" class="back_button"><img class="img_back" src="img/back.png" /></button>
                </form>
                <h1 style="color: white;">Statistiques pour <?php
                                                            $query_result_ = mysqli_query($db_conn, "SELECT * FROM `hosts` WHERE `uuid` = '$host'");

                                                            while ($query_row_ = mysqli_fetch_row($query_result_)) {
                                                                echo  $query_row_[1] . " (" . $query_row_[2] . ") :";
                                                            } ?>
                    <form action="" method="POST">
                        <button type="submit" name="select_machine" value="<?php echo $host; ?>" class="refresh_button"><img class="img_refresh" src="img/refresh.png" /></button>
                    </form>
            </div>
            <div class="graph graph1">
                <h1>Pourcentages du CPU :</h1>
                <div id="chart_cpu" class="chart chartcpu"></div>
            </div>
            <div class="graph graph2">
                <h1>Utilisation de la RAM :</h1>
                <div id="chart_ram" class="chart chartram"></div>
            </div>
            <br><br>
            <div class="graph graph1">
                <h1>Utilisation du réseau :</h1>
                <div id="chart_network" class="chart chartnetwork"></div>
            </div>

            <?php
            $i = 0;
            $i_ = 2;
            foreach ($disk_usage as $name => $test) {

                if ($name != "") {
                    $disk_hs = strpos($disk_list, "| " . $name . " |");
                    if ($disk_hs !== false) {
            ?>

                        <div class="graph graph<?php echo $i_; ?>">
                        <?php } else {
                        ?>
                            <div style="border: 10px solid; border-color: red;" class="graph graph<?php echo $i_; ?>">
                            <?php }

                        if ($i_ == 1) $i_ = 2;
                        else $i_ = 1;
                            ?>
                            <h1>Utilisation du disque (<?php echo $name; ?>) :</h1>
                            <div id="chart_disk_<?php echo $i; ?>" class="chart chartdisk"></div>

                            </div>
                    <?php if ($i_ == 1) echo "<br><br>";
                }
                $i++;
            } ?>
                    <script>
                        var chart_cpu = new CanvasJS.Chart("chart_cpu", {
                            theme: "dark2",
                            zoomEnabled: true,
                            exportEnabled: true,
                            animationEnabled: true,
                            //animationDuration: 1000,
                            stripLines: [{
                                value: 50,
                                label: "Average"
                            }],
                            toolTip: {
                                shared: true
                            },
                            title: {
                                //text: "(Total)"
                            },
                            axisY: {
                                suffix: " %"
                            },
                            axisX: {
                                //Color: "red",
                            },
                            legend: {
                                cursor: "pointer",
                                fontSize: 16,
                                itemclick: toggleDataSeries
                            },
                            <?php
                            echo "data: [";
                            foreach ($cpu_usage as $i => $value) {
                                if ($i != "" || $i == "0") {
                                    echo "{";
                                    if ($i == "_Total" && $i != "0") {
                                        $i = "Total ";
                                        echo "color: 'rgb(209,209,209)',";
                                    } else {
                                        echo "color: 'rgb(150,255,255)',";
                                        echo "visible: false,";
                                    }
                                    echo "type: 'area',";
                                    echo "name: '$i',";
                                    echo "showInLegend: true,";
                                    echo "dataPoints:";
                                    echo json_encode($value, JSON_NUMERIC_CHECK);
                                    echo "},\n";
                                }
                            }
                            echo "]";
                            ?>
                        });
                        chart_cpu.render();
                        var chart_ram = new CanvasJS.Chart("chart_ram", { //Faire la max value
                            theme: "dark2",
                            zoomEnabled: true,
                            exportEnabled: true,
                            animationEnabled: true,
                            toolTip: {
                                shared: true
                            },
                            title: {
                                //text: "(RAM Utiliser)"
                            },
                            axisY: {
                                suffix: " %"
                            },
                            axisX: {
                                //Color: "red",
                            },
                            <?php
                            echo "data: [";
                            echo "{";
                            echo "color: 'rgb(209,209,209)',";
                            echo "type: 'stackedArea100',";
                            //echo "yValueFormatString: '#, Go',";
                            echo "name: 'Used',";
                            //echo "name: 'ram',";
                            echo "dataPoints:";
                            echo json_encode($ram_usage, JSON_NUMERIC_CHECK);
                            echo "},\n";
                            echo "{";
                            echo "color: 'rgb(0,255,0)',";
                            echo "type: 'stackedArea100',";
                            //echo "yValueFormatString: '#, Go',";
                            echo "name: 'Free',";
                            //echo "name: 'ram',";
                            echo "dataPoints:";
                            echo json_encode($ram_usage_total, JSON_NUMERIC_CHECK);
                            echo "},\n";
                            echo "]";
                            ?>
                        });
                        chart_ram.render();

                        var chart_network = new CanvasJS.Chart("chart_network", { //Faire la max value
                            theme: "dark2",
                            zoomEnabled: true,
                            exportEnabled: true,
                            animationEnabled: true,
                            toolTip: {
                                shared: true
                            },
                            title: {
                                //text: "(RAM Utiliser)"
                            },
                            axisY: {
                                suffix: " Kb/s"
                            },
                            axisX: {
                                //Color: "red",
                            },
                            <?php
                            echo "data: [";
                            echo "{";
                            echo "color: 'rgb(209,209,209)',";
                            echo "type: 'area',";
                            echo "name: 'In',";
                            echo "dataPoints:";
                            echo json_encode($network_in_usage, JSON_NUMERIC_CHECK);
                            echo "},\n";
                            echo "{";
                            echo "color: 'rgb(150,255,255)',";
                            echo "type: 'area',";
                            echo "name: 'Out',";
                            echo "dataPoints:";
                            echo json_encode($network_out_usage, JSON_NUMERIC_CHECK);
                            echo "},\n";

                            echo "]";
                            ?>
                        });
                        chart_network.render();
                        <?php
                        $ii = 0;
                        foreach ($disk_usage as $i => $value) {
                            if ($i != "") {
                        ?>
                                var chart_disk = new CanvasJS.Chart("chart_disk_<?php echo $ii; ?>", { //Faire la max value
                                    theme: "dark2",
                                    zoomEnabled: true,
                                    exportEnabled: true,
                                    animationEnabled: true,
                                    toolTip: {
                                        shared: true
                                    },
                                    title: {
                                        //text: "<?php echo $i; ?>"
                                    },
                                    axisY: {
                                        suffix: " %"
                                    },
                                    axisX: {
                                        //Color: "red",
                                    },
                                    <?php
                                    echo "data: [";
                                    echo "{";
                                    echo "color: 'rgb(209,209,209)',";
                                    echo "type: 'stackedArea100',";
                                    echo "name: 'Used',";
                                    echo "dataPoints:";
                                    echo json_encode($value, JSON_NUMERIC_CHECK);
                                    echo "},\n";
                                    echo "{";
                                    echo "color: 'rgb(0,255,0)',";
                                    echo "type: 'stackedArea100',";
                                    echo "name: 'Free',";
                                    echo "dataPoints:";
                                    echo json_encode($disk_usage_total[$i], JSON_NUMERIC_CHECK);
                                    echo "},\n";
                                    echo "]";
                                    ?>
                                });
                                chart_disk.render();
                        <?php
                            }
                            $ii++;
                        }
                        ?>

                        function toggleDataSeries(e) {
                            if (typeof(e.dataSeries.visible) === "undefined" || e.dataSeries.visible) {
                                e.dataSeries.visible = false;
                            } else {
                                e.dataSeries.visible = true;
                            }
                            chart_cpu.render();
                        }
                    </script>
                <?php
            } else {

                ?>

                    <div class="search_page">
                        <h1>Page de statistiques :</h1>
                        <br><br>
                        <h3>Sélectionner la machine :</h3>
                        <br>
                        <div class="search_machine">
                            <form action="" method="POST">
                                <select class="select_machine" name="select_machine" required>
                                    <option selected disabled value='tous'>----------</option>
                                    <?php
                                    $query_result = mysqli_query($db_conn, "SELECT * FROM `hosts`");
                                    $cpt = 0;
                                    while ($query_row = mysqli_fetch_row($query_result)) {
                                        $query_row[1];
                                        echo "<option value='$query_row[0]'>$query_row[1] | $query_row[2]</option>";
                                    } ?>
                                </select>
                        </div>
                        <div class="search_button"><br>
                            <button type="submit" class="button_search" name="button_search">Rechercher</button>
                        </div>
                        </form>
                    </div>
                <?php } ?>
            </body>


</HTML>