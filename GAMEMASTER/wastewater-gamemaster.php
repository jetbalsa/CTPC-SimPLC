<?php
$plcname = "WW-GAMEMASTER";
function logme($line)
{
    global $plcname;
    $date = date(DATE_ATOM);
    fwrite(STDERR, "[$plcname][$date]> $line" . PHP_EOL);
}
$m = new Memcached();
$m->addServer($_ENV["DAM_MEMCACHED_ADDR"], 11211);

function getmem($key)
{
    global $m;
    $output = $m->get($key);
    if (!is_numeric($output)) {
        logme("$key forced to 0");
        $output = 0;
    }
    return $output;
}
$gs = array();

/// WW-INPUTEAST = Last Mesured Input of Input Rates on the west side of the plant
/// WW-INPUTWEST = Last Mesured Input of Input Rates on the west side of the plant

/// WW-CLEAR-OUTPUT = KGPM to lake TODO: Make lake level rise for dam?
/// WW-CLEAR-OUTPUT-GATE = CURRENT POSTION OF GATE 1-10

/// WW-STORM-OUTPUT = KGPM to lake TODO: Make lake level rise for dam?
/// WW-STORM-OUTPUT-GATE = CURRENT POSTION OF GATE 1-10

/// WW-SEDIMENT = How many kGal the take has in it. 
/// WW-SEDIMENT-GATE = CURRENT POSTION OF GATE 1-10, this gate leads into the chlorine tank!

/// WW-CHLORINE = How many kGal the take has in it. 
/// WW-CHLORINE-GATE = CURRENT POSTION OF GATE 1-10, this gate leads the clear water tank!

/// WW-CLEARTANK = How many kGal the take has in it. 
/// WW-CLEARTANK-GATE = CURRENT POSTION OF GATE 1-10, this gate leads the CLEAR-OUTPUT-FLOW

//// Grab if Raining from Dam Sensors!
$gs["RAIN-STORM"] = getmem('RAIN-STORM');

/////////////// INPUTS //////////////////
$gs["WW-INPUTEAST"] = getmem('WW-INPUTEAST');
$gs["WW-INPUTWEST"] = getmem('WW-INPUTWEST');
$gs["WW-TOTAL-INPUT"] = getmem('WW-TOTAL-INPUT');
if ($gs["RAIN-STORM"] == 1) {
    $float = rand(0, 10) / 100;
    $gs["WW-INPUTEAST"] = (rand(10, 15) + $float) * 3;
    $float = rand(0, 10) / 100;
    $gs["WW-INPUTWEST"] = (rand(10, 15) + $float) * 3;
    $gs["WW-TOTAL-INPUT"] = $gs["WW-INPUTEAST"] + $gs["WW-INPUTWEST"];
} else {
    $float = rand(0, 10) / 100;
    $gs["WW-INPUTEAST"] = (rand(10, 20) + $float);
    $float = rand(0, 10) / 100;
    logme($float);
    $gs["WW-INPUTWEST"] = (rand(10, 20) + $float);
    $gs["WW-TOTAL-INPUT"] = $gs["WW-INPUTEAST"] + $gs["WW-INPUTWEST"];
}
logme("WW-TOTAL-INPUT: " . $gs["WW-TOTAL-INPUT"] . "kGPM - W: " . $gs["WW-INPUTWEST"] . " E: " . $gs["WW-INPUTEAST"]);
$m->set('WW-TOTAL-INPUT', $gs["WW-TOTAL-INPUT"]);
$m->set('WW-INPUTWEST', $gs["WW-INPUTWEST"]);
$m->set('WW-INPUTEAST', $gs["WW-INPUTEAST"]);

////////////////// STORM OVERFLOW ///////////////////
$maxstormoutput = 100;
$gs["WW-STORM-OUTPUT-GATE"] = getmem("WW-STORM-OUTPUT-GATE");
if ($gs["WW-STORM-OUTPUT-GATE"] <= 1) {
    $gs["WW-STORM-OUTPUT-GATE"] = 1;
    $gs["WW-STORM-OUTPUT"] = 0;
} else {
    $gs["WW-STORM-OUTPUT"] = round($maxstormoutput * ($gs["WW-STORM-OUTPUT-GATE"] / 10), 2);
}
$m->set('WW-STORM-OUTPUT', $gs["WW-STORM-OUTPUT"]);
$gs["WW-TOTAL-INPUT"] = $gs["WW-TOTAL-INPUT"] - $gs["WW-STORM-OUTPUT"];
if($gs["WW-TOTAL-INPUT"] < 1){$gs["WW-TOTAL-INPUT"] = 0;}
logme("Storm Overflow Output: " . $gs["WW-STORM-OUTPUT"] . " kGPM - Tank Flow: " . $gs["WW-TOTAL-INPUT"]);
//sub the output from total

///////////////// TANK LEVELS //////////////////////

//Tanks can handle 40kGal/minute last tank can get full and will backlog all other tanks!

