<?php
namespace raiz;
use  MongoDB;

set_time_limit(0);
error_reporting(E_ALL ^ E_NOTICE ^ E_WARNING ^  E_DEPRECATED);

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

$every=100;
$howmanyTickets = 6113;
$iniciar_do = 0;

for ($i=$iniciar_do;$i< ($howmanyTickets+$every);$i = $i + $every){
    $trans=null;$trans = array(":startAt" => $i ,":maxResults" => $every );

    $tickets = null;
    $tickets = $API->CallAPI("GET",  strtr(  $Globais->jiraEndpoint, $trans)  );
    //var_dump($tickets["babirondo/rest-api"]);

    echo "\n\n calling ".strtr(  $Globais->jiraEndpoint, $trans)."  = resposta HTTP CODE -".$tickets["babirondo/rest-api"]["http_code"]."- \n ";

    if ($tickets["babirondo/rest-api"]["http_code"] == 200){
      foreach ($tickets["issues"] as $t){
        //TODO: substituir por insertAll
        $resultMongo = $con->MongoInsertOne( $t  );
        echo ".";
      }
      echo "\n";

    }
    else{
      echo "nao retornou 200 (".$tickets["babirondo/rest-api"]["http_code"]."), nao importou os tickets \n";
      $i = $i - $every;
      sleep(5);
    }
}
