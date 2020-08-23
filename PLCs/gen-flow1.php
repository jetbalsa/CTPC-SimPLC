#!/usr/bin/php
<?php
$plcname = "DAM-GENFLOW-1";
require "template.php";
/// DAM POWER GENERATORS WATER FLOW INLET #1
/// READINGS RETURN MILLIONS OF GALLONS PER MINUTE.
/// 
/// PORT 0000 - INLET FOR MILLIONS OF GALLONS PER SECOND
///           - WRITE: A GATE LEVEL BETWEEN 0 AND 100 PERCENT. OPENING FULLY MAY DAMANGE THINGS :)
/// PORT 0100 - GET CURRENT GATE LEVEL IN PERCENT

//////////////////////////////////////////
/// MEMCACHE KEYS
/////////////////////////////////////////
/// DAM-GENFLOW1 = MGPM RATE INLET FOR GEN1
/// DAM-GENFLOW1-GATE = CURRENT POSTION OF GATE 1-10
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

                        $flowrate = $m->get('DAM-GENFLOW1');
                        if (empty($flowrate)) {
                            //empty lake, lets refill it!
                            $m->set('DAM-GENFLOW1', 2.15);
                            $flowrate = 2.15;
                            logme('Memcache DAM-GENFLOW1 Empty, retin to 2.15mGPM');
                        }
                        logme('Sending DAM-GENFLOW1 of ' . $flowrate);
                        //crash on invaild data
                        if ($res["value"] !== "0000") {
                            simcrash("Invaild Value for Reading DAM-GENFLOW1, CRASHING!");
                            break;
                        }
                        ///write_data($type, $port, $number, $mfg1)
                        $data = write_data("1", "0000", $flowrate, 0);
                        fwrite(STDOUT, pack('H*', base_convert("00" . $data, 2, 16)));
                    break;
                    case "0100":

                        $gatepos = $m->get('DAM-GENFLOW1-GATE');
                        if (empty($gatepos)) {
                            //alarm not set, setting to OFF
                            $m->set('DAM-GENFLOW1-GATE', 2);
                            logme('resetting DAM-GENFLOW1-GATE to 20%');
                        }
                        logme('Sending DAM-GENFLOW1-GATE Status ' . $gatepos);
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
                        logme("DAM-GENFLOW1-GATEto ".$res["value"]);
                        if ($res["value"] === "0000") {
                            simcrash("Invaild Value for DAM-GENFLOW1-GATE");
                            break;
                        }
                        if (bindec($res["value"]) > 10) {
                            simcrash("Invaild Value for DAM-GENFLOW1-GATE");
                            break;
                        }
                        logme("Setting DAM-GENFLOW1-GATE to " . bindec($res["value"]) . "0%");
                        $m->set('DAM-GENFLOW1-GATE', bindec($res["value"]));
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
