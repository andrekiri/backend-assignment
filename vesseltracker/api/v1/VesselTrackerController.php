<?php

namespace api\v1;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Container\ContainerInterface;
use api\core\Database;
use PDO;

class VesselTrackerController
{
    /**
     * The Vessel Tracker.
     *
     * @var ContainerInterface
     */
    protected $container;

    /**
     * VesselTrackerController constructor.
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    // TODO - check sql injection for all API calls

    /**
     * Read track.
     *
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     */
    public function readTrack(Request $request, Response $response, $args){       
        // Check if content type present, single and supported
        if (!$this->contentTypeIsAllowed($request->getHeaders())){
            // Default content type 'application/json' will be used to respond when request contains an invalid content type
            return $this->createResponseWithContentType('application/json', 403, $response, "A single and supported content type required in the request headers.");
        } 
        $content_type = $request->getHeaders()['CONTENT_TYPE'][0];     

        // Check if request is allowed
        if (!$this->requestIsAllowed($request->getServerParam('REMOTE_ADDR'))){
            return $this->createResponseWithContentType($content_type, 403, $response, "There were more than 10 successfully answered (20x) requests the last hour from this ip address.");
        }

        // Check id
        if (empty($args['id']) || !is_numeric($args['id']) || !is_int($args['id'] + 0) || $args['id'] < 1  ) {
            return $this->createResponseWithContentType($content_type, 400, $response, "Invalid track id.");
        }

        // Create sql query 
        $query = ' SELECT *  FROM tracks WHERE id = '.$args['id'].';';
        
        // Connect to DB and execute query
        $sqlresult;
        try {
            $conn = Database::connect( $this->container->get('config')['database']);
            $sqlresult = Database::execute($conn, $query);
        } catch (\PDOException $e) {
            return $this->createResponseWithContentType($content_type, 500, $response, "Database error.");
        }

        // Create response
        if($sqlresult->rowCount() > 0) {
            $row = $sqlresult->fetch(PDO::FETCH_ASSOC);
            extract($row);
            $item = array(
                "id" => intval($id),
                "mmsi"=> intval($mmsi),
                "status"=> intval($status),
                "stationId"=> intval($stationId),
                "speed"=> intval($speed),
                "lon"=> floatval($lon),
                "lat"=> floatval($lat),
                "course"=> intval($course),
                "heading"=> intval($heading),
                "rot"=> $rot,
                "timestamp"=> intval($timestamp)            
            );
            return $this->createResponseWithContentType($content_type, 200, $response,  $item);
        } 
        return $this->createResponseWithContentType($content_type, 404, $response, "No track found.");
    }