//calc flow rates based off gates, and calc base levels
$gs["WW-SEDIMENT-GATE"] = getmem("WW-SEDIMENT-GATE");
$gs["WW-CHLORINE-GATE"] = getmem("WW-CHLORINE-GATE");
$gs["WW-CLEARTANK-GATE"] = getmem("WW-CLEARTANK-GATE");
//set a min
$tankoutputrate = 40;
if ($gs["WW-SEDIMENT-GATE"] <= 1) {
    $gs["WW-SEDIMENT-GATE"] = 1;
    $gs["WW-SEDIMENT-RATE"] = 0;
} else {
    $gs["WW-SEDIMENT-RATE"] = round($tankoutputrate * ($gs["WW-SEDIMENT-GATE"] / 10), 2);
}
if ($gs["WW-CHLORINE-GATE"] <= 1) {
    $gs["WW-CHLORINE-GATE"] = 1;
    $gs["WW-CHLORINE-RATE"] = 0;
} else {
    $gs["WW-CHLORINE-RATE"] = round($tankoutputrate * ($gs["WW-CHLORINE-GATE"] / 10), 2);
}
if ($gs["WW-CLEARTANK-GATE"] <= 1) {
    $gs["WW-CLEARTANK-GATE"] = 1;
    $gs["WW-CLEARTANK-RATE"] = 0;
} else {
    $gs["WW-CLEARTANK-RATE"] = round($tankoutputrate * ($gs["WW-CLEARTANK-GATE"] / 10), 2);
}
logme("Tank Rates - SED: " . $gs["WW-SEDIMENT-RATE"] . " CHL: " . $gs["WW-CHLORINE-RATE"] . " CLR: " . $gs["WW-CLEARTANK-RATE"]);
//calc levels
$gs["WW-SEDIMENT"] = getmem("WW-SEDIMENT");
$gs["WW-CHLORINE"] = getmem("WW-CHLORINE");
$gs["WW-CLEARTANK"] = getmem("WW-CLEARTANK");

//sed tank diff
$gs["WW-SEDIMENT"] = $gs["WW-SEDIMENT"] + ($gs["WW-TOTAL-INPUT"] - $gs["WW-SEDIMENT-RATE"]);
$gs["WW-SEDIMENT-RATEDIFF"] = ($gs["WW-TOTAL-INPUT"] - $gs["WW-SEDIMENT-RATE"]);
if($gs["WW-SEDIMENT"] < 1){$gs["WW-SEDIMENT"] = 0;}
if($gs["WW-SEDIMENT"] > 98.01){$gs["WW-SEDIMENT"] = 98.01;}

//chl tank diff
$gs["WW-CHLORINE"] = $gs["WW-CHLORINE"] + ($gs["WW-SEDIMENT-RATEDIFF"] - $gs["WW-CHLORINE-RATE"]);
$gs["WW-CHLORINE-RATEDFF"] = ($gs["WW-SEDIMENT-RATEDIFF"] - $gs["WW-CHLORINE-RATE"]);
if($gs["WW-CHLORINE"] < 1){$gs["WW-CHLORINE"] = 0;}
if($gs["WW-CHLORINE"] > 99.99){$gs["WW-CHLORINE"] = 99.99;}

//clear tank diff
//calc output for clear tank into lake
$maxclearoutlet = 40;
$gs["WW-CLEAR-OUTPUT-GATE"] = getmem("WW-CLEAR-OUTPUT-GATE");
if ($gs["WW-CLEAR-OUTPUT-GATE"] <= 1) {
    $gs["WW-CLEAR-OUTPUT-GATE"] = 1;
    $gs["WW-CLEAR-OUTPUT"] = 0;
} else {
    $gs["WW-CLEAR-OUTPUT"] = round($maxstormoutput * ($gs["WW-CLEAR-OUTPUT-GATE"] / 10), 2);
}
$m->set('WW-CLEAR-OUTPUT', $gs["WW-CLEAR-OUTPUT"]);
// calc level
$gs["WW-CLEARTANK"] = $gs["WW-CLEARTANK"] + ($gs["WW-CHLORINE-RATEDFF"] - $gs["WW-CLEAR-OUTPUT"]);
if($gs["WW-CLEARTANK"] < 1){$gs["WW-CLEARTANK"] = 0;}
if($gs["WW-CLEARTANK"] > 100.99){$gs["WW-CLEARTANK"] = 100.99;}


//calc backflow
if($gs["WW-CLEARTANK"] > 90){
    $gs["WW-CHLORINE"] = getmem("WW-CHLORINE")+$gs["WW-CHLORINE-RATE"];
}
if($gs["WW-CHLORINE"] > 90){
    $gs["WW-SEDIMENT"] = getmem("WW-SEDIMENT")+$gs["WW-SEDIMENT-RATE"];
}

if($gs["WW-CLEARTANK"] > 101.99){$gs["WW-CLEARTANK"] = 100.99;}
if($gs["WW-CHLORINE"] > 100.10){$gs["WW-CHLORINE"] = 99.99;}
if($gs["WW-SEDIMENT"] > 98.20){$gs["WW-SEDIMENT"] = 98.01;}
logme("Tank BF LVL: SED: ".$gs["WW-SEDIMENT"]. " CHL: ". $gs["WW-CHLORINE"] ." CLR: ".$gs["WW-CLEARTANK"] );


$m->set('WW-CLEARTANK', $gs["WW-CLEARTANK"]);
$m->set('WW-CHLORINE', $gs["WW-CHLORINE"]);
$m->set('WW-SEDIMENT', $gs["WW-SEDIMENT"]);

