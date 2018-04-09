<?php
$config = array(
"xml_location" => "./ns-stations.xml", // Location of the XML file which is outputted by http://webservices.ns.nl/ns-api-stations-v2
"db_location" => "./ns-stations.db"
);

$db_schema = <<<EOF
CREATE TABLE nsstations (
"stationcode" VARCHAR(8),
"stationname" VARCHAR(255),
"station_type" VARCHAR(64),
PRIMARY KEY("stationcode")
);
EOF;
// The database schema.

$db = new PDO("sqlite:" . $config['db_location']);
$xml = new SimpleXMLElement(file_get_contents($config['xml_location'])); // Create a PDO and SimpleXMLElement class to interact with the database and the XML file.

$dropquery = $db->prepare("DROP TABLE IF EXISTS nsstations;");
$dropquery->execute();
$createquery = $db->prepare($db_schema);
$createquery->execute(); // Clears the database. Comment this if you don't want to clear the table. I can't see why you'd want that, though.

$insertquery = $db->prepare("INSERT INTO nsstations VALUES(:stationcode, :stationname, :station_type)"); // Prepare the SQL statement. A prepared statement can be reused infinitely, so we're going to make use of this so we don't need to create a prepared statement for every train station.

foreach($xml as $k => $v) {
    if($v->Land != "NL") {
        continue; // Station is niet in Nederland, negeren.
    }
    $data = array(); // the variables we will be passing onto $query->execute();

    $data['stationcode'] = (string) $v->Code;
    $data['stationname'] = (string) $v->Namen->Lang; // The XML data has 3 names, a short one, a middle-length one, and a long one. There's currently no need for short names.
    $data['station_type'] = (string) $v->Type; // The fact that SimpleXML requires every fucking thing to be casted to a string even when requesting A INDIVIDUAL VALUE is slightly crappy imo. But it's PHP, so that's just the way it is.
    // TODO: Misschien ook toevoegen dat de Friese stationsnamen er ook in worden gezet, die staan er als alias in en we willen niet de 10 mensen die Fries spreken tegen het been stoten /sarcasm off
    $insertquery->execute($data);
}
