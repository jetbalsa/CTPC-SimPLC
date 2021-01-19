<?php
$plcname = "DAM-GAMEMASTER";
function logme($line)
{
    global $plcname;
    $team = file_get_contents("/opt/plc/team");
    if(empty($team)){
        $team = "NOTEAM-".gethostname();
    }
    $date = date(DATE_ATOM);
    fwrite(STDERR, "[$team][$plcname][$date]> $line" . PHP_EOL);
    $data = [
        "event" =>[
            "team" => $team,
            "message" => $line,
        ],
        "sourcetype" => "wepplc:plc",
        "host" => $plcname
    ];
    $json = json_encode($data);
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL,"https://splunk.cyberrange.rit.edu:8088/services/collector");
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $json);  //Post Fields
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

    $headers = [
        'Authorization: Splunk 8025e831-0c37-4c1f-976b-2421aebe1e01'
    ];

    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_HEADER, 1);
    $server_output = curl_exec ($ch);
}

$mcserver = "127.0.0.1";
$m = new Memcached();
$m->addServer($mcserver, 11211);

function getmem($key)
{
    global $m;
    $output = $m->get($key);
    if(!is_numeric($output)){
        logme("$key forced to 0");
        $output = 0;
    }
    return $output;

}
function getAllKeys(string $host, int $port): array
{
    $sock = fsockopen($host, $port, $errno, $errstr);
    if ($sock === false) {
        throw new Exception("Error connection to server {$host} on port {$port}: ({$errno}) {$errstr}");
    }

    if (fwrite($sock, "stats items\n") === false) {
        throw new Exception("Error writing to socket");
    }

    $slabCounts = [];
    while (($line = fgets($sock)) !== false) {
        $line = trim($line);
        if ($line === 'END') {
            break;
        }

        // STAT items:8:number 3
        if (preg_match('!^STAT items:(\d+):number (\d+)$!', $line, $matches)) {
            $slabCounts[$matches[1]] = (int)$matches[2];
        }
    }

    foreach ($slabCounts as $slabNr => $slabCount) {
        if (fwrite($sock, "lru_crawler metadump {$slabNr}\n") === false) {
            throw new Exception('Error writing to socket');
        }

        $count = 0;
        while (($line = fgets($sock)) !== false) {
            $line = trim($line);
            if ($line === 'END') {
                break;
            }

            // key=foobar exp=1596440293 la=1596439293 cas=8492 fetch=no cls=24 size=14908
            if (preg_match('!^key=(\S+)!', $line, $matches)) {
                $allKeys[] = $matches[1];
                $count++;
            }
        }

//        if ($count !== $slabCount) {
//            throw new Exception("Surprise, got {$count} keys instead of {$slabCount} keys");
//        }
    }

    if (fclose($sock) === false) {
        throw new Exception('Error closing socket');
    }
   
    return $allKeys;
}
$gs = array();
/// PLC_LAKE_LEVEL = Lake Level in Feet
/// LAKE_ALARM_1 = if alarm is going OFF or ON 
/// LAKE_ALARM_2 = if town evacuation alarm is running
/// LAKE_ALARM_BYPASS = if lake alarm bypass is currently enabled.
/// DAM-GENFLOW1 = MGPM RATE INLET FOR GEN1
/// DAM-GENFLOW1-GATE = CURRENT POSTION OF GATE 1-10
/// DAM-GENFLOW2 = MGPM RATE INLET FOR GEN1
/// DAM-GENFLOW2-GATE = CURRENT POSTION OF GATE 1-10
/// DAM-GENOUT1 = MW OUTPUT OF GEN1
/// DAM-GENOUT1-BYPASS = SET TO 1 TO DISABLE BYPASS, SET TO >1 TO ENGAGE BYPASS
/// DAM-DRUMGATE = PERCENT TO HAVE GATE OPEN, BETWEEN 1 (FULLY CLOSED) TO 10 (FULLY OPEN)
/// DAM-DRUMGATE-FLOW = FLOW RATE PAST DRUM GATE, CAN DEPEND ON LEVEL OF WATER HOW MUCH A OPEN GATE CAN PUSH :)
/// TOTAL-OUTPUT = Last Calc'd total output for dam
/// RAIN-STORM = Is there a rainstorm? 1 = true, 0 = false, increses inputs by 50%!
/// RAIN-STORM-TICKS = number of ticks to have a rainstorm
/// DAM-GAMEOVER = DAM EXPLODED :(
///first step, calc current lake level with all outputs.

