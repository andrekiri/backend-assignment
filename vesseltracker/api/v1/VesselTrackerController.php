<?php

namespace api\v1;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Container\ContainerInterface;
use api\model\TrackDbModel;
use api\Utils;

class VesselTrackerController
{
    /**
     * The Vessel Tracker.
     *
     * @var array
     */
    protected $dbsettings;

    /**
     * VesselTrackerController constructor.
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->dbsettings = $container->get('config')['database'];
    }

    /**
     * Read track.
     *
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     */
    public function readTrack(Request $request, Response $response, $args){       
        // Check if request is valid content type and ip address allowed
        $response = $this->validateRequestAndCreateResponse($request, $response);
        if (intdiv($response->getStatusCode(), 10) === 40){
            return $response;
        }  

        $responsebody = $response->getBody();
        $track = new TrackDbModel($this->dbsettings);

        $track->read($args['id']);
        if ($track->status !== 200) {
            $responsebody->write($track->data);
        }
        else {
             $responsebody->write(Utils::convertDataByContentType($request->getHeader('Content-Type')[0], $track->data));
        }

        return $response->withBody($responsebody)->withStatus($track->status);
    }

    /**
     * Search tracks.
     *
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function searchTracks(Request $request, Response $response){ 

        // Check if request is valid content type and ip address allowed
        $response = $this->validateRequestAndCreateResponse($request, $response);
        if (intdiv($response->getStatusCode(), 10) === 40){
            return $response;
        }    

        $responsebody = $response->getBody();
        $track = new TrackDbModel($this->dbsettings);
        $track->readMultiple($request->getQueryParams());
        if ($track->status !== 200) {
            $responsebody->write($track->data);
        }
        else {
            $content_type = $request->getHeader('Content-Type')[0];
            $responsebody->write(Utils::convertDataByContentType($content_type, $track->data));
        }

        return $response->withBody($responsebody)->withStatus($track->status);
    }

    /**
     * Add new track.
     *
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function postTrack(Request $request, Response $response){ 

        // Check if request is valid content type and ip address allowed
        $response = $this->validateRequestAndCreateResponse($request, $response);
        if (intdiv($response->getStatusCode(), 10) === 40){
            return $response;
        }
        
        $content_type = $request->getHeader('Content-Type')[0];
        $params = Utils::readBodyByContentType($request->getBody(), $content_type);

        $track = new TrackDbModel($this->dbsettings);
        $track->create($params);
        $responsebody = $response->getBody();
        $responsebody->write($track->data);
        return $response->withBody($responsebody)->withStatus($track->status);
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
        // Check if request is valid content type and ip address allowed
        $response = $this->validateRequestAndCreateResponse($request, $response);
        if (intdiv($response->getStatusCode(), 10) === 40){
            return $response;
        }
        
        $content_type = $request->getHeader('Content-Type')[0];
        $params = Utils::readBodyByContentType($request->getBody(), $content_type);

        $track = new TrackDbModel($this->dbsettings);
        $track->update($args['id'], $params);
        $responsebody = $response->getBody();
        $responsebody->write($track->data);
        return $response->withBody($responsebody)->withStatus($track->status);
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
        // Check if request is valid content type and ip address allowed
        $response = $this->validateRequestAndCreateResponse($request, $response);
        if (intdiv($response->getStatusCode(), 10) === 40){
            return $response;
        }  

        $responsebody = $response->getBody();
        $track = new TrackDbModel($this->dbsettings);
        $track->delete($args['id']);
        
        $responsebody->write($track->data);
        return $response->withBody($responsebody)->withStatus($track->status);
    }

    /**
     * Check if the request content type and ip is allowed.
     * Writes response status code, message, and content type if needed and then returns it.
     *
     * @param Request $body
     * @param Response $response
     * @return Response
     */
    private function validateRequestAndCreateResponse($request, $response){
        $body = $response->getBody();
        // Check if content type present, single and supported
        $content_type = (count($request->getHeader('Content-Type')) === 1) ? $request->getHeader('Content-Type')[0] : "";
        if (!Utils::contentTypeIsAllowed($content_type)){
            // Default content type 'application/json' will be used to respond when request contains an invalid content type           
            $body->write("A single and supported content type required in the request headers.");
            return $response->withBody($body)->withHeader("Content-Type", 'application/json')->withStatus(403);
        } else {
            $response = $response->withHeader("Content-Type", $content_type);
        }   

        // Check if request is allowed
        if (!Utils::requestIPisAllowed($request->getServerParam('REMOTE_ADDR'))){
            $body->write("There were more than 10 successfully answered (20x) requests the last hour from this ip address.");
            return $response->withBody($body)->withStatus(403);
        }
        return $response;
    }

}