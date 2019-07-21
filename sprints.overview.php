<?php
namespace raiz;
use  MongoDB;

ini_set('xdebug.var_display_max_depth', '10');
ini_set('xdebug.var_display_max_children', '256');
ini_set('xdebug.var_display_max_data', '1024');

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

//$Debug["ticket"]["INPT-4474"] = 1; //done fora do periodo
//$Debug["ticket"]["INPT-4372"] = 1; // commit and not done
//$Debug["ticket"]["INPT-4499"] = 1; // supposed to be commited and delayed
//$Debug["ticket"]["INPT-4480"] = 1; // supposed to be commited and ontime
//$Debug["ticket"]["INPT-4069"] = 1; // ontime AND notcommitDONE, wrong of course

//TODO: hide my password for publishing on github
$API = new \babirondo\REST\RESTCall($Globais->JiraUsername,$Globais->JiraPassword);



$filtros= array();
$filtros =  array('$or' =>
array(
  array(
    "key" => "INPT-4480",
  )
)
) ;

$filtros=array();
$params = array();
$novalistatorneios=array();
$l=0;

$resultMongo =  $con->MongoFind($filtros );

if (@is_array(  $resultMongo )){

  foreach (@ $resultMongo  as  $foreach_linha){
    $type = trim($foreach_linha["fields"]["issuetype"]["name"]) ;

    if ($Globais->IGNORE_TYPES_OF_TICKET[$type] == 1) continue;

    $novalistatorneios[$foreach_linha["key"]] = $foreach_linha;
    $l++;

    //MAPEAMENTO DE CAMPOS DO JIRA
    $sprints = json_decode(json_encode($foreach_linha["fields"]["customfield_10115"], true), true);
    $team = $foreach_linha["fields"]["customfield_11269"]["value"];
    $team = (($team)?$team:"noteam");

    $fixVersion = json_decode(json_encode($foreach_linha["fields"]["fixVersions"], true), true);
    $fixVersion = (( is_array( $fixVersion ) )? $fixVersion[0]["name"] : "-");

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

        //  $print_tabela[   $sprintInfo["name"] ][$team][$ticket["key"]] = $ticket;

        //  $releases[$fixVersion]["sprints"][$sprintInfo["name"]] = $sprintInfo["name"];

        $fim_do_sprint = (( $sprintInfo["completeDate"] != "<null>")? $sprintInfo["completeDate"] : $sprintInfo["endDate"]);
        $sprintInfo["completeDate"] = $fim_do_sprint;

        $all_sprints[ $sprintInfo["name"] ]["sprintInfo"] = $sprintInfo;
        $all_sprints[ $sprintInfo["name"] ]["teams"][$team] = $team;

        $windows[$team][$sprintInfo["name"] ]["startDate"] = $all_sprints[ $sprintInfo["name"] ]["sprintInfo"]["startDate"];
        $windows[$team][$sprintInfo["name"] ]["completeDate"] = $fim_do_sprint;
      }
    }


  }//for each
}// if sprint
//var_dump($all_sprints);

// criando range de datas e seus respectivos sprints
foreach ($windows as $team => $conteudo_bkp){
  foreach ($conteudo_bkp as $sprint => $conteudo){

    $fim_do_sprint = (( $conteudo["completeDate"] != "<null>")? $conteudo["completeDate"] : $conteudo["endDate"]);

    $diaAtual =   @$Globais->formataData($conteudo["startDate"]) ;
    $fim =  @$Globais->formataData( $fim_do_sprint  );
    $inicio =  @$Globais->formataData( $conteudo["startDate"]);

    //          echo "<BR><BR>inicio: ". date("Y-m-d",  $inicio) ." -  fim: ".   date("Y-m-d",$fim) ;

    while ( $diaAtual <= $fim ){
      //$windows[$team]["DATAS"][ $diaAtual ][$contador]["txt"] = date("d/m/Y",$diaAtual) . " =>" .$sprint;// debug;
      $windows[$team]["DATAS"][ $diaAtual ][] = array( "0" => $sprint, "1" => $fim);
      //$windows[$team]["DATAS"][ $diaAtual ][ = $fim;

      //echo "<BR> dia do loop: $diaAtual ".date("Y-m-d",$diaAtual) . " = ". $sprint;


      $diaAtual = strtotime('+1 days', ($diaAtual));

    }

  }
}
//var_dump( $windows ) ; exit;


echo "<table border=1>";
echo "<tr>

<td>Sprint</td>
<td>Team</td>

<td>Sprint Start</td>
<td>Sprint End</td>

<td>Sprint Goal</td>
<td>Goal Achieved ?</td>

