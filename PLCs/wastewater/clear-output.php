#!/usr/bin/php
<?php
$plcname = "WW-CLEAR-OUTPUT";
require "template.php";
/// Where all the main water flow should end up
/// 
/// PORT 0000 - Outlet for clear and clean water in kGPM
///           - WRITE: A GATE LEVEL BETWEEN 0 AND 10 PERCENT. OPENING FULLY MAY DAMANGE THINGS :)
/// PORT 0100 - GET CURRENT GATE LEVEL IN PERCENT

//////////////////////////////////////////
/// MEMCACHE KEYS
/////////////////////////////////////////
/// WW-CLEAR-OUTPUT = KGPM to lake TODO: Make lake level rise for dam?
/// WW-CLEAR-OUTPUT-GATE = CURRENT POSTION OF GATE 1-10
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

                        $flowrate = $m->get('WW-CLEAR-OUTPUT');
                        if (empty($flowrate)) {
                            //empty lake, lets refill it!
                            $m->set('WW-CLEAR-OUTPUT', 10);
                            $flowrate = 10;
                            logme('Memcache WW-CLEAR-OUTPUT Empty, retin to 10kGPM');
                        }
                        logme('Sending WW-CLEAR-OUTPUT of ' . $flowrate);
                        //crash on invaild data
                        if ($res["value"] !== "0000") {
                            simcrash("Invaild Value for Reading WW-CLEAR-OUTPUT, CRASHING!");
                            break;
                        }
                        ///write_data($type, $port, $number, $mfg1)
                        $data = write_data("1", "0000", $flowrate, 0);
                        fwrite(STDOUT, pack('H*', base_convert("00" . $data, 2, 16)));
                    break;
                    case "0100":

                        $gatepos = $m->get('WW-CLEAR-OUTPUT-GATE');
                        if (empty($gatepos)) {
                            //alarm not set, setting to OFF
                            $m->set('WW-CLEAR-OUTPUT-GATE', 2);
                            logme('resetting WW-CLEAR-OUTPUT-GATE to 20%');
                        }
                        logme('Sending WW-CLEAR-OUTPUT-GATE Status ' . $gatepos);
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
                        logme("WW-CLEAR-OUTPUT-GATE to ".$res["value"]);
                        if ($res["value"] === "0000") {
                            simcrash("Invaild Value for WW-CLEAR-OUTPUT-GATE");
                            break;
                        }
                        if (bindec($res["value"]) > 10) {
                            simcrash("Invaild Value for WW-CLEAR-OUTPUT-GATE");
                            break;
                        }
                        logme("Setting WW-CLEAR-OUTPUT-GATE to " . bindec($res["value"]) . "0%");
                        $m->set('WW-CLEAR-OUTPUT-GATE', bindec($res["value"]));
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
