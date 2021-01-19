<?php
require 'func.php';
//modify to accept ports
$servers = [
    // "DAM-DRUMGATE" => ["127.0.0.1", "2026"],
    // "DAM-GENFLOW-1" => ["127.0.0.1", "2024"],
    // "DAM-GENFLOW-2" => ["127.0.0.1", "2025"],
    // "DAM-GENOUT1" => ["127.0.0.1", "2027"] ,
    // "DAM-GENOUT2" => ["127.0.0.1", "2028"],
    // "DAM-LAKELEVEL" => ["127.0.0.1", "2023"],
    // TODO: 
    // idk why tf this doesn't work but $_ENV is empty on the container
    // -- maybe lack of 'E' in php.ini vars config?
    // "DAM-DRUMGATE" => [$_ENV["DAM_PLC_DRUMGATE"], $_ENV["DAM_PLC_PORT"]],
    // "DAM-GENFLOW-1" => [$_ENV["DAM_PLC_GENFLOW1"], $_ENV["DAM_PLC_PORT"]],
    // "DAM-GENFLOW-2" => [$_ENV["DAM_PLC_GENFLOW2"], $_ENV["DAM_PLC_PORT"]],
    // "DAM-GENOUT1" => [$_ENV["DAM_PLC_GENOUT1"], $_ENV["DAM_PLC_PORT"]],
    // "DAM-GENOUT2" => [$_ENV["DAM_PLC_GENOUT2"], $_ENV["DAM_PLC_PORT"]],
    // "DAM-LAKELEVEL" => [$_ENV["DAM_PLC_LAKELEVEL"], $_ENV["DAM_PLC_PORT"]]
    /// so.... hardcode these mfers for now i guess
    "DAM-DRUMGATE" => ["plc_drumgate", 2023],
    "DAM-GENFLOW-1" => ["plc_gen_flow_1", 2023],
    "DAM-GENFLOW-2" => ["plc_gen_flow_2", 2023],
    "DAM-GENOUT1" => ["plc_genout_1", 2023],
    "DAM-GENOUT2" => ["plc_genout_2", 2023],
    "DAM-LAKELEVEL" => ["plc_lake_level", 2023]
];

/////////////////////////// START SUPERVISOR ////////////////////////
/// function plc_read($servername, $port){
/// function plc_set($servername, $port, $value){
//get all values needed to make a decision

$state = array();
$state["DAM-DRUMGATE-FLOW"] = plc_read("DAM-DRUMGATE", "0000");

$state["DAM-GENFLOW1"] = plc_read("DAM-GENFLOW-1", "0000");
$state["DAM-GENFLOW1-GATE"] = plc_read("DAM-GENFLOW-1", "0000");

$state["DAM-GENFLOW2"] = plc_read("DAM-GENFLOW-2", "0000");
$state["DAM-GENFLOW2-GATE"] = plc_read("DAM-GENFLOW-2", "0000");

$state["DAM-GENOUT1"] = plc_read("DAM-GENOUT1", "0000");
$state["DAM-GENOUT2"] = plc_read("DAM-GENOUT2", "0000");

$state["DAM-LAKELEVEL"] = plc_read("DAM-LAKELEVEL", "0000");
$state["LAKE_ALARM_1"] = plc_read("DAM-LAKELEVEL", "0001");
$state["LAKE_ALARM_2"] = plc_read("DAM-LAKELEVEL", "0010");

logme("DG: " . $state["DAM-DRUMGATE-FLOW"] ." GF1: " . $state["DAM-GENFLOW1"] ." GF2: " . $state["DAM-GENFLOW2"] . " GO1: " . $state["DAM-GENOUT1"] . " GO2: " .$state["DAM-GENOUT2"]. " LL: ".$state["DAM-LAKELEVEL"]);
//FULL OPEN ON TOO FULL
if($state["DAM-LAKELEVEL"] > 90){
    plc_set("DAM-GENFLOW-1", "0000", 10);
    plc_set("DAM-GENFLOW-2", "0000", 10);
    plc_set("DAM-DRUMGATE", "0000", 10);
    logme("Setting gates to 100%");
    die();
}
if($state["DAM-LAKELEVEL"] >= 85){
    plc_set("DAM-GENFLOW-1", "0000", 8);
    plc_set("DAM-GENFLOW-2", "0000", 8);
    plc_set("DAM-DRUMGATE", "0000", 7);
    logme("Setting gates to 80%");
    die();
}
if($state["DAM-LAKELEVEL"] >= 80){
    plc_set("DAM-GENFLOW-1", "0000", 6);
    plc_set("DAM-GENFLOW-2", "0000", 6);
    plc_set("DAM-DRUMGATE", "0000", 2);
    logme("Setting gates to 60%");
    die();
}
if($state["DAM-LAKELEVEL"] >= 75){
    plc_set("DAM-GENFLOW-1", "0000", 5);
    plc_set("DAM-GENFLOW-2", "0000", 5);
    plc_set("DAM-DRUMGATE", "0000", 1);
    logme("Setting gates to 50%");
    die();
}
if($state["DAM-LAKELEVEL"] >= 70){
    plc_set("DAM-GENFLOW-1", "0000", 4);
    plc_set("DAM-GENFLOW-2", "0000", 4);
    plc_set("DAM-DRUMGATE", "0000", 1);
    logme("Setting gates to 40%");
    die();
}
if($state["DAM-LAKELEVEL"] < 60){
    plc_set("DAM-GENFLOW-1", "0000", 1);
    plc_set("DAM-GENFLOW-2", "0000", 1);
    plc_set("DAM-DRUMGATE", "0000", 1);
    logme("Setting gates to 0%");
    die();
}
