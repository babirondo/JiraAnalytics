<?php
namespace raiz;

use Slim\Views\PhpRenderer;

include "vendor/autoload.php";


$app = new \Slim\App(['settings' => ['displayErrorDetails' => true]]);

$container = $app->getContainer();
$container['renderer'] = new PhpRenderer("./templates");


$app->get('/Import/', function ($request, $response, $args)  use ($app )   {
    require_once("importJira.php");
}  );

$app->get('/TicketList/', function ($request, $response, $args)  use ($app )   {
    require_once("jiralist.php");
}  );

$app->get('/TicketsSprint/', function ($request, $response, $args)  use ($app )   {
    require_once("tickets.sprint.php");
}  );

$app->get('/TicketsRelease/', function ($request, $response, $args)  use ($app )   {
    require_once("tickets.release.php");
}  );

$app->get('/Releases/', function ($request, $response, $args)  use ($app )   {
    require_once("releases.php");
}  );


$app->get('/SprintOverview/', function ($request, $response, $args)  use ($app )   {
    require_once("sprints.overview.php");
}  );



$app->get('/Inconsistences/', function ($request, $response, $args)  use ($app )   {
    require_once("inconsistencias.list.php");
}  );

$app->get('/', function ($request, $response, $args)  use ($app )   {
    require_once("homepage.php");
}  );


$app->run();