<td>%Commitment x Done</td>
<td>Reactionary Posture per Sprint   (Not commited / total done)</td>
<td>Team Members during Sprint</td>

<td  colspan=2 >Total Worked</td>
<td  colspan=2 >Total Done</td>

<td colspan=2 >Commited</td>
<td  colspan=2 >Commited and Done</td>
<td  colspan=2 >Commited and Done and ontime</td>
<td  colspan=2 >Commited and Done and Released</td>
<td  colspan=2 >Commited and Done and Delayed</td>
<td  colspan=2 >Commited and Not Done</td>

<td  colspan=2 >Not Commited </td>
<td  colspan=2 >Not Commited and Done </td>
<td  colspan=2 >Not Commited and Done and Released</td>
<td  colspan=2 >Not Commited and not Done</td>
<td  colspan=2 >Done and Not Released</td>

<td>New Bugs</td>
<td>Closed Bugs</td>
<td>new Tech Debts</td>
<td>Closed Tech Debts</td>

<td>Automation</td>
<td>Defect</td>
<td>Epic</td>
<td>Improvement</td>
<td>Spike</td>
<td>Story</td>
<td>Story Defect</td>
<td>Sub-task</td>
<td>Task</td>
<td>Tech Debt</td>

<td>Backlog Bugs</td>
<td>Backlog Tech Debt</td>
<td>Automated Tests Coverage</td>

</tr>  ";


$contador=1;

