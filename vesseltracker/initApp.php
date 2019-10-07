<?php
use Psr\Container\ContainerInterface;

// create app
$app = new \Slim\App();
$container = $app->getContainer();

////////////// Add components to container ///////////// 
$container['config'] = function () {
    return require __DIR__ . '/config.php';
};
////////////////////////////////////////////////////////


///////////////* api/v1 Routes *////////////////////////

// GET hello 
$app->get('/api/v1/hello/{name}', function ($request, $response, $args) {
    return $response->write("Hello " . $args['name']);
});

// GET tracks
$app->get('/api/v1/track/{id}', 'api\v1\VesselTrackerController:readTrack');

// GET tracks
$app->get('/api/v1/tracks/search', 'api\v1\VesselTrackerController:searchTracks');

// Post tracks
$app->post('/api/v1/track', 'api\v1\VesselTrackerController:postTrack');

// Put tracks
$app->put('/api/v1/track/{id}', 'api\v1\VesselTrackerController:putTrack');

// Delete track
$app->delete('/api/v1/track/{id}', 'api\v1\VesselTrackerController:deleteTrack');

////////////////////////////////////////////////////////

return $app;