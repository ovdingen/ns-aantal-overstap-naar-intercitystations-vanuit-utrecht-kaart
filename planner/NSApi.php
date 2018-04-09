<?php
// Een basale class voor de NS API.
class NSApi {
    public function __construct(string $apiUsername, string $apiPassword, string $apiUrl = "https://webservices.ns.nl/") {
        $this->apiUsername = $apiUsername;
        $this->apiPassword = $apiPassword;
        $this->apiUrl = $apiUrl;
    }

    private function getUsername() {
        return $this->apiUsername;
    }
    private function getPassword() {
        return $this->apiPassword;
    }
    private function getApiUrl() {
        return $this->apiUrl;
    }
    private function xmlToArray(string $xml) {
        $xml = new SimpleXMLElement($xml);
        $json = json_encode($xml);
        return json_decode($json);
    } // A hack because SimpleXML makes my skin crawl. Things like these make me wonder why I still use PHP, but oh well.
    private function apiRequest(string $method, array $args) {
        $curl = curl_init();
        $args_enc = http_build_query($args); // Encode the arguments because this is a GET request, not a POST request.
        curl_setopt_array($curl, array(
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_URL => $this->getApiUrl() . $method . '?' . $args_enc,
            CURLOPT_USERPWD => $this->getUsername() . ":" . $this->getPassword(),
            CURLOPT_HTTPAUTH => CURLAUTH_BASIC
        ));
        return $this->xmlToArray(curl_exec($curl)); // Feel free to port all functions to SimpleXML if you're willing to go down that rabbit hole. I'd prefer the blue pill, tyvm

    } // A few getters and the apiRequest function, all of them are only supposed to be used within the scope of this class.

    public function treinplanner(string $fromStation, string $toStation, string $viaStation = '', int $previousAdvices = 5, int $nextAdvices = 5, DateTime $dateTime = NULL, bool $departure = true, bool $hslAllowed = false, bool $yearCard = false) {
        if(!$dateTime) { // We can't use advanced stuff like "new DateTime" as the default value of a parameter, so we set it to NULL and set it here if it is NULL.
            $dateTime = new DateTime(); // The constructor uses the current time by default
        }
        return $this->apiRequest("ns-api-treinplanner", array("fromStation" => $fromStation, "toStation" => $toStation, "viaStation" => $viaStation, "previousAdvices" => $previousAdvices, "nextAdvices" => $nextAdvices, "dateTime" => $dateTime->format(DateTime::ATOM), "departure" => $departure, "hslAllowed" => $hslAllowed, "yearCard" => $yearCard));
    }
}

