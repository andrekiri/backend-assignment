<?php

// create app
$app = new \Slim\App();
$container = $app->getContainer();

////////////// Add components to container ///////////// 
$container['config'] = function () {
    return require __DIR__ . '/config.php';
};
////////////////////////////////////////////////////////

require __DIR__ . '/routes.php';

return $app;