    /**
     * Search tracks.
     *
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function searchTracks(Request $request, Response $response){ 

        // Check if content type present, single and supported
        if (!$this->contentTypeIsAllowed($request->getHeaders())){
            // Default content type 'application/json' will be used to respond when request contains an invalid content type
            return $this->createResponseWithContentType('application/json', 403, $response, "A single and supported content type required in the request headers.");
        } 
        $content_type = $request->getHeaders()['CONTENT_TYPE'][0];     

        // Check if request is allowed
        if (!$this->requestIsAllowed($request->getServerParam('REMOTE_ADDR'))){
            return $this->createResponseWithContentType($content_type, 403, $response, "There were more than 10 successfully answered (20x) requests the last hour from this ip address.");
        }
        
        // Create sql query with filters if query parameters present
        $query = 'SELECT id, mmsi, status, stationId, speed, lon, lat, course, heading, rot, timestamp FROM tracks';
        $params = $request->getQueryParams();
        if (!empty($params)){
            $where = ' WHERE ';
            if (isset($params['timestart'])){
                if (empty($params['timestart']) || !is_numeric($params['timestart']) || !is_int($params['timestart'] + 0)){
                    return $this->createResponseWithContentType($content_type, 400, $response, "Invalid timestart parameter.");
                }
                $where .= 'timestamp >= '.$params['timestart']. ' AND ';
            }
            if (isset($params['timeend'])){
                if (empty($params['timeend']) || !is_numeric($params['timeend']) || !is_int($params['timeend'] + 0)){
                    return $this->createResponseWithContentType($content_type, 400, $response, "Invalid timeend parameter.");
                }
                $where .= 'timestamp < '.$params['timeend']. ' AND ';
            }
            if (isset($params['latstart'])){
                if (empty($params['latstart']) || !is_numeric($params['latstart']) || !is_float($params['latstart'] + 0.0)){
                    return $this->createResponseWithContentType($content_type, 400, $response, "Invalid latstart parameter.");
                }
                $where .= 'lat > '.$params['latstart']. ' AND ';
            }
            if (isset($params['latend'])){
                if (empty($params['latend']) || !is_numeric($params['latend']) || !is_float($params['latend'] + 0.0)){
                    return $this->createResponseWithContentType($content_type, 400, $response, "Invalid latend parameter.");
                }
                $where .= 'lat < '.$params['latend']. ' AND ';
            }
            if (isset($params['lonstart'])){
                if (empty($params['lonstart']) || !is_numeric($params['lonstart']) || !is_float($params['lonstart'] + 0.0)){
                    return $this->createResponseWithContentType($content_type, 400, $response, "Invalid lonstart parameter.");
                }
                $where .= 'lon > '.$params['lonstart']. ' AND ';
            }
            if (isset($params['lonend'])){
                if (empty($params['lonend']) || !is_numeric($params['lonend']) || !is_float($params['lonend'] + 0.0)){
                    return $this->createResponseWithContentType($content_type, 400, $response, "Invalid lonend parameter.");
                }
                $where .= 'lon < '.$params['lonend']. ' AND ';
            }
            if (isset($params['mmsi'])){
                if (is_array($params['mmsi'])){
                    // TODO - check if array contains only integers
                    $where .= 'mmsi IN ('.join(', ',$params['mmsi']).') AND ';
                }
                elseif (is_numeric($params['mmsi']) && is_int($params['mmsi'] + 0)){
                    $where .= 'mmsi = '.$params['mmsi']. ' AND ';
                }
                else {
                    return $this->createResponseWithContentType($content_type, 400, $response, "Invalid mmsi parameter.");
                }
            }
            if ($where !== ' WHERE '){
                $query .= 	substr($where, 0, -5).';';
            }
        }

        // Connect to DB and execute query
        $sqlresult;
        try {
            $conn = Database::connect( $this->container->get('config')['database']);
            $sqlresult = Database::execute($conn, $query);
        } catch (\PDOException $e) {
            return $this->createResponseWithContentType($content_type, 500, $response, "Database error.");
        }
  
        // Check if any tracks
        if($sqlresult->rowCount() > 0) {
            $tracks = array();
            // fetch requires less memory compared to fetchAll
            while($row = $sqlresult->fetch(PDO::FETCH_ASSOC)) {
                extract($row);
                $item = array(
                    "id" => intval($id),
                    "mmsi"=> intval($mmsi),
                    "status"=> intval($status),
                    "stationId"=> intval($stationId),
                    "speed"=> intval($speed),
                    "lon"=> floatval($lon),
                    "lat"=> floatval($lat),
                    "course"=> intval($course),
                    "heading"=> intval($heading),
                    "rot"=> $rot,
                    "timestamp"=> intval($timestamp)            
                );
                array_push($tracks, $item);
            }
          return $this->createResponseWithContentType($content_type, 200, $response, $tracks);

        } else {
            // No tracks found
            return $this->createResponseWithContentType($content_type, 200, $response, "No tracks found.");
        }
    }

    /**
     * Add new track.
     *
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function postTrack(Request $request, Response $response){ 
        if (!$this->contentTypeIsAllowed($request->getHeaders())){
            // Default content type 'application/json' will be used to respond when request contains an invalid content type
            return $this->createResponseWithContentType('application/json', 403, $response, "A single and supported content type required in the request headers.");
        } 
        $content_type = $request->getHeaders()['CONTENT_TYPE'][0];     

        // Check if request is allowed
        if (!$this->requestIsAllowed($request->getServerParam('REMOTE_ADDR'))){
            return $this->createResponseWithContentType($content_type, 403, $response, "There were more than 10 successfully answered (20x) requests the last hour from this ip address.");
        }
        
        $params = $this->readBodyByContentType($request->getBody(), $content_type);
        if (empty($params) ||
            empty($params['mmsi']) || !is_numeric($params['mmsi']) ||!is_int($params['mmsi'] + 0)||
            !isset($params['status']) || !is_numeric($params['status']) || !is_int($params['status']+ 0) || 
            empty($params['stationId']) || !is_numeric($params['stationId']) || !is_int($params['stationId']+ 0) || 
            !isset($params['speed']) || !is_numeric($params['speed']) || !is_int($params['speed']+ 0) || 
            empty($params['lon']) || !is_numeric($params['lon']) || 
            empty($params['lat']) || !is_numeric($params['lat']) ||
            empty($params['course']) || !is_numeric($params['course']) || !is_int($params['course'] + 0) || 
            empty($params['heading']) || !is_numeric($params['heading']) || !is_int($params['heading'] + 0) || 
            empty($params['timestamp']) || !is_numeric($params['timestamp']) || !is_int($params['timestamp'] + 0)){
            return $this->createResponseWithContentType($content_type, 400, $response, "Invalid or missing parameter.");
        }
        if (!is_string($params['rot'])){
            $params['rot'] = "";
        }
        // Create sql query 
        $query = 'INSERT INTO tracks ( mmsi, status, stationId, speed, lon, lat, course, heading, rot, timestamp ) VALUES ('.
            $params['mmsi'].', '.
            $params['status'].', '.
            $params['stationId'].', '.
            $params['speed'].', '.
            $params['lon'].', '.
            $params['lat'].', '.
            $params['course'].', '.
            $params['heading'].", '".
            $params['rot']."', ".
            $params['timestamp'].
            ');';

        // Connect to DB and execute query
        $sqlresult;
        try {
            $conn = Database::connect( $this->container->get('config')['database']);
            $sqlresult = Database::execute($conn, $query);
        } catch (\PDOException $e) {
            return $this->createResponseWithContentType($content_type, 500, $response, "Database error.");
        }

        if ($sqlresult->rowCount() === 1){
            return $this->createResponseWithContentType($content_type, 200, $response, array("inserted_Id" => intval($conn->lastInsertId())));           
        }
        return $this->createResponseWithContentType($content_type, 400, $response, "Something went wrong. Track could not be added.");
    }

    /**
     * Update track.
     *
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     */
    public function putTrack(Request $request, Response $response, $args){       
        // Check if content type present, single and supported
        if (!$this->contentTypeIsAllowed($request->getHeaders())){
            // Default content type 'application/json' will be used to respond when request contains an invalid content type
            return $this->createResponseWithContentType('application/json', 403, $response, "A single and supported content type required in the request headers.");
        } 
        $content_type = $request->getHeaders()['CONTENT_TYPE'][0];     

        // Check if request is allowed
        if (!$this->requestIsAllowed($request->getServerParam('REMOTE_ADDR'))){
            return $this->createResponseWithContentType($content_type, 403, $response, "There were more than 10 successfully answered (20x) requests the last hour from this ip address.");
        }

        // Check id
        if (empty($args['id']) || !is_numeric($args['id']) || !is_int($args['id'] + 0) || $args['id'] < 1  ) {
            return $this->createResponseWithContentType($content_type, 400, $response, "Invalid track id.");
        }

        $params = $this->readBodyByContentType($request->getBody(), $content_type);
        $query = 'UPDATE tracks SET ';
        if (isset($params['mmsi'])) {
            if (empty($params['mmsi']) || !is_numeric($params['mmsi']) || !is_int($params['mmsi'] + 0)){
                return $this->createResponseWithContentType($content_type, 400, $response, "Invalid mmsi.");
            } else {
                $query .= 'mmsi = '.$params['mmsi'].', ';
            }
        }
        if (isset($params['status'])) {
            if (!is_numeric($params['status']) || !is_int($params['status'] + 0)){
                return $this->createResponseWithContentType($content_type, 400, $response, "Invalid status.");
            } else {
                $query .= 'status = '.$params['status'].', ';
            }
        }
        if (isset($params['stationId'])) {
            if (empty($params['stationId']) || !is_numeric($params['stationId']) || !is_int($params['stationId'] + 0)){
                return $this->createResponseWithContentType($content_type, 400, $response, "Invalid stationId.");
            } else {
                $query .= 'stationId = '.$params['stationId'].', ';
            }
        }
        if (isset($params['speed'])) {
            if (!is_numeric($params['speed']) || !is_int($params['speed'] + 0)){
                return $this->createResponseWithContentType($content_type, 400, $response, "Invalid speed.");
            } else {
                $query .= 'speed = '.$params['speed'].', ';
            }
        }
        if (isset($params['lon'])) {
            if (empty($params['lon']) || !is_numeric($params['lon'])){
                return $this->createResponseWithContentType($content_type, 400, $response, "Invalid lon.");
            } else {
                $query .= 'lon = '.$params['lon'].', ';
            }
        }
        if (isset($params['lat'])) {
            if (empty($params['lat']) || !is_numeric($params['lat'])){
                return $this->createResponseWithContentType($content_type, 400, $response, "Invalid lat.");
            } else {
                $query .= 'lat = '.$params['lat'].', ';
            }
        }
        if (isset($params['course'])) {
            if (empty($params['course']) || !is_numeric($params['course']) || !is_int($params['course'] + 0)){
                return $this->createResponseWithContentType($content_type, 400, $response, "Invalid course.");
            } else {
                $query .= 'course = '.$params['course'].', ';
            }
        }
        if (isset($params['heading'])) {
            if (empty($params['heading']) || !is_numeric($params['heading']) || !is_int($params['heading'] + 0)){
                return $this->createResponseWithContentType($content_type, 400, $response, "Invalid heading.");
            } else {
                $query .= 'heading = '.$params['heading'].', ';
            }
        }
        if (isset($params['timestamp'])) {
            if (empty($params['timestamp']) || !is_numeric($params['timestamp']) || !is_int($params['timestamp'] + 0)){
                return $this->createResponseWithContentType($content_type, 400, $response, "Invalid timestamp.");
            } else {
                $query .= 'timestamp = '.$params['timestamp'].', ';
            }
        }
        if (isset($params['rot'])) {
            if (!is_string($params['rot'])){
                $query .= "rot = '', ";
            } else {
                $query .= "rot = '".$params['rot']."', ";
            }
        }

        if ($query === 'UPDATE tracks SET '){
            return $this->createResponseWithContentType($content_type, 400, $response, "Nothing to update.");
        }

        $query = substr($query, 0, -2).' WHERE id = '.$args['id'].';';

        // Connect to DB and execute query
        $sqlresult;
        try {
            $conn = Database::connect( $this->container->get('config')['database']);
            $sqlresult = Database::execute($conn, $query);
        } catch (\PDOException $e) {
            return $this->createResponseWithContentType($content_type, 500, $response, "Database error.");
        }

        if ($sqlresult->rowCount() === 1){
            return $this->createResponseWithContentType($content_type, 200, $response, "Updated ".$sqlresult->rowCount()." row.");
        }
        return $this->createResponseWithContentType($content_type, 400, $response, "Nothing updated.");
    }

