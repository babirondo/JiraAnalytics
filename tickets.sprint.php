<?php
namespace raiz;
use  MongoDB;

error_reporting(E_ALL ^ E_NOTICE ^ E_WARNING ^  E_DEPRECATED);

include "vendor/autoload.php";
include "include/globais.php";

ini_set('memory_limit', '1024M');
set_time_limit(0);

$Globais = new \raiz\Globais();
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

function classifica_ticket($ticket, $Globais){
    $classificacao  =  $Globais->depara_key[ $ticket["key"] ] ;

    return $classificacao;
}



$filtros= array();
$filtros =  array('$or' =>
                array(
                    array("key" => "INPT-1728")
                )
              ) ;
$filtros=array();
$params = array();
$novalistatorneios=array();


$resultMongo =  $con->MongoFind($filtros );

$nosprint["state"] = "";
$nosprint["name"] = "";
$nosprint["goal"] = "";
$nosprint["startDate"] = "";
$nosprint["endDate"] = "";
$nosprint["completeDate"] = "";


if (@is_array(  $resultMongo )){

    foreach (@ $resultMongo  as  $foreach_linha){


      $sprints = json_decode(json_encode($foreach_linha["fields"]["customfield_10115"], true), true);


      if (is_array($sprints)){
        foreach ($sprints as $s){

          $f = null; $sprintData = null; $sprintInfo = null; $Info = null;

          $f = trim(substr($s, strpos($s, "[")+1));
          $f = substr($f, 0, strlen($f)-1);
          $sprintData = explode(",",$f);

          foreach ($sprintData as $caract){
            $Info = explode("=",$caract);
            $sprintInfo[$Info[0]] = $Info[1];
          }

          $novalistatorneios[ $sprintInfo["name"] ][$foreach_linha["key"]] = $foreach_linha;
          $novalistatorneios[ $sprintInfo["name"] ][$foreach_linha["key"]]["sprint"] = $sprintInfo;

          $all_sprints[ $sprintInfo["name"] ] = $sprintInfo;
        }
      }
      else{
        $novalistatorneios["$sprint"][$foreach_linha["key"]] = $foreach_linha;
        $novalistatorneios["$sprint"][$foreach_linha["key"]]["sprint"] = $nosprint;
      }

    }
}

/*
foreach (  $novalistatorneios["Inpatient Refinement"]  as  $spt => $cont){
  foreach (  $cont  as    $ccc){

  }
    var_dump($cont );exit;
}
exit;
*/

echo "<table border=1>";
echo "<tr>
          <td>#</td>
          <td>Sprint</td>
          <td>Sprint State</td>
          <td>Sprint Goal</td>
          <td>Sprint Start Date</td>
          <td>Sprint End Date</td>
          <td>Sprint Complete Date</td>

          <td>Epic Link</td>
          <td>Epic Name</td>
          <td>Epic Domain</td>

          <td>Parent Type</td>
          <td>Parent Key</td>
          <td>Parent Summary</td>
          <td>Parent Domain</td>

          <td>Issue</td>
          <td>Key</td>
          <td>Summary</td>
          <td>Domain</td>

          <td>Priority</td>
          <td>Assignee</td>
          <td>Status</td>

          <td>Resolution</td>
          <td>Created</td>
          <td>Resolved</td>
          <td>Status Change Date</td>

          <td>Story Points</td>
          <td>Team</td>
          <td>Fix Version</td>

          <td>Affect Version</td>

      </tr>  ";
$contador=1;

$output = null;



foreach ($novalistatorneios as $sprint => $sprints ){
  foreach ($sprints as $ticket ){
    $k++;

    $affectVersion=null;
    $fixVersion=null;

    $affectVersion = json_decode(json_encode($ticket["fields"]["versions"], true), true);
    //$sprints = json_decode(json_encode($ticket["fields"]["customfield_10115"], true), true);
    $fixVersion = json_decode(json_encode($ticket["fields"]["fixVersions"], true), true);
    $fixVersion = (( is_array( $fixVersion ) )? $fixVersion[0]["name"] : "-");

    $storypoints = $ticket["fields"]["customfield_10117"];

    $key_domain = classifica_ticket($ticket, $Globais);
    $parent_domain = classifica_ticket($novalistatorneios[ $ticket["fields"]["parent"]["key"]], $Globais);
    $epic_domain = classifica_ticket($novalistatorneios [$ticket["fields"]["customfield_10005"]], $Globais);


    if ($ticket["fields"]["parent"]["fields"]["issuetype"]["name"])
      $parent_type = $ticket["fields"]["parent"]["fields"]["issuetype"]["name"];
    else
      $parent_type = null;

    if ($ticket["fields"]["parent"]["key"]){
      $parent_key = $ticket["fields"]["parent"]["key"];
    }
    else{
      $parent_domain=null;
      $parent_key = null;
    }

    if ($ticket["fields"]["parent"]["fields"]["summary"])
      $parent_summary = $ticket["fields"]["parent"]["fields"]["summary"];
    else
      $parent_summary = null;



     echo "<tr>
              <td>".$contador++."</td>

              <td>".$all_sprints[ $sprint]["state"] ."</td>
              <td>". $all_sprints[ $sprint] ["name"] ."</td>
              <td>". $all_sprints[ $sprint]["goal"] ."</td>
              <td>". $all_sprints[ $sprint]["startDate"] ."</td>
              <td>". $all_sprints[ $sprint]["endDate"] ."</td>
              <td>". $all_sprints[ $sprint]["completeDate"] ."</td>

              <td>".$ticket["fields"]["customfield_10005"]."</td>
              <td>". $novalistatorneios [ $ticket["fields"]["customfield_10005"] ]["fields"]["summary"]." </td>
              <td>".$epic_domain."</td>

              <td>".$parent_type."</td>
              <td>".$parent_key."</td>
              <td>".$parent_summary."</td>
              <td>".$parent_domain."</td>

              <td>".$ticket["fields"]["issuetype"]["name"]."</td>
              <td>".$ticket["key"] ."</td>
              <td>".$ticket["fields"]["summary"]."</td>
              <td>".$key_domain."</td>

              <td>".$ticket["fields"]["priority"]["name"]."</td>
              <td>".$ticket["fields"]["assignee"]["key"]."</td>
              <td>".$ticket["fields"]["status"]["name"]."</td>
              <td>".$ticket["fields"]["resolution"]["name"]."</td>
              <td>".$ticket["fields"]["created"]."</td>
              <td>".$ticket["fields"]["resolutiondate"]."</td>
              <td>".$ticket["fields"]["statuscategorychangedate"]."</td>

              <td>". $storypoints."</td>
              <td>".$ticket["fields"]["customfield_11269"]["value"]."</td>
              <td>".  $fixVersion  ."</td>

              <td>". (( is_array( $affectVersion ) )?$affectVersion[0]["name"] : "-")  ."</td>


          </tr>  ";

          //if ($k>100) break;
  }
}
echo "$k tickets encontrados";


echo "</table>";