$output = null;
foreach ($novalistatorneios as $ticket ){
  $k++;

  //MAPEAMENTO DE CAMPOS DO JIRA
  $sprints = json_decode(json_encode($ticket["fields"]["customfield_10115"], true), true);
  $sprint = "nosprint";
  $storypoints = $ticket["fields"]["customfield_10117"];

  $typeTicket = trim($ticket["fields"]["issuetype"]["name"]) ;

  $fixVersion = json_decode(json_encode($ticket["fields"]["fixVersions"], true), true);
  $fixVersion = (( is_array( $fixVersion ) )? $fixVersion[0]["name"] : "-");

  $team = $ticket["fields"]["customfield_11269"]["value"];
  $team = (($team)?$team:"noteam");

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


  if ($Globais->BUGS[ $typeTicket ] == "BUG"){
    //se tipo BUG, conta bug abertos por toda a vida do jira = backlog de BUGS no final daquele sprint

    foreach (  $all_sprints as $sprintvariante => $relacaoSprints ){
      if ( $Globais->DEPARA_STATUS[ $ticket_status ] == "NOTDONE"){
        if ($Globais->formataData($ticket["fields"]["created"]) <= $Globais->formataData($relacaoSprints["sprintInfo"]["endDate"]) ){
          $timeframe [   $sprintvariante ] [$team]  [ "BugsBacklog" ] ["TICKETS"] [ $ticket["key"]]  =  $ticket["key"];


        }
      }
    }
  }

  if ($Globais->TechDebt[ $typeTicket ] == "TechDebt"){
    //se tipo BUG

    foreach (  $all_sprints as $sprintvariante => $relacaoSprints ){
      if ( $Globais->DEPARA_STATUS[ $ticket_status ] == "NOTDONE"){
        if ( $Globais->formataData($ticket["fields"]["created"]) <= $Globais->formataData( $relacaoSprints["sprintInfo"]["endDate"]  ) ){
          $timeframe [   $sprintvariante ] [$team]  [ "TechDebtBacklog" ] ["TICKETS"] [ $ticket["key"]]  =  $ticket["key"];
        }
      }
    }

  }



  if (is_array($sprints)){
    $quantidade_sprints_ticket = @COUNT($sprints);

    foreach ($sprints as $s){

      $f = null; $sprintData = null; $sprintInfo = null; $Info = null;

      $f = trim(substr($s, strpos($s, "[")+1));
      $f = substr($f, 0, strlen($f)-1);
      $sprintData = explode(",",$f);

      foreach ($sprintData as $caract){
        $Info = explode("=",$caract);
        $sprintInfo[$Info[0]] = $Info[1];
      }

      $ticket_trabalhado_na_janela_sprint=null;
      if ( @count($windows[$team]["DATAS"][  $Globais->formataData($conclusao_ticket) ]) > 1 ){

        $maior = 0;
        $maiorSprint = null;
        foreach  ($windows[$team]["DATAS"][  $Globais->formataData($conclusao_ticket) ] as $SprintsdoTicket){

          if ($SprintsdoTicket[1] > $maior){
            $maior = $SprintsdoTicket[1];
            $maiorSprint = $SprintsdoTicket[0];
          }
        }


        if ( $maiorSprint ){

          // se o ticket foi encerrado no ultimo dia da sprint e a proxima sprint comeca no mesmo dia, tem que saber se o ticket foi comprometido para a proxima sprint.
          //se foi, conta-se o esforco no proximo sprintf
          //caso contrario conta no sprint do ticket
          if ($quantidade_sprints_ticket == 1)
            $ticket_trabalhado_na_janela_sprint =   $sprintInfo["name"] ;//$windows[$team]["DATAS"][  $Globais->formataData($conclusao_ticket) ][0][0];
          else
            $ticket_trabalhado_na_janela_sprint =   $maiorSprint ;//$windows[$team]["DATAS"][  $Globais->formataData($conclusao_ticket) ][0][0];
        }
        else{
          $ticket_trabalhado_na_janela_sprint = "nosprint";
        }
      }
      else{

        $ticket_trabalhado_na_janela_sprint =   $windows[$team]["DATAS"][  $Globais->formataData($conclusao_ticket) ][0][0];//$sprintInfo["name"];
      }

      $timeframe [   $sprintInfo["name"] ] [$team]  [ $typeTicket ] ["TICKETS"] [ $ticket["key"]]  =  $ticket["key"];
      $timeframe [   $sprintInfo["name"] ] [$team]  [ $typeTicket ] ["StoryPoints"]    +=  $storypoints;


      if ($Globais->BUGS[ $typeTicket ] == "BUG"){
        //se tipo BUG

        if ( $Globais->formataData($ticket["fields"]["created"]) >= $Globais->formataData($sprintInfo["startDate"])
        && $Globais->formataData($ticket["fields"]["created"]) <= $Globais->formataData($sprintInfo["completeDate"]  ) ){
          $timeframe [   $sprintInfo["name"] ] [$team]  [ "BUGRAISED" ] ["TICKETS"] [ $ticket["key"]]  =  $ticket["key"];
          $timeframe [   $sprintInfo["name"] ] [$team]  [ "BUGRAISED" ]["StoryPoints"]    +=  $storypoints;
        }

        if ( $Globais->formataData($conclusao_ticket) >= $Globais->formataData($sprintInfo["startDate"])
        && $Globais->formataData($conclusao_ticket) <= $Globais->formataData($sprintInfo["completeDate"]  ) ){
          $timeframe [   $sprintInfo["name"] ] [$team]  [ "BUGCLOSED" ] ["TICKETS"] [ $ticket["key"]]  =  $ticket["key"];
          $timeframe [   $sprintInfo["name"] ] [$team]  [ "BUGCLOSED" ] ["StoryPoints"]    +=  $storypoints;
        }

      }


      if ($Globais->TechDebt[ $typeTicket ] == "TechDebt"){
        //se tipo BUG

        if ( $Globais->formataData($ticket["fields"]["created"]) >= $Globais->formataData($sprintInfo["startDate"])
        && $Globais->formataData($ticket["fields"]["created"]) <= $Globais->formataData($sprintInfo["completeDate"]  ) ){
          $timeframe [   $sprintInfo["name"] ] [$team]  [ "TECHDEBTRAISED" ] ["TICKETS"] [ $ticket["key"]]  =  $ticket["key"];
          $timeframe [   $sprintInfo["name"] ] [$team]  [ "TECHDEBTRAISED" ] ["StoryPoints"]    +=  $storypoints;
        }

        if ( $Globais->formataData($conclusao_ticket) >= $Globais->formataData($sprintInfo["startDate"])
        && $Globais->formataData($conclusao_ticket) <= $Globais->formataData($sprintInfo["completeDate"]  ) ){
          $timeframe [   $sprintInfo["name"] ] [$team]  [ "TECHDEBTCLOSED" ] ["TICKETS"] [ $ticket["key"]]  =  $ticket["key"];
          $timeframe [   $sprintInfo["name"] ] [$team]  [ "TECHDEBTCLOSED" ] ["StoryPoints"]    +=  $storypoints;
        }

      }

      //COMMITMENT
      $commitment[ $sprintInfo["name"] ][$team]  [ "COMMITED" ] ["TICKETS"] [ $ticket["key"]] = $ticket["key"];
      $commitment[ $sprintInfo["name"] ][$team]  [ "COMMITED" ]["StoryPoints"]    +=  $storypoints;

      if ($fixVersion){
        $commitment [   $sprintInfo["name"] ] [$team]  [ "COMMITED" ] ["RELEASED"] ["TICKETS"][ $ticket["key"]] = $ticket["key"];
        $commitment [   $sprintInfo["name"] ] [$team]  [ "COMMITED" ] ["RELEASED"] ["StoryPoints"]    +=  $storypoints;
      }
      if ($Globais->DEPARA_STATUS[ $ticket_status ] == "DONE"){
        if (!$fixVersion){
          $timeframe[ $ticket_trabalhado_na_janela_sprint ][$team]  ["DONE"]  ["NOTRELEASED"] ["TICKETS"] [ $ticket["key"]] = $ticket["key"];
          $timeframe[ $ticket_trabalhado_na_janela_sprint ][$team]  ["DONE"]  ["NOTRELEASED"]["StoryPoints"]    +=  $storypoints;
        }
      }

      $commitment [   $sprintInfo["name"] ] [$team]  [ "COMMITED" ] [ $Globais->DEPARA_STATUS[ $ticket_status ] ] ["TICKETS"][ $ticket["key"]] = $ticket["key"];
      $commitment [   $sprintInfo["name"] ] [$team]  [ "COMMITED" ] [ $Globais->DEPARA_STATUS[ $ticket_status ] ] ["StoryPoints"]    +=  $storypoints;



      $timeframe [  $ticket_trabalhado_na_janela_sprint ] [$team] [ $Globais->DEPARA_STATUS[ $ticket_status ] ] ["TICKETS"][ $ticket["key"]] = $ticket["key"];
      $timeframe [  $ticket_trabalhado_na_janela_sprint ] [$team] [ $Globais->DEPARA_STATUS[ $ticket_status ] ] ["StoryPoints"]    +=  $storypoints;

      $timeframe [  $ticket_trabalhado_na_janela_sprint ] [$team]  [ "TOTAL_WORKED" ] ["TICKETS"] [ $ticket["key"]] = $ticket["key"];
      $timeframe [  $ticket_trabalhado_na_janela_sprint ] [$team]  [ "TOTAL_WORKED" ]  ["StoryPoints"]    +=  $storypoints;


      if ($ticket_trabalhado_na_janela_sprint == $sprintInfo["name"]){ // ticket foi encerrado na janela do mesmo sprint
        $commitment [   $sprintInfo["name"] ] [$team]  [ "COMMITED" ] [ $Globais->DEPARA_STATUS[ $ticket_status ] ] ["ONTIME"] ["TICKETS"][ $ticket["key"]] = $ticket["key"];
        $commitment [   $sprintInfo["name"] ] [$team]  [ "COMMITED" ] [ $Globais->DEPARA_STATUS[ $ticket_status ] ] ["ONTIME"] ["StoryPoints"]    +=  $storypoints;


        //  unset( $commitment [   $ticket_trabalhado_na_janela_sprint ] [$team]  [ "COMMITED" ] [ $Globais->DEPARA_STATUS[ $ticket_status ] ] ["DELAYED"] ["TICKETS"][ $ticket["key"]] );
        //if ($sprintInfo["name"] == "(Team 1) - Sprint 1"){
          //echo "<BR> ticket: ".$ticket["key"]." - story: $storypoints, acumulado: ".$commitment [   $sprintInfo["name"] ] [$team]  [ "COMMITED" ] [ $Globais->DEPARA_STATUS[ $ticket_status ] ] ["ONTIME"] ["StoryPoints"];
        //}

      }
      else{ // ticket foi encerrado na janela de outro sprint
        $commitment [   $sprintInfo["name"] ] [$team]  [ "COMMITED" ] [ $Globais->DEPARA_STATUS[ $ticket_status ] ] ["DELAYED"] ["TICKETS"][ $ticket["key"]] = $ticket["key"];
        $commitment [   $sprintInfo["name"] ] [$team]  [ "COMMITED" ] [ $Globais->DEPARA_STATUS[ $ticket_status ] ] ["DELAYED"] ["StoryPoints"]    +=  $storypoints;

        if ( !$commitment [   $ticket_trabalhado_na_janela_sprint ] [$team]  [ "COMMITED" ] ["TICKETS"] [ $ticket["key"]] ){

          $timeframe [   $ticket_trabalhado_na_janela_sprint ] [$team]  [ "NOTCOMMITED" ] ["TICKETS"] [ $ticket["key"]] = $ticket["key"];
          $timeframe [   $ticket_trabalhado_na_janela_sprint ] [$team]  [ "NOTCOMMITED" ]["StoryPoints"]    +=  $storypoints;

          $timeframe [   $ticket_trabalhado_na_janela_sprint ] [$team]  [ "NOTCOMMITED" ] [ $Globais->DEPARA_STATUS[ $ticket_status ] ] ["TICKETS"][ $ticket["key"]] = $ticket["key"];
          $timeframe [   $ticket_trabalhado_na_janela_sprint ] [$team]  [ "NOTCOMMITED" ] [ $Globais->DEPARA_STATUS[ $ticket_status ] ] ["StoryPoints"]    +=  $storypoints;
        }

        // WORK DONE
        if ($fixVersion){
          $timeframe [   $ticket_trabalhado_na_janela_sprint ] [$team]  [ "NOTCOMMITED" ]["RELEASED" ] ["TICKETS"] [ $ticket["key"]] = $ticket["key"];
          $timeframe [   $ticket_trabalhado_na_janela_sprint ] [$team]  [ "NOTCOMMITED" ]["RELEASED" ] ["StoryPoints"]    +=  $storypoints;

          $commitment [   $sprintInfo["name"] ] [$team]  [ "COMMITED" ] ["RELEASED"] ["TICKETS"][ $ticket["key"]] = $ticket["key"];
          $commitment [   $sprintInfo["name"] ] [$team]  [ "COMMITED" ] ["RELEASED"]  ["StoryPoints"]    +=  $storypoints;
        }
        else{
          if ($Globais->DEPARA_STATUS[ $ticket_status ] == "DONE"){
            $timeframe[ $ticket_trabalhado_na_janela_sprint ][$team]  ["DONE"]  ["NOTRELEASED"] ["TICKETS"] [ $ticket["key"]] = $ticket["key"];
            $timeframe[ $ticket_trabalhado_na_janela_sprint ][$team]  ["DONE"]  ["NOTRELEASED"] ["StoryPoints"]    +=  $storypoints;
          }
        }

      }

      //se o ticket passou por varios sprints, nao contar como uncommitted o que esta comprometido para a sprint do ticket
      if ( $timeframe[ $sprintInfo["name"] ][$team]  [ "NOTCOMMITED" ] ["TICKETS"] [ $ticket["key"]] ){
        unset($timeframe [   $sprintInfo["name"] ] [$team]  [ "NOTCOMMITED" ] ["TICKETS"] [ $ticket["key"]]);
        $timeframe [   $sprintInfo["name"] ] [$team]  [ "NOTCOMMITED" ] ["StoryPoints"]    -=  $storypoints;
      }

      if ( $timeframe [   $sprintInfo["name"] ] [$team]  [ "NOTCOMMITED" ] ["DONE"] ["TICKETS"] [ $ticket["key"]] ){
        unset($timeframe [   $sprintInfo["name"] ] [$team]  [ "NOTCOMMITED" ]["DONE"] ["TICKETS"] [ $ticket["key"]]);
        $timeframe [   $sprintInfo["name"] ] [$team]  [ "NOTCOMMITED" ] ["DONE"]["StoryPoints"]    -=  $storypoints;
      }

      if ( $timeframe [   $sprintInfo["name"] ] [$team]  [ "NOTCOMMITED" ] ["NOTDONE"] ["TICKETS"] [ $ticket["key"]] ){
        unset($timeframe [   $sprintInfo["name"] ] [$team]  [ "NOTCOMMITED" ]["NOTDONE"] ["TICKETS"] [ $ticket["key"]]);
        $timeframe [   $sprintInfo["name"] ] [$team]  [ "NOTCOMMITED" ]["NOTDONE"] ["StoryPoints"]    -=  $storypoints;
      }
      if ( $timeframe [   $sprintInfo["name"] ] [$team]  [ "NOTCOMMITED" ] ["RELEASED"] ["TICKETS"] [ $ticket["key"]] ){
        unset($timeframe [   $sprintInfo["name"] ] [$team]  [ "NOTCOMMITED" ]["RELEASED"] ["TICKETS"] [ $ticket["key"]]);
        $timeframe [   $sprintInfo["name"] ] [$team]  [ "NOTCOMMITED" ] ["RELEASED"]["StoryPoints"]    -=  $storypoints;
      }
      //FIM RULES



      if ( $Debug["ticket"][ $ticket["key"] ]  == 1 )
      {

        echo " <BR> <BR> <BR>
        ticket: ".$ticket["key"]." <BR>
        Team: $team <BR>
        comprometido para a sprint: ". $sprintInfo["name"]."<BR>
        foi finalizado no timeframe:  $ticket_trabalhado_na_janela_sprint<BR>
        Resolution Date: $resolutiondate<BR>
        Ultima alteracao de status: $statuscategorychangedate<BR>
        conclusao ticket: $conclusao_ticket<BR>
        Status: $ticket_status <BR>
        DEPARA DE STATUS:   ".$Globais->DEPARA_STATUS[ $ticket_status ]." <BR>
        Quantidade de Sprints que esse ticket aparece: $quantidade_sprints_ticket
        <BR><BR>

        Contabilizado como:<BR>
        Total_worked: ".$timeframe [  $sprintInfo["name"] ] [$team] ["TOTAL_WORKED"]["TICKETS"][ $ticket["key"] ]."<BR>
        Total_done: ".$timeframe [  $sprintInfo["name"] ] [$team] ["DONE"]["TICKETS"][ $ticket["key"] ]."<BR>
        commited: ".$commitment [ $sprintInfo["name"] ] [$team] ["COMMITED"]["TICKETS"][ $ticket["key"] ]."<BR>
        commited_done: ".$commitment [ $sprintInfo["name"] ] [$team] ["COMMITED"]["DONE"]["TICKETS"][ $ticket["key"] ]."<BR>
        commited_not_done: ".$commitment [ $sprintInfo["name"] ] [$team]["COMMITED"]["NOTDONE"] ["TICKETS"][ $ticket["key"] ]."<BR>
        commited_done_ontime: ".$commitment [ $sprintInfo["name"] ] [$team] ["COMMITED"]["DONE"]["ONTIME"]["TICKETS"][ $ticket["key"] ]."<BR>
        commited_done_delayed: ".$commitment [ $sprintInfo["name"] ] [$team] ["COMMITED"]["DONE"]["DELAYED"]["TICKETS"][ $ticket["key"] ]."<BR>
        commited_done_released: ".$commitment [ $sprintInfo["name"] ] [$team] ["COMMITED"]["RELEASED"]["TICKETS"][ $ticket["key"] ]."<BR>

        not_commited: ".$timeframe [  $sprintInfo["name"] ] [$team] ["NOTCOMMITED"]["TICKETS"][ $ticket["key"] ]."<BR>
        not_commited_done: ".$timeframe [  $sprintInfo["name"] ] [$team] ["NOTCOMMITED"]["DONE"]["TICKETS"][ $ticket["key"] ]."<BR>
        not_commited_not_done: ".$timeframe [  $sprintInfo["name"] ] [$team] ["NOTCOMMITED"]["NOTDONE"]["TICKETS"][ $ticket["key"] ]."<BR>
        not_commited_done_released: ".$timeframe [  $sprintInfo["name"] ] [$team] ["NOTCOMMITED"]["RELEASED"]["TICKETS"][ $ticket["key"] ]."<BR>


        <PRE>".var_export($timeframe [  $sprintInfo["name"] ] [$team]["NOTCOMMITED"]["DONE"]["TICKETS"], 1)."</PRE>";

        //$timeframe [ $Sprint ] [$Team] ["NOTCOMMITED"]["NOTDONE"]["TICKETS"] )
      }

    }
  }
  else{
    // falta criar a logica para tickets sem sprint
  }
}