    /**
     * Delete track.
     *
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     */
    public function deleteTrack(Request $request, Response $response, $args){       
        // Check if content type present, single and supported
        if (!$this->contentTypeIsAllowed($request->getHeaders())){
            // Default content type 'application/json' will be used to respond when request contains an invalid content type
            return $this->createResponseWithContentType('application/json', 403, $response, "A single and supported content type required in the request headers.");
        } 
        $content_type = $request->getHeaders()['CONTENT_TYPE'][0];     

        // Check if request is allowed
        if (!$this->requestIsAllowed($request->getServerParam('REMOTE_ADDR'))){
            return $this->createResponseWithContentType($content_type, 403, $response, "There were more than 10 successfully answered (20x) requests the last hour from this ip address.");
        }

        // Check id
        if (empty($args['id']) || !is_numeric($args['id']) || !is_int($args['id'] + 0) || $args['id'] < 1  ) {
            return $this->createResponseWithContentType($content_type, 400, $response, "Invalid track id.");
        }

        // Create sql query 
        $query = 'DELETE FROM tracks where id = '.$args['id'].';';

        // Connect to DB and execute query
        $sqlresult;
        try {
            $conn = Database::connect( $this->container->get('config')['database']);
            $sqlresult = Database::execute($conn, $query);
        } catch (\PDOException $e) {
            return $this->createResponseWithContentType($content_type, 500, $response, "Database error.");
        }

        if ($sqlresult->rowCount() === 1){
            return $this->createResponseWithContentType($content_type, 200, $response,"Deleted ".$sqlresult->rowCount()." row.");
        }
        return $this->createResponseWithContentType($content_type, 400, $response, "Track id not found.");
    }

