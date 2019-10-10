<?php 

namespace api\model;
use api\model\Database;

class TrackDbModel extends Database{
    /**
     * The data to return.
     *
     * @var array
     */
    public $data; 

    /**
     * The HHTP status to return.
     *
     * @var array
     */
    public $status; 


    /**
     * TrackDbModel constructor.
     *
     * @param string $requestid
     *
     */
    public function __construct($dbsettings) {
        $this->connect($dbsettings); 
    }

    /**
     * Read track and writes status and data public parameters.
     *
     * @param string $requestid
     * @return void
     */
    public function read(string $requestid) {
        // Check id
        if (empty($requestid) || !is_numeric($requestid) || !is_int($requestid + 0) || $requestid < 1  ) {
            $this->status = 400;
            $this->data = "Invalid track id.";
            return;
        }

        // Send sql query 
        $query = 'SELECT *  FROM tracks WHERE id = '.$requestid.';';
        $this->execute($query);
        if (empty($this->sqlresult)){
            $this->status = 500;
            $this->data = "Database SQL error.";
            return;
        } 

        // Create result data
        if($this->sqlresult->rowCount() > 0) {
            $row = $this->sqlresult->fetch(\PDO::FETCH_ASSOC);
            extract($row);
            $this->data = array(
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
            $this->status = 200;
            return;
        }
        else {
            $this->status = 404;
            $this->data = "No track found.";
            return;
        }
    }
 
    /**
     * Searches tracks and writes status and code public parameters.
     *
     * @param array $params
     * @return void
     */       
    public function readMultiple(array $params) {
        // Check if connection was successful
        if (empty($this->conn)){
            $this->status = 500;
            $this->data = "Database Connection error.";
            return;
        }

        // Create sql query with filters if query parameters present
        $query = 'SELECT id, mmsi, status, stationId, speed, lon, lat, course, heading, rot, timestamp FROM tracks';
        $this->status = 400;
        if (!empty($params)){
            $where = ' WHERE ';
            if (isset($params['timestart'])){
                if (empty($params['timestart']) || !is_numeric($params['timestart']) || !is_int($params['timestart'] + 0)){
                    $this->data = "Invalid timestart parameter.";
                    return;
                }
                $where .= 'timestamp >= '.$params['timestart']. ' AND ';
            }
            if (isset($params['timeend'])){
                if (empty($params['timeend']) || !is_numeric($params['timeend']) || !is_int($params['timeend'] + 0)){
                    $this->data = "Invalid timeend parameter.";
                    return;
                }
                $where .= 'timestamp < '.$params['timeend']. ' AND ';
            }
            if (isset($params['latstart'])){
                if (empty($params['latstart']) || !is_numeric($params['latstart']) || !is_float($params['latstart'] + 0.0)){
                    $this->data = "Invalid latstart parameter.";
                    return;
                }
                $where .= 'lat > '.$params['latstart']. ' AND ';
            }
            if (isset($params['latend'])){
                if (empty($params['latend']) || !is_numeric($params['latend']) || !is_float($params['latend'] + 0.0)){
                    $this->data = "Invalid latend parameter.";
                    return;
                }
                $where .= 'lat < '.$params['latend']. ' AND ';
            }
            if (isset($params['lonstart'])){
                if (empty($params['lonstart']) || !is_numeric($params['lonstart']) || !is_float($params['lonstart'] + 0.0)){
                    $this->data = "Invalid lonstart parameter.";
                    return;
                }
                $where .= 'lon > '.$params['lonstart']. ' AND ';
            }
            if (isset($params['lonend'])){
                if (empty($params['lonend']) || !is_numeric($params['lonend']) || !is_float($params['lonend'] + 0.0)){
                    $this->data = "Invalid lonend parameter.";
                    return;
                }
                $where .= 'lon < '.$params['lonend']. ' AND ';
            }
            if (isset($params['mmsi'])){
                if (is_array($params['mmsi'])){
                    foreach ($params['mmsi'] as $key => $value){
                        if (!is_numeric($value) || !is_int($value + 0)){
                            $this->data = "Invalid mmsi parameter.";
                            return;                            
                        }
                    } 
                    $where .= 'mmsi IN ('.join(', ',$params['mmsi']).') AND ';
                }
                elseif (is_numeric($params['mmsi']) && is_int($params['mmsi'] + 0)){
                    $where .= 'mmsi = '.$params['mmsi']. ' AND ';
                }
                else {
                    $this->data = "Invalid mmsi parameter.";
                    return;
                }
            }
            if ($where !== ' WHERE '){
                $query .= substr($where, 0, -5);
            }
        }

        $this->execute($query);
        if (empty($this->sqlresult)){
            $this->status = 500;
            $this->data = "Database SQL error.";
            return;
        } 

        // Check if any tracks
        if(!empty($this->sqlresult) && $this->sqlresult->rowCount() > 0) {
            $this->data = array();
            // fetch requires less memory compared to fetchAll
            while($row = $this->sqlresult->fetch(\PDO::FETCH_ASSOC)) {
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
                array_push($this->data, $item);
            }
            $this->status = 200;
            return;
        } else {
            // No tracks found
            $this->status = 404;
            $this->data = "No tracks found.";
            return;
        }
    }

    /**
     * Creates track and writes status and code public parameters.
     *
     * @param array $requestid
     * @return void
     */
    public function create(array $params) {
        // Check if connection was successful
        if (empty($this->conn)){
            $this->status = 500;
            $this->data = "Database Connection error.";
            return;
        }

        if (empty($params) ||
            empty($params['mmsi']) || !is_numeric($params['mmsi']) ||!is_int($params['mmsi'] + 0)||
            !isset($params['status']) || !is_numeric($params['status']) || !is_int($params['status']+ 0) || 
            empty($params['stationId']) || !is_numeric($params['stationId']) || !is_int($params['stationId']+ 0) || 
            !isset($params['speed']) || !is_numeric($params['speed']) || !is_int($params['speed']+ 0) || 
            empty($params['lon']) || !is_numeric($params['lon']) || 
            empty($params['lat']) || !is_numeric($params['lat']) ||
            empty($params['course']) || !is_numeric($params['course']) || !is_int($params['course'] + 0) || 
            empty($params['heading']) || !is_numeric($params['heading']) || !is_int($params['heading'] + 0) || 
            empty($params['timestamp']) || !is_numeric($params['timestamp']) || !is_int($params['timestamp'] + 0)
            ){           
                // If good params 
                $this->status = 400;
                $this->data = "Invalid or missing parameter.";
                return;
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
        $this->execute($query);

        if ($this->sqlresult->rowCount() === 1){
            $this->status = 200;
            $this->data = "inserted_Id = ".$this->conn->lastInsertId();
            return;
        }
        else {
            $this->status = 500;
            $this->data = "Something went wrong. Track could not be added.";
            return;
        }
    }

    /**
     * Creates track and writes status and code public parameters.
     *
     * @param int $requestid
     * @param array $params
     * @return void
     */
    public function update(int $requestid, array $params) {
        // Check if connection was successful
        if (empty($this->conn)){
            $this->status = 500;
            $this->data = "Database Connection error.";
            return;
        }

        $this->status = 400;
        // Check id
        if (empty($requestid) || !is_numeric($requestid) || !is_int($requestid + 0) || $requestid < 1  ) {
            $this->data = "Invalid track id.";
            return;
        }

        if (empty($params)) {
            $this->data = "Invalid parameters.";
            return;
        }
        $query = 'UPDATE tracks SET ';
        if (isset($params['mmsi'])) {
            if (empty($params['mmsi']) || !is_numeric($params['mmsi']) || !is_int($params['mmsi'] + 0)){
                $this->data = "Invalid mmsi.";
                return;
            } else {
                $query .= 'mmsi = '.$params['mmsi'].', ';
            }
        }
        if (isset($params['status'])) {
            if (!is_numeric($params['status']) || !is_int($params['status'] + 0)){
                $this->data = "Invalid status.";
                return;
            } else {
                $query .= 'status = '.$params['status'].', ';
            }
        }
        if (isset($params['stationId'])) {
            if (empty($params['stationId']) || !is_numeric($params['stationId']) || !is_int($params['stationId'] + 0)){
                $this->data = "Invalid stationId.";
                return;
            } else {
                $query .= 'stationId = '.$params['stationId'].', ';
            }
        }
        if (isset($params['speed'])) {
            if (!is_numeric($params['speed']) || !is_int($params['speed'] + 0)){
                $this->data = "Invalid speed.";
                return;
            } else {
                $query .= 'speed = '.$params['speed'].', ';
            }
        }
        if (isset($params['lon'])) {
            if (empty($params['lon']) || !is_numeric($params['lon'])){
                $this->data = "Invalid lon.";
                return;
            } else {
                $query .= 'lon = '.$params['lon'].', ';
            }
        }
        if (isset($params['lat'])) {
            if (empty($params['lat']) || !is_numeric($params['lat'])){
                $this->data = "Invalid lat.";
                return;
            } else {
                $query .= 'lat = '.$params['lat'].', ';
            }
        }
        if (isset($params['course'])) {
            if (empty($params['course']) || !is_numeric($params['course']) || !is_int($params['course'] + 0)){
                $this->data = "Invalid course.";
                return;
            } else {
                $query .= 'course = '.$params['course'].', ';
            }
        }
        if (isset($params['heading'])) {
            if (empty($params['heading']) || !is_numeric($params['heading']) || !is_int($params['heading'] + 0)){
                $this->data = "Invalid heading.";
                return;
            } else {
                $query .= 'heading = '.$params['heading'].', ';
            }
        }
        if (isset($params['timestamp'])) {
            if (empty($params['timestamp']) || !is_numeric($params['timestamp']) || !is_int($params['timestamp'] + 0)){
                $this->data = "Invalid timestamp.";
                return;
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
            $this->data = "Nothing to update.";
            return;
        }

        $query = substr($query, 0, -2).' WHERE id = '.$requestid.';';
        $this->execute($query);

        if ($this->sqlresult->rowCount() > 0){
            $this->status = 200;
            $this->data = "Updated ".$this->sqlresult->rowCount()." row.";
            return;
        }
        else {
            $this->status = 400;
            $this->data = "Track not found or none of the parameters has changes.";
            return;
        }
    }

    /**
     * Deletes track and writes status and code public parameters.
     *
     * @param string $requestid
     * @return void
     */
    public function delete(string $requestid) {
        // Check if connection was successful
        if (empty($this->conn)){
            $this->status = 500;
            $this->data = "Database Connection error.";
            return;
        }

        // Check id
        if (empty($requestid) || !is_numeric($requestid) || !is_int($requestid + 0) || $requestid < 1  ) {
            $this->status = 400;
            $this->data = "Invalid track id.";
            return;
        }

        // Send sql query 
        $query = 'DELETE FROM tracks WHERE id = '.$requestid.';';
        $this->execute($query);
        if (empty($this->sqlresult)){
            $this->status = 500;
            $this->data = "Database SQL error.";
            return;
        } 

        // Create result data
        $this->status = ($this->sqlresult->rowCount() > 0) ? 200 : 404;
        $this->data = "Deleted ".$this->sqlresult->rowCount()." row.";
        return;
    }

}
