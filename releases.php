<?php
namespace raiz;
use  MongoDB;
//error_reporting(E_ALL ^ E_NOTICE  ^ E_WARNING ^  E_DEPRECATED);
error_reporting(E_ALL ^ E_NOTICE  ^  E_DEPRECATED);
set_time_limit(15);
ini_set('memory_limit', '-1');

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

//TODO: implementar cache

$filtros=array( ); //"key" => "INPT-4206"
//$filtros=array( "key" => "INPT-4272");
$params = array();
$novalistatorneios=array();
$l=0;

$resultMongo =  $con->MongoFind($filtros );
//$resultMongo =  json_decode(json_encode(current($resultMongo->toArray()),true));



    foreach ($resultMongo  as  $foreach_linha){

        $novalistatorneios[$l] = $foreach_linha;

        $l++;
    }

    foreach ($novalistatorneios as $ticket ){
      $sprints_ticket_string=null;
      $sprints_ticket = null;
      $affectVersion=null;
      $fixVersion=null;
      $team=null;

      $affectVersion = json_decode(json_encode($ticket["fields"]["versions"], true), true);
      $sprints = json_decode(json_encode($ticket["fields"]["customfield_10115"], true), true);
      $team = $ticket["fields"]["customfield_11269"]["value"];

      $fixVersion = json_decode(json_encode($ticket["fields"]["fixVersions"], true), true);
      $fixVersion = (( is_array( $fixVersion ) )? $fixVersion[0]["name"] : "-");

      if (is_array($sprints)){

        foreach ($sprints as $s){

          $sprint_parseado = null;
          $sprint_parseado = substr($s, strpos($s,",name=")+6  , (strpos($s,",goal=")-strpos($s,",name=") -6) );
          $lista_sprints[$sprint_parseado]["NOME"] = $sprint_parseado;

          $sprints_ticket[] = $sprint_parseado;
        }
        //$sprints_ticket_string = join(", ",$sprints_ticket) ;

        foreach ($sprints_ticket as $s){
          $tabela[$s]["COMMITTED"][$ticket["key"]] = $ticket["key"];
          $tabela[$s][$ticket["key"]] = $ticket;
          $tabela[$s][$ticket["key"]]["sprint"] = $s;

          $storypoints = $ticket["fields"]["customfield_10117"];

            // pre compiling data for the table

          //TOTAL DONE
          if ($ticket["fields"]["status"]["name"] == "Done" ||
              $ticket["fields"]["status"]["name"] == "Ready for Demo" ||
              $ticket["fields"]["status"]["name"] == "Closed" )
          {
              ++$tabela[$s]["DONE"];
          }

          //PROFILE: FEATURE
          if ($ticket["fields"]["issuetype"]["name"] == "Task" ||
              $ticket["fields"]["issuetype"]["name"] == "Story"  ||
              $ticket["fields"]["issuetype"]["name"] == "Improvement"  ||
              $ticket["fields"]["issuetype"]["name"] == "Sub-task"
                )
          {
              ++$tabela[$s]["Prof_Feature"];
          }
          //PROFILE: BUG
          else if ($ticket["fields"]["issuetype"]["name"] == "Story Defect" ||
              $ticket["fields"]["issuetype"]["name"] == "Defect"
                )
          {
              ++$tabela[$s]["Prof_Bug"];
          }
          //PROFILE: TEch Debt
          else if ($ticket["fields"]["issuetype"]["name"] == "Spike" ||
              $ticket["fields"]["issuetype"]["name"] == "Tech Debt" ||
              $ticket["fields"]["issuetype"]["name"] == "Automation"
                )
          {
              ++$tabela[$s]["Prof_Tech_Debt"];
          }
          else
              ++$tabela[$s]["Prof_Others"];

                //ESTIMABLE
          if (
              ( $ticket["fields"]["issuetype"]["name"] == "Spike" ||
                $ticket["fields"]["issuetype"]["name"] == "Improvement"  ||
                $ticket["fields"]["issuetype"]["name"] == "Story"  ||
                $ticket["fields"]["issuetype"]["name"] == "Task"  ||
                $ticket["fields"]["issuetype"]["name"] == "Tech Debt"  ||
                $ticket["fields"]["issuetype"]["name"] == "Automation" )
            )
          {
              // CORRECTLY STORY POINTED
            if ($storypoints > 0 ){

              $tabela[$s]["Estimated_Story_Points"] += $storypoints;
              $tabela[$s]["Estimated_Quantity"]++;
            }
            else{
                  // INCORRECTLY STORY POINTED
              $tabela[$s]["Non_Estimated_Supposed_Quantity"]++;
            }
          }
          else
            $tabela[$s]["Non_Estimated_non_estimable_Quantity"]++;
        }
      }



      //$tabela[]
    }


$ticket=null;

echo "<table border=1>";
echo "<tr>
        <td>Sprint</td>
        <td>Committed Tickets</td>
        <td>Done Tickets</td>
        <td>Sprint Goal Achieved ?</td>

        <td>Prof onsite</td>
        <td>Prof Feature</td>
        <td>Prof Bug</td>
        <td>Prof Tech Debt</td>
        <td>Prof Others</td>

        <td>Estimated Quantity</td>
        <td>Estiamted Story Points</td>


        <td>non estimated, supposed to be, quantity</td>
        <td>non estimated, non estimable, quantity</td>


      </tr>";

foreach ($lista_sprints as $sprint => $sprintData){
  echo "<tr>
          <td>".$sprintData["NOME"]."</td>
          <td>".count($tabela[$sprintData["NOME"]]["COMMITTED"])."</td>
          <td>".$tabela[$sprintData["NOME"]]["DONE"]."</td>
          <td>...</td>

          <td>?</td>
          <td>".$tabela[$sprintData["NOME"]]["Prof_Feature"]."</td>
          <td>".$tabela[$sprintData["NOME"]]["Prof_Bug"]."</td>
          <td>".$tabela[$sprintData["NOME"]]["Prof_Tech_Debt"]."</td>
          <td>".$tabela[$sprintData["NOME"]]["Prof_Others"]."</td>

          <td>".$tabela[$sprintData["NOME"]]["Estimated_Quantity"]."</td>
          <td>".$tabela[$sprintData["NOME"]]["Estimated_Story_Points"]."</td>

          <td>".$tabela[$sprintData["NOME"]]["Non_Estimated_Supposed_Quantity"]."</td>
          <td>".$tabela[$sprintData["NOME"]]["Non_Estimated_non_estimable_Quantity"]."</td>
          ";


  echo "</tr>";
}




echo "</table>";