    /**
     * Check if request is allowed. 
     * Request are limeted to 10 succesfully answered per hour per user (by ip address)
     *
     * @param string $ip
     * @return boolean
     */
    private function requestIsAllowed($ip){
        $file = fopen(getcwd().'/logs/access.log', 'r');
        $ln = 0; 
        $linestring = array();
        $output = array();
        for($x_pos = 0; fseek($file, $x_pos, SEEK_END) !== -1 && count($output) < 10; $x_pos--) {
            $char = fgetc($file);
            if ($char === "\n") {
                if (strpos($linestring[$ln], '/api/v1/track') !== false && // line contains '/api/v1/tracks'
                    strpos($linestring[$ln], 'HTTP/1.1" 20') !== false &&  // line contains 'HTTP/1.1" 20x'
                    strpos($linestring[$ln], $ip) !== false ){              // line contains request ip address
                    // add timestamp to output array
                    array_push($output, explode( ']', explode( '[', $linestring[$ln] )[1])[0]);
                }
                // go to next line
                $ln++;
                continue;
            }
            // add character to current linestring
            $linestring[$ln] = $char . ((array_key_exists($ln, $linestring)) ? $linestring[$ln] : '');
        }

        // Handle first line (10nth line or top line of file)
        if (strpos(end($linestring), '/api/v1/tracks') !== false && strpos(end($linestring), $ip) !== false && strpos(end($linestring), 'HTTP/1.1" 200') !== false){
            array_push($output, explode( ']', explode( '[', end($linestring) )[1])[0]);
        }
        fclose($file);
        $time_one_hour_before = time() - 3600;
        if (count($output) < 10 || strtotime(end($output)) < $time_one_hour_before ){
            return true;
        }
        return false;
    }