// Comparision function
function date_compare($element1, $element2) {
  $datetime1 = strtotime($element1['sprintInfo']['completeDate']);
  $datetime2 = strtotime($element2['sprintInfo']['completeDate']);
  return $datetime2 - $datetime1;
}

// Sort the array
usort($all_sprints, '\raiz\date_compare');

//var_dump($all_sprints);exit;

foreach ($all_sprints as $linha ){

  $quantos_times_nesse_sprint =  1+@count( $linha["teams"] )*5 ;
  $Sprint = $linha["sprintInfo"]["name"];

  if (
    $Sprint == "Inpatient Refinement" || $Sprint == "Engineering Backlog" || $Sprint == "(Team 2) Sprint 8 Candidates" || $Sprint == "(Team 1) Sprint 8 Candidates" ||
    substr($Sprint,0,9) == "Inpatient" || $Sprint == "Tools Sprint 5" || $Sprint == "Auto - Inpatient Sprint 1"
    //  $Sprint != "(Team 2) Sprint 7"
  ){

    continue;
  }



  if (is_array($linha["teams"])){

    foreach ($linha["teams"] as $sprint_time ){
      $Team = $sprint_time;
      $Team = (($Team)?$Team:"noteam");

      echo "<tr>


      <TD nowrap >  ".$Sprint."</td>
      <td   >".$Team."</td>

      <td>".date("d/m/Y",$Globais->formataData($linha["sprintInfo"]["startDate"]))."</td>
      <td>".date("d/m/Y",$Globais->formataData($linha["sprintInfo"]["completeDate"]))."</td>

      <TD>   </td>
      <TD>   </td>
      <TD>   </td>
      <TD>   </td>
      <TD>   </td>



      <td   title=\"". var_export( $timeframe [ $Sprint ] [$Team] ["TOTAL_WORKED"]["TICKETS"]  , 1)."\" >".
      @count($timeframe [ $Sprint ] [$Team] ["TOTAL_WORKED"]["TICKETS"] )."
      </td>
      <td >". $timeframe [ $Sprint ] [$Team] ["TOTAL_WORKED"]["StoryPoints"] ."</td>

      <td    title=\"". var_export( $timeframe [ $Sprint ] [$Team] ["DONE"]["TICKETS"]  , 1)."\"  >".
      @count($timeframe [ $Sprint ] [$Team] ["DONE"]["TICKETS"] )."
      </td>
      <td >". $timeframe [ $Sprint ] [$Team] ["DONE"]["StoryPoints"] ."</td>


      <td  title=\"". var_export($commitment [ $Sprint ] [$Team] ["COMMITED"]["TICKETS"], 1)."\">".
      @count($commitment [ $Sprint ] [$Team] ["COMMITED"]["TICKETS"] )."
      </td>
      <td >". $commitment [ $Sprint ] [$Team] ["COMMITED"]["StoryPoints"] ."</td>


      <td  title=\"". var_export($commitment [ $Sprint ] [$Team] ["COMMITED"]["DONE"]["TICKETS"], 1)."\" >".
      @count($commitment [ $Sprint ] [$Team] ["COMMITED"]["DONE"]["TICKETS"] )."
      </td>
      <td >". $commitment [ $Sprint ] [$Team] ["COMMITED"]["DONE"]["StoryPoints"] ."</td>


      <td  title=\"". var_export($commitment [ $Sprint ] [$Team] ["COMMITED"]["DONE"]["ONTIME"]["TICKETS"], 1)."\" >".
      @count($commitment [ $Sprint ] [$Team] ["COMMITED"]["DONE"]["ONTIME"]["TICKETS"] )."
      </td>
      <td >". $commitment [ $Sprint ] [$Team]  ["COMMITED"]["DONE"]["ONTIME"]["StoryPoints"] ."</td>


      <td   title=\"". var_export($commitment [ $Sprint ] [$Team] ["COMMITED"]["RELEASED"]["TICKETS"], 1)."\"  >".
      @count($commitment [ $Sprint ] [$Team] ["COMMITED"]["RELEASED"]["TICKETS"] )."
      </td>
      <td >". $commitment [ $Sprint ] [$Team] ["COMMITED"]["RELEASED"]["StoryPoints"] ."</td>


      <td  title=\"". var_export($commitment [ $Sprint ] [$Team] ["COMMITED"]["DONE"]["DELAYED"]["TICKETS"], 1)."\"  >".
      @count($commitment [ $Sprint ] [$Team] ["COMMITED"]["DONE"]["DELAYED"]["TICKETS"] )."
      </td>
      <td >". $commitment [ $Sprint ] [$Team]  ["COMMITED"]["DONE"]["StoryPoints"] ."</td>


      <td title=\"". var_export( $commitment [ $Sprint ] [$Team] ["COMMITED"]["NOTDONE"]["TICKETS"]  , 1)."\"  >".
      @count($commitment [ $Sprint ] [$Team] ["COMMITED"]["NOTDONE"]["TICKETS"] )."
      </td>
      <td >". $commitment [ $Sprint ] [$Team] ["COMMITED"]["NOTDONE"]["StoryPoints"] ."</td>


      <td   title=\"". var_export( $timeframe [ $Sprint ] [$Team] ["NOTCOMMITED"]["TICKETS"]  , 1)."\">".
      @count($timeframe [ $Sprint ] [$Team] ["NOTCOMMITED"]["TICKETS"] )."
      </td>
      <td >". $timeframe [ $Sprint ] [$Team] ["NOTCOMMITED"]["StoryPoints"] ."</td>


      <td title=\"". var_export($timeframe [ $Sprint ] [$Team] ["NOTCOMMITED"]["DONE"]["TICKETS"]   , 1)."\" >
      ".
      @count($timeframe [ $Sprint ] [$Team] ["NOTCOMMITED"]["DONE"]["TICKETS"] )."
      </td>
      <td >". $timeframe [ $Sprint ] [$Team] ["NOTCOMMITED"]["DONE"]["StoryPoints"] ."</td>


      <td title=\"". var_export( $timeframe [ $Sprint ] [$Team] ["NOTCOMMITED"]["RELEASED"]["TICKETS"]  , 1)."\" >".
      @count($timeframe [ $Sprint ] [$Team]  ["NOTCOMMITED"]["RELEASED"]["TICKETS"] )."
      </td>
      <td >". $timeframe [ $Sprint ] [$Team] ["NOTCOMMITED"]["RELEASED"]["StoryPoints"] ."</td>


      <td title=\"". var_export( $timeframe [ $Sprint ] [$Team] ["NOTCOMMITED"]["NOTDONE"]["TICKETS"]  , 1)."\" >".
      @count($timeframe [ $Sprint ] [$Team] ["NOTCOMMITED"]["NOTDONE"]["TICKETS"] )."
      </td>
      <td >". $timeframe [ $Sprint ] [$Team]["NOTCOMMITED"]["NOTDONE"]["StoryPoints"] ."</td>


      <td title=\"". var_export( $timeframe [ $Sprint ] [$Team] ["DONE"] ["NOTRELEASED"]["TICKETS"]  , 1)."\" >".
      @count($timeframe [ $Sprint ] [$Team]  ["DONE"] ["NOTRELEASED"]["TICKETS"] )."
      </td>
      <td >". $timeframe [ $Sprint ] [$Team] ["NOTRELEASED"]["StoryPoints"] ."</td>


      <td title=\"". var_export( $timeframe [ $Sprint ] [$Team] ["BUGRAISED"]["TICKETS"]  , 1)."\" >".
      @count($timeframe [ $Sprint ] [$Team] ["BUGRAISED"]["TICKETS"] )."
      </td>


      <td title=\"". var_export( $timeframe [ $Sprint ] [$Team] ["BUGCLOSED"]["TICKETS"]  , 1)."\" >".
      @count($timeframe [ $Sprint ] [$Team] ["BUGCLOSED"]["TICKETS"] )."
      </td>


      <td title=\"". var_export( $timeframe [ $Sprint ] [$Team] ["TECHDEBTRAISED"]["TICKETS"]  , 1)."\" >".
      @count($timeframe [ $Sprint ] [$Team] ["TECHDEBTRAISED"]["TICKETS"] )."
      </td>


      <td title=\"". var_export( $timeframe [ $Sprint ] [$Team] ["TECHDEBTCLOSED"]["TICKETS"]  , 1)."\" >".
      @count($timeframe [ $Sprint ] [$Team] ["TECHDEBTCLOSED"]["TICKETS"] )."
      </td>


      <td title=\"". var_export( $timeframe [ $Sprint ] [$Team] ["Automation"]["TICKETS"]  , 1)."\" >".
      @count($timeframe [ $Sprint ] [$Team] ["Automation"]["TICKETS"] )."
      </td>

      <td title=\"". var_export( $timeframe [ $Sprint ] [$Team] ["Defect"]["TICKETS"]  , 1)."\" >".
      @count($timeframe [ $Sprint ] [$Team] ["Defect"]["TICKETS"] )."
      </td>

      <td title=\"". var_export( $timeframe [ $Sprint ] [$Team] ["Epic"]["TICKETS"]  , 1)."\" >".
      @count($timeframe [ $Sprint ] [$Team] ["Epic"]["TICKETS"] )."
      </td>

      <td title=\"". var_export( $timeframe [ $Sprint ] [$Team] ["Improvement"]["TICKETS"]  , 1)."\" >".
      @count($timeframe [ $Sprint ] [$Team] ["Improvement"]["TICKETS"] )."
      </td>

      <td title=\"". var_export( $timeframe [ $Sprint ] [$Team] ["Spike"]["TICKETS"]  , 1)."\" >".
      @count($timeframe [ $Sprint ] [$Team] ["Spike"]["TICKETS"] )."
      </td>

      <td title=\"". var_export( $timeframe [ $Sprint ] [$Team] ["Story"]["TICKETS"]  , 1)."\" >".
      @count($timeframe [ $Sprint ] [$Team] ["Story"]["TICKETS"] )."
      </td>

      <td title=\"". var_export( $timeframe [ $Sprint ] [$Team] ["Story Defect"]["TICKETS"]  , 1)."\" >".
      @count($timeframe [ $Sprint ] [$Team] ["Story Defect"]["TICKETS"] )."
      </td>

      <td title=\"". var_export( $timeframe [ $Sprint ] [$Team] ["Sub-Task"]["TICKETS"]  , 1)."\" >".
      @count($timeframe [ $Sprint ] [$Team] ["Sub-Task"]["TICKETS"] )."
      </td>

      <td title=\"". var_export( $timeframe [ $Sprint ] [$Team] ["Task"]["TICKETS"]  , 1)."\" >".
      @count($timeframe [ $Sprint ] [$Team] ["Task"]["TICKETS"] )."
      </td>

      <td title=\"". var_export( $timeframe [ $Sprint ] [$Team] ["Tech Debt"]["TICKETS"]  , 1)."\" >".
      @count($timeframe [ $Sprint ] [$Team] ["Tech Debt"]["TICKETS"] )."
      </td>


      <td  >".
      @count($timeframe [ $Sprint ] [$Team] ["BugsBacklog"]["TICKETS"] )."
      </td>

      <td  >".
      @count($timeframe [ $Sprint ] [$Team] ["TechDebtBacklog"]["TICKETS"] )."
      </td>


      <TD></TD>



      </tr>  ";


    }
  }
  else
  echo "</tr>";
}



echo "</table>";
