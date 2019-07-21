<?php
namespace raiz;
use  MongoDB;

error_reporting(E_ALL ^ E_NOTICE ^  E_DEPRECATED);

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

  //  if (!$classificacao)
  //    $classificacao = "<font color=aa0000>Not Found</font>";
      //  echo "<BR>(".$ticket["key"].") checking for ".$ticket["key"]." = $classificacao";

    return $classificacao;
}



$filtros= array();
$filtros =  array('$or' =>
                array(
                    array("key" => "INPT-4509")
                )
              ) ;
$filtros=array();
$params = array();
$novalistatorneios=array();
$l=0;

$resultMongo =  $con->MongoFind($filtros );

if (@is_array(  $resultMongo )){

    foreach (@ $resultMongo  as  $foreach_linha){

        $novalistatorneios[$foreach_linha["key"]] = $foreach_linha;
        $l++;
    }
}


echo "<table border=1>";
echo "<tr>
          <td>#</td>
          <td>Error</td>
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

          <td>Story Points</td>
          <td>Team</td>
          <td>Fix Version</td>

          <td>Affect Version</td>
          <td>Sprint</td>

      </tr>  ";
$contador=1;

$output = null;
foreach ($novalistatorneios as $ticket ){

    $sprint=null;
    $affectVersion=null;
    $fixVersion=null;
    $key = $ticket["key"];

    $affectVersion = json_decode(json_encode($ticket["fields"]["versions"], true), true);
    $sprints = json_decode(json_encode($ticket["fields"]["customfield_10115"], true), true);
    $fixVersion = json_decode(json_encode($ticket["fields"]["fixVersions"], true), true);
    $fixVersion = (( is_array( $fixVersion ) )? $fixVersion[0]["name"] : "-");

    $storypoints = $ticket["fields"]["customfield_10117"];

    if (is_array($sprints)){


      foreach ($sprints as $s){

        $arraY_sprints[$key][] = substr($s, strpos($s,",name=")+6  , (strpos($s,",goal=")-strpos($s,",name=") -6) );
      }
      $sprint = join(", ",$arraY_sprints[$key]) ;
    }

    //$key_domain = classifica_ticket($ticket, $Globais);
    //$parent_domain = classifica_ticket($novalistatorneios[ $ticket["fields"]["parent"]["key"]], $Globais);
    //$epic_domain = classifica_ticket($novalistatorneios [$ticket["fields"]["customfield_10005"]], $Globais);


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

      $team = $ticket["fields"]["customfield_11269"]["value"];


      $statuscategorychangedate = $ticket["fields"]["statuscategorychangedate"] ;
      $ticket_status = trim($ticket["fields"]["status"]["name"]);

      $resolutiondate = $ticket["fields"]["resolutiondate"] ;

      if ($Globais->DEPARA_STATUS[ $ticket_status ] == "DONE"){
        if ($resolutiondate != "")
          $conclusao_ticket =  $resolutiondate;
        else
         $conclusao_ticket = $statuscategorychangedate;
      }
      else
        $conclusao_ticket = $statuscategorychangedate;
      $statusTicket = $ticket["fields"]["status"]["name"];

      $type = trim($ticket["fields"]["issuetype"]["name"]) ;
      $error = null;

      if ($Globais->IGNORE_TYPES_OF_TICKET[$type] == 1) {$error[] = "Ignored type";  continue;}
      else if ( $Globais->formataData($conclusao_ticket) <  $Globais->formataData("2019-03-19")) {$error[] = "too old tickets";  continue;}
      else if ( @in_array("Inpatient Refinement",$arraY_sprints[$key]) ) {$error[] = "No Sprint associated";  continue;}
      else if ( @in_array("(Team 1) Sprint 8 Candidates",$arraY_sprints[$key]) ) {$error[] = "No Sprint associated";  continue;}
      else if ( @in_array("(Team 2) Sprint 8 Candidates",$arraY_sprints[$key]) ) {$error[] = "No Sprint associated";  continue;}
      else if ( @in_array("Engineering Backlog",$arraY_sprints[$key]) ) {$error[] = "No Sprint associated";  continue;}
      else if ($ticket["fields"]["resolution"]["name"] == "Won't Do" ) {$error[] = "non calculable";  continue;}


      if ($team == "") {$error[] = "No Team";  }
      if ( !@is_array ($arraY_sprints[$key]) ) {$error[] = "No Sprint associated";  }
      if ( $storypoints ==0 ) {$error[] = "No Story Pointed";  }
      if ($type == "Defect" && (!$affectVersion[0]["name"] )) {$error[] = "Affected Version missing";  }
      if (   $Globais->DEPARA_STATUS[ $statusTicket] == "DONE" &&
              (
                !@is_array ($arraY_sprints[$key]) 

              )
          ) {$error[] = "Done without Sprint";  }


      if ( !is_array($error) ) {  continue;}


    $output .= "<tr>
              <td>".$contador++."</td>
              <td> ".@implode(", ", $error)." </td>

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

              <td>". $storypoints."</td>
              <td>".$team."</td>
              <td>".  $fixVersion  ."</td>

              <td>". (( is_array( $affectVersion ) )?$affectVersion[0]["name"] : "-")  ."</td>
              <td>". $sprint ."</td>


          </tr>  ";
          $k++;

          //if ($k>100) break;
}
echo "$k tickets encontrados";

echo $output;

echo "</table>";