    /**
     * Check if content type is allowed. 
     * Return true if recieved content type is exact and one of the following content type: 
     * application/json, application/ld+json, application/xml, text/csv.
     * Else return false
     *
     * @param array $headers
     * @return boolean
     */
    private function contentTypeIsAllowed($headers){      
        if (!empty($headers["CONTENT_TYPE"]) && 
            is_array($headers["CONTENT_TYPE"]) && 
            count($headers["CONTENT_TYPE"]) === 1 && 
            in_array($headers["CONTENT_TYPE"][0], array('application/json', 'application/ld+json', 'application/xml', 'text/csv'))){
            return true;
        }
        return false;
    }

    /**
     * Converts body into array depending on content type.
     * Allowed content types application/json, application/ld+json, application/xml, text/csv
     * Returns the converted array or false if body not in the expected format.
     *
     * @param string $body
     * @param string $content_type
     * @return array
     */
    private function readBodyByContentType($body, $content_type){

        if ($content_type === 'application/json' || $content_type === 'application/ld+json'){
            return json_decode($body, true);
        } 
        elseif ($content_type === 'application/xml') {
                $xml = simplexml_load_string($body);
                $json = json_encode($xml);
            return json_decode($json, true);
        }
        elseif ($content_type === 'text/csv') {
            $lines = explode("\n", $body);
            $keys = explode(",", $lines[0]);
            $values = explode(",", $lines[1]);
            $result = array();
            for ($x = 0; $x < count($keys); $x++){               
                $result[$keys[$x]] = $values[$x];
                $result;
            }
            return $result;
        }
        else {
            // This is redundant because contentTypeIsAllowed($request->headers) should be called before this function
            return false;
        }

    }

