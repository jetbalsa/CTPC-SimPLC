#!/usr/bin/php
<?php
$plcname = "WW-CLEARTANK";
require "template.php";
/// Where all the overflow rain water should end up
/// 
/// PORT 0000 - How many kGal the take has in it. 
///           - WRITE: A GATE LEVEL BETWEEN 0 AND 10 PERCENT. OPENING FULLY MAY DAMANGE THINGS :)
/// PORT 0100 - GET CURRENT GATE LEVEL IN PERCENT

//////////////////////////////////////////
/// MEMCACHE KEYS
/////////////////////////////////////////
/// WW-CLEARTANK = How many kGal the take has in it. 
/// WW-CLEARTANK-GATE = CURRENT POSTION OF GATE 1-10, this gate leads the CLEAR-OUTPUT-FLOW
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

                        $flowrate = $m->get('WW-CLEARTANK');
                        if (empty($flowrate)) {
                            //empty lake, lets refill it!
                            $m->set('WW-CLEARTANK', 0);
                            $flowrate = 0;
                            logme('Memcache WW-CLEARTANK Empty, retin to 10kGPM');
                        }
                        logme('Sending WW-CLEARTANK of ' . $flowrate);
                        //crash on invaild data
                        if ($res["value"] !== "0000") {
                            simcrash("Invaild Value for Reading WW-CLEARTANK, CRASHING!");
                            break;
                        }
                        ///write_data($type, $port, $number, $mfg1)
                        $data = write_data("1", "0000", $flowrate, 0);
                        fwrite(STDOUT, pack('H*', base_convert("00" . $data, 2, 16)));
                    break;
                    case "0100":

                        $gatepos = $m->get('WW-CLEARTANK-GATE');
                        if (empty($gatepos)) {
                            //alarm not set, setting to OFF
                            $m->set('WW-CLEARTANK-GATE', 2);
                            logme('resetting WW-CLEARTANK-GATE to 20%');
                        }
                        logme('Sending WW-CLEARTANK-GATE Status ' . $gatepos);
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
                        logme("WW-CLEARTANK-GATE to ".$res["value"]);
                        if ($res["value"] === "0000") {
                            simcrash("Invaild Value for WW-CLEARTANK-GATE");
                            break;
                        }
                        if (bindec($res["value"]) > 10) {
                            simcrash("Invaild Value for WW-CLEARTANK-GATE");
                            break;
                        }
                        logme("Setting WW-CLEARTANK-GATE to " . bindec($res["value"]) . "0%");
                        $m->set('WW-CLEARTANK-GATE', bindec($res["value"]));
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
