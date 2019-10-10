<?php 
namespace api;

class Utils {

    /**
     * Check if request is allowed. 
     * Request are limeted to 10 succesfully answered per hour per user (by ip address)
     *
     * @param string $ip
     * @return boolean
     */
    public static function requestIPisAllowed(string $ip){
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
     * Return true if content type is one of the following content type: 
     * application/json, application/ld+json, application/xml, text/csv.
     * Else return false
     *
     * @param string $content_type
     * @return boolean
     */
    public static function contentTypeIsAllowed(string $content_type){  
        $allowed_content_types = array('application/json', 'application/ld+json', 'application/xml', 'text/csv');
            
        if (!empty($content_type) && is_string($content_type) && in_array($content_type, $allowed_content_types)){
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
    public static function readBodyByContentType($body, $content_type){
        $result;
        try {
            if ($content_type === 'application/json' || $content_type === 'application/ld+json'){
                $result = json_decode($body, true);
            } 
            elseif ($content_type === 'application/xml') {
                $xml = simplexml_load_string($body);
                $json = json_encode($xml);
                $result = json_decode($json, true);
            }
            elseif ($content_type === 'text/csv') {
                $lines = explode("\n", $body);
                $keys = explode(",", $lines[0]);
                $values = explode(",", $lines[1]);
                $result = array();
                for ($x = 0; $x < count($keys); $x++){               
                    $result[$keys[$x]] = $values[$x];
                }
            }
            else {
                // This is redundant because contentTypeIsAllowed($request->headers) should be called before this function
                $result = array();
            }

            return is_array($result) ? $result : array();

        } catch (\Exception $e){
            // Reading error
            error_log($e->getMessage());
            return array();
        }
    }

    /**
     * Converts $data from array to right format.
     * Returns false if $data invalid
     *
     * @param string $content_type
     * @return string
     */
    public static function convertDataByContentType($content_type, $data){
        if ($content_type === 'application/json' || $content_type === 'application/ld+json'){
            return json_encode($data);
        } 
        elseif ($content_type === 'application/xml') {
            return Utils::arrayToXml($data);
        }
        elseif ($content_type === 'text/csv') {
            if (array_keys($data) !== range(0, count($data) - 1)){
                return Utils::str_putcsv(array($data));                  
            }
            else {
                return Utils::str_putcsv($data);
            }                 
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
    private static function arrayToXml($array, $rootElement = null, $xml = null) {
      $_xml = $xml;
 
      if ($_xml === null) {
        $_xml = new \SimpleXMLElement($rootElement !== null ? $rootElement : '<root/>');
      }
 
      foreach ($array as $k => $v) {
        if (is_array($v)) { //nested array
          Utils::arrayToXml($v, $k, $_xml->addChild($k));
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
    private static function str_putcsv($data) {
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