    /**
     * Writes $data to request body in the right format.
     * Returns false if $data invalid
     *
     * @param string $content_type
     * @param Response $response
     * @param array $data
     * @param int $status
     * @return Response
     */
    private function createResponseWithContentType($content_type, $status, $response, $data){

        if ($content_type === 'application/json' || $content_type === 'application/ld+json'){
            return $response->withStatus($status)->withJson($data);
        } 
        elseif ($content_type === 'application/xml') {
            if (is_array($data)){
                $data = $this->arrayToXml($data);
            }
            $response->getBody()->write($data);
            return $response->withHeader("Content-Type", 'application/xml')->withStatus($status);
        }
        elseif ($content_type === 'text/csv') {
            if (is_array($data) ){
                if (array_keys($data) !== range(0, count($data) - 1)){
                    $data = $this->str_putcsv(array($data));                  
                }
                else {
                    $data = $this->str_putcsv($data);
                }
            }
            $response->getBody()->write($data);
            return $response->withHeader("Content-Type", 'text/csv')->withStatus($status);            
        }
        else {
            // This is redundant because contentTypeIsAllowed($request->headers) should be called before this function
            return false;
        }
    }

    /**
     * 3rd party code taken from https://www.kerstner.at/2011/12/php-array-to-xml-conversion/
     *
     * @param array $array the array to be converted
     * @param string? $rootElement if specified will be taken as root element, otherwise defaults to <root>
     * @param SimpleXMLElement? if specified content will be appended, used for recursion
     * @return string XML version of $array
     */
    private function arrayToXml($array, $rootElement = null, $xml = null) {
      $_xml = $xml;
 
      if ($_xml === null) {
        $_xml = new \SimpleXMLElement($rootElement !== null ? $rootElement : '<root/>');
      }
 
      foreach ($array as $k => $v) {
        if (is_array($v)) { //nested array
          $this->arrayToXml($v, $k, $_xml->addChild($k));
        } else {
          $_xml->addChild($k, $v);
        }
      }
 
      return $_xml->asXML();
    }

    /**
     * 3rd party code taken from https://coderwall.com/p/zvzwwa/array-to-comma-separated-string-in-php
     *
     * Convert a multi-dimensional, associative array to CSV data
     * @param  array $data the array of data
     * @return string       CSV text
     */
    function str_putcsv($data) {
            # Generate CSV data from array
            $fh = fopen('php://temp', 'rw'); # don't create a file, attempt
                                             # to use memory instead
            # write out the headers
            fputcsv($fh, array_keys(current($data)));

            # write out the data
            foreach ( $data as $row ) {
                    fputcsv($fh, $row);
            }
            rewind($fh);
            $csv = stream_get_contents($fh);
            fclose($fh);

            return $csv;
    }

}