//grab last lake level
$gs["LAKE-LEVEL"] = getmem('PLC_LAKE_LEVEL');

///////////// RAIN STORM ////////////////////
$gs["RAIN-STORM"] = getmem('RAIN-STORM');
$gs["RAIN-STORM-TICKS"] = getmem('RAIN-STORM-TICKS');
if($gs["RAIN-STORM"] == 0){
if(rand(1,10) == 4){
    $gs["RAIN-STORM-TICKS"] = rand(3,15);
    logme("=== RAIN STORM ACTIVE FOR THE NEXT ".$gs["RAIN-STORM-TICKS"]." TICKS===");
    $gs["RAIN-STORM"] = 1;
    $m->set('RAIN-STORM', 1);
    $m->set('RAIN-STORM-TICKS', $gs["RAIN-STORM-TICKS"]);
}
}else{
    $gs["RAIN-STORM-TICKS"]--;
    $m->set('RAIN-STORM-TICKS', $gs["RAIN-STORM-TICKS"]);
    if($gs["RAIN-STORM-TICKS"] == 0){
        $m->set('RAIN-STORM', 0);
    }else{
        logme("=== Rain Storm: ACTIVE - Ticks Left: " .$gs["RAIN-STORM-TICKS"]);
    }
}

///////////// OUTPUTS //////////////////////

//Lake too low!
if($gs["LAKE-LEVEL"] > 15){
$gs['DAM-GENFLOW1-GATE'] = getmem('DAM-GENFLOW1-GATE');
$gs['DAM-GENFLOW2-GATE'] =  getmem('DAM-GENFLOW2-GATE');
$gs['DAM-DRUMGATE'] =  getmem('DAM-DRUMGATE');

logme("Gate 1 " . $gs['DAM-GENFLOW1-GATE'] ." Gate 2 " . $gs['DAM-GENFLOW2-GATE'] . " Drum Gate " . $gs['DAM-DRUMGATE']);

// calc flow rates based off percentage for outputs.
$gs['DAM-DRUMGATE'] = ($gs['DAM-DRUMGATE']-1) * 2;
$m->set('DAM-DRUMGATE-FLOW', $gs['DAM-DRUMGATE']);
$m->set('DAM-GENFLOW1', ($gs['DAM-GENFLOW2-GATE']-1));
$m->set('DAM-GENFLOW2', ($gs['DAM-GENFLOW1-GATE']-1));
//total flow rate output
$gs['TOTAL-OUTPUT'] = abs(($gs['DAM-GENFLOW1-GATE']-1) + ($gs['DAM-GENFLOW2-GATE']-1) + $gs['DAM-DRUMGATE']);
$m->set('TOTAL-OUTPUT', $gs['TOTAL-OUTPUT']);
logme("Total Output: " . $gs['TOTAL-OUTPUT'] . "mGPM");
}else{
    $m->set('TOTAL-OUTPUT', 0);
    $m->set('DAM-DRUMGATE-FLOW', 0);
    $m->set('DAM-GENFLOW1', 0);
    $m->set('DAM-GENFLOW2', 0);
    logme("=== Lake Level too Low! Level: " .$gs["LAKE-LEVEL"]. "ft, Outputs Disabled");
    $gs['TOTAL-OUTPUT'] = 0;
    $gs['DAM-GENFLOW1-GATE'] = -1;
    $gs['DAM-GENFLOW2-GATE'] = -1;
    
}
////////////// INPUTS ///////////////////

if($gs["RAIN-STORM"] == 1){
    $float = rand(0,10) / 10;
    $gs["RIVER-INPUT"] = (rand(6, 8)+$float) * 3;
}else{
    $float = rand(0,100) / 100;
    $gs["RIVER-INPUT"] = rand(6, 8)+$float;
}
logme("RIVER-INPUT: " . $gs["RIVER-INPUT"] . "mGPM");
$m->set('RIVER-INPUT', $gs["RIVER-INPUT"]);

///////////////// LAKE LEVEL ///////////////

