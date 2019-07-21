<?php
namespace raiz;
use  MongoDB;
error_reporting(E_ALL ^ E_NOTICE ^  E_DEPRECATED);

include "vendor/autoload.php";
require_once("include/globais.php");

$Globais = new Globais();
$con = new \babirondo\classbd\db();

$con->conecta($Globais->banco ,
                      $Globais->localhost,
                      $Globais->db,
                      $Globais->username,
                      $Globais->password,
                      $Globais->port);

$con->MongoDB = $Globais->Championship["Index"];
$con->MongoTable = $Globais->Championship["Type"]["campeonato"];
//TODO: hide my password for publishing on github
$API = new \babirondo\REST\RESTCall($Globais->JiraUsername,$Globais->JiraPassword);


echo "<a href='".$Globais->Rotas["Raiz"]."Import/'>Jira Import</a> <BR>";
echo "<a href='".$Globais->Rotas["Raiz"]."Releases/'>Releases</a> <BR>";
echo "<a href='".$Globais->Rotas["Raiz"]."TicketList/'>List of Jira Tickets</a> <BR>";
echo "<a href='".$Globais->Rotas["Raiz"]."TicketsSprint/'>Tickets per Sprint </a> <BR>";
echo "<a href='".$Globais->Rotas["Raiz"]."TicketsRelease/'>Tickets per Release </a> <BR>";
echo "<a href='".$Globais->Rotas["Raiz"]."SprintOverview/'>Sprints overview </a> <BR>";
echo "<a href='".$Globais->Rotas["Raiz"]."Inconsistences/'>Jira Inconsistences</a> <BR>";
