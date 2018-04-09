<?php
if(!file_exists('./config.php')) { die('Config.php is missing'); } // Check if the config exists, if not, exit
include './config.php';
if(!file_exists($config['stations_location'])) { die('Stations DB doesn\'t exist'); } // Now that we've included the config, check if the database file exists, if not, exit
include './NSApi.php';

$nsapi = new NSApi($config['ns_api_username'], $config['ns_api_password']);
$db = new PDO("sqlite:" . $config['stations_location']); // Create instances of NSApi and PDO to interact with the API and the database

$stationsrequest = <<<EOF
SELECT * FROM nsstations WHERE station_type = "intercitystation" OR station_type = "knooppuntIntercitystation" OR station_type = "megastation" AND stationcode IS NOT "UT";
EOF;

// Dit was een klein beetje verwarrend omdat de NS geen drietreinenstelsel meer voert, maar sneltreinstation en varianten daarop zijn ook echt stations waar sneltreinen stoppen (van andere vervoerders dus want de NS voert die naam al een tijd niet meer)
// This is what you need to change if you want to use different options. I.e. use all stations, ignore some stations, whatever. As long as the query returns the same rows as are in the schema, it won't really care.

$stmt = $db->prepare($stationsrequest);
$stmt->execute();
$stations = $stmt->fetchAll(PDO::FETCH_ASSOC); // Get all the stations matching the query and place them into a array.

$finishedCount = 0;
$reisadviesCount = 0;
$stationCount = count($stations); // For showing statistics on terminal

$finalData = array(); // create an array that will store the amount of changes for each trip advice and some station metadata for geojson generation

foreach($stations as $station) {
    $datetime = new DateTime();
    $datetime->setTimestamp($config['peilpunt']);
    $reisadvies = $nsapi->treinplanner($config['from_station'], $station['stationcode'], '', 0, 5, $datetime); // get the trip advices

    $currentStation = array("zeroChanges" => 0, "oneOrMoreChanges" => 0, "changeCounts" => array(), "stationMeta" => array("name" => $station['stationname'], "code" => $station['stationcode'], "lat" => $station['station_lat'], "lon" => $station['station_lon']));

    if(!isset($reisadvies->ReisMogelijkheid)) {
        continue;
    }

    foreach($reisadvies->ReisMogelijkheid as $reisMogelijkheid) {
        if($reisMogelijkheid->AantalOverstappen == 0) {
            $currentStation['zeroChanges']++;
        } else {
            $currentStation['oneOrMoreChanges']++;
            if(array_key_exists($reisMogelijkheid->AantalOverstappen, $currentStation['changeCounts'])) {
                $currentStation['changeCounts'][$reisMogelijkheid->AantalOverstappen]++;
            } else {
                $currentStation['changeCounts'][$reisMogelijkheid->AantalOverstappen] = 1;
            }
        }
        $finalData[$station['stationcode']] = $currentStation;
        $reisadviesCount++;
    }
    $finishedCount++;
    echo("\r" . $finishedCount . " van " . $stationCount . " klaar, " . $reisadviesCount . " reisadviezen verwerkt");

}
var_dump($finalData);
echo json_encode($finalData);
// NOT FINISHED.
// Todo: convert $finalData to geoJSON.