if (empty($gs["LAKE-LEVEL"])) {
    //empty lake, lets refill it!
    $m->set('PLC_LAKE_LEVEL', 77.01);
    $gs["LAKE-LEVEL"] = 77.01;
    logme('Memcache Lake Level Empty, Reint to 77ft');
}
$delta = $gs["RIVER-INPUT"] - $gs['TOTAL-OUTPUT'];

$gs["LAKE-LEVEL"] = round(abs($gs["LAKE-LEVEL"] + round($delta / 4, 2)), 2);
if($gs["LAKE-LEVEL"] > 120){ $gs["LAKE-LEVEL"] = 120.99;}

logme("Dam Flow Delta: " . $delta . "mGPM - New Lake Level " . $gs["LAKE-LEVEL"]);

// set lake level and alarms
$m->set('PLC_LAKE_LEVEL', $gs["LAKE-LEVEL"]);
if($gs["LAKE-LEVEL"] > 100){
    logme("=== WARNING WARNING WARNING === LAKE LEVEL EXCEEDED MAX!");
    $m->set('LAKE_ALARM_2', "ON");
}else{
    $m->set('LAKE_ALARM_2', "OFF");
}
if($gs["LAKE-LEVEL"] > 110){
    // its game over man, GAME OVER! 
    // Reminder: Do Something with Game Over
    logme("=== WARNING WARNING WARNING === GAME OVER!");
    $m->set('DAM-GAMEOVER', $gs["LAKE-LEVEL"]);
}
if($gs["LAKE-LEVEL"] > 90){
    logme("=== WARNING WARNING WARNING === LAKE LEVEL WARNING");
    $m->set('LAKE_ALARM_1', "ON");
}else{
    $m->set('LAKE_ALARM_1', "OFF");
}
if($gs["LAKE-LEVEL"] < 50){
    logme("=== WARNING WARNING WARNING === LAKE LEVEL WARNING");
    $m->set('LAKE_ALARM_1', "ON");
}else{
    $m->set('LAKE_ALARM_1', "OFF");
}

///////////// POWER OUTPUT /////////////////////
$gs["DAM-GENOUT1-BYPASS"] = getmem("DAM-GENOUT1-BYPASS");
$gs["DAM-GENOUT2-BYPASS"] = getmem("DAM-GENOUT2-BYPASS");

//Water Hight Output Multi
$lakelevelmulti = $gs["LAKE-LEVEL"] * 0.01;

//GEN 1 
if($gs["DAM-GENOUT1-BYPASS"] > 1){
    $m->set('DAM-GENOUT1', 0);
    logme("Gen 1 Output: BYPASS MW");
}else{
    $gs["DAM-GENOUT1"] = round($gs['DAM-GENFLOW1-GATE'] + (rand(1,2) * (rand(2,10) / 10)) + $lakelevelmulti, 2);
    logme("Gen 1 Output: " . $gs["DAM-GENOUT1"] . " MW");
    $m->set('DAM-GENOUT1', $gs["DAM-GENOUT1"]);
}

//GEN 2 
if($gs["DAM-GENOUT2-BYPASS"] > 1){
    $m->set('DAM-GENOUT2', 0);
    logme("Gen 1 Output: BYPASS MW");
}else{
    $gs["DAM-GENOUT2"] = round($gs['DAM-GENFLOW1-GATE'] + (rand(1,2) * (rand(4,10) / 10)) + $lakelevelmulti, 2);
    logme("Gen 2 Output: " . $gs["DAM-GENOUT2"] . " MW");
    $m->set('DAM-GENOUT2', $gs["DAM-GENOUT2"]);
}



    $mcserver = "127.0.0.1";
    $fullmem = array();
    foreach(getAllKeys($mcserver, "11211") as $key){
        $fullmem[$key] = $m->get($key);
    }
    $team = file_get_contents("/opt/plc/team");
    if(empty($team)){
        $team = "NOTEAM-".gethostname();
    }
    $fullmem["team"] = $team;
    $data = [
        "event" => $fullmem,
        "sourcetype" => "wepplc:gamestate",
        "host" => $plcname
    ];
    $json = json_encode($data);
    echo $json.PHP_EOL;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL,"https://splunk.cyberrange.rit.edu:8088/services/collector");
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $json);  //Post Fields
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $headers = [
        'Authorization: Splunk 8025e831-0c37-4c1f-976b-2421aebe1e01'
    ];

    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_HEADER, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    $server_output = curl_exec ($ch);
