#!/usr/bin/php
<?php
$plcname = "WW-SEDIMENT";
require "template.php";
/// Where all the overflow rain water should end up
/// 
/// PORT 0000 - How many kGal the take has in it. 
///           - WRITE: A GATE LEVEL BETWEEN 0 AND 10 PERCENT. OPENING FULLY MAY DAMANGE THINGS :)
/// PORT 0100 - GET CURRENT GATE LEVEL IN PERCENT

//////////////////////////////////////////
/// MEMCACHE KEYS
/////////////////////////////////////////
/// WW-SEDIMENT = How many kGal the take has in it. 
/// WW-SEDIMENT-GATE = CURRENT POSTION OF GATE 1-10, this gate leads into the chlorine tank!
////////////////////////////////////////

logme("===== STARTUP =====");
function process_logic($input)
{
    ///$input
    /// {"port":"0000","action":"1","value":98,"mfg":15}
    global $m;
    $res = read_data($input);
    if ($res !== false) {
        logme("response OK, " . json_encode($res));
        if ($res["act"] === "1") {
            //read a sensor
                        
            switch ($res["port"]) {
                case "0000":

                        $flowrate = $m->get('WW-SEDIMENT');
                        if (empty($flowrate)) {
                            //empty lake, lets refill it!
                            $m->set('WW-SEDIMENT', 0);
                            $flowrate = 0;
                            logme('Memcache WW-SEDIMENT Empty, retin to 10kGPM');
                        }
                        logme('Sending WW-SEDIMENT of ' . $flowrate);
                        //crash on invaild data
                        if ($res["value"] !== "0000") {
                            simcrash("Invaild Value for Reading WW-SEDIMENT, CRASHING!");
                            break;
                        }
                        ///write_data($type, $port, $number, $mfg1)
                        $data = write_data("1", "0000", $flowrate, 0);
                        fwrite(STDOUT, pack('H*', base_convert("00" . $data, 2, 16)));
                    break;
                    case "0100":

                        $gatepos = $m->get('WW-SEDIMENT-GATE');
                        if (empty($gatepos)) {
                            //alarm not set, setting to OFF
                            $m->set('WW-SEDIMENT-GATE', 2);
                            logme('resetting WW-SEDIMENT-GATE to 20%');
                        }
                        logme('Sending WW-SEDIMENT-GATE Status ' . $gatepos);
                        $data = write_data("1", "0100", $gatepos, 0);
                        fwrite(STDOUT, pack('H*', base_convert("00" . $data, 2, 16)));
                    break;

                    default:
                        logme('Sending Unused Port ' . $res["port"]. " false data");
                        $data = write_data("1", $res["port"], floor(rand(5, 100)), 0);
                        fwrite(STDOUT, pack('H*', base_convert("00" . $data, 2, 16)));
                    break;
    }

            return true;
        }
        if ($res["act"] === "0") {
            switch ($res["port"]) {
                    case "0000":
                        logme("WW-SEDIMENT-GATE to ".$res["value"]);
                        if ($res["value"] === "0000") {
                            simcrash("Invaild Value for WW-SEDIMENT-GATE");
                            break;
                        }
                        if (bindec($res["value"]) > 10) {
                            simcrash("Invaild Value for WW-SEDIMENT-GATE");
                            break;
                        }
                        logme("Setting WW-SEDIMENT-GATE to " . bindec($res["value"]) . "0%");
                        $m->set('WW-SEDIMENT-GATE', bindec($res["value"]));
                        $data = write_data("0", $res["port"], bindec($res["value"]), 0);
                        fwrite(STDOUT, pack('H*', base_convert("00" . $data, 2, 16)));
                    break;
                    default:
                        logme('Sending Unused Port ' . $res["port"]. " false data");
                        $data = write_data("0", $res["port"], floor(rand(5, 100)), 0);
                        fwrite(STDOUT, pack('H*', base_convert("00" . $data, 2, 16)));
                    break;
            }
            return true;
        }
        simcrash("Act Failure? Bailing!");
        return false;
    } else {
        logme("read_data failed, bailing");
        return false;
    }
}

$input = base_convert(bin2hex(fread(STDIN, 6)), 16, 2);
process_logic($input);
logme("==== DONE ====");
