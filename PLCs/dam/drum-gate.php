#!/usr/bin/php
<?php
$plcname = "DAM-DRUMGATE";
require "template.php";
/// DAM POWER DRUM GATE
/// READINGS RETURN MILLIONS OF GALLONS PER MINUTE
/// 
/// PORT 0000 - FLOW RATE PAST DRUM GATE, CAN DEPEND ON LEVEL OF WATER HOW MUCH A OPEN GATE CAN PUSH :)
///           - WRITE: PERCENT TO HAVE GATE OPEN, BETWEEN 1 (FULLY CLOSED) TO 10 (FULLY OPEN)


//////////////////////////////////////////
/// MEMCACHE KEYS
/////////////////////////////////////////
/// DAM-DRUMGATE = PERCENT TO HAVE GATE OPEN, BETWEEN 1 (FULLY CLOSED) TO 10 (FULLY OPEN)
/// DAM-DRUMGATE-FLOW = FLOW RATE PAST DRUM GATE, CAN DEPEND ON LEVEL OF WATER HOW MUCH A OPEN GATE CAN PUSH :)
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

                        $flowrate = $m->get('DAM-DRUMGATE-FLOW');
                        if (empty($flowrate)) {
                            //NO POWERRRR
                            $m->set('DAM-DRUMGATE-FLOW', 0.00);
                            $flowrate = 0.00;
                            logme('Memcache DAM-DRUMGATE-FLOW Empty, retin to 0MGPM');
                        }
                        logme('Sending DAM-DRUMGATE-FLOW of ' . $flowrate);
                        //crash on invaild data
                        if ($res["value"] !== "0000") {
                            simcrash("Invaild Value for Reading DAM-DRUMGATE-FLOW, CRASHING!");
                            break;
                        }
                        ///write_data($type, $port, $number, $mfg1)
                        $data = write_data("1", "0000", $flowrate, 0);
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
                        logme("DAM-DRUMGATE to ".$res["value"]);
                        if ($res["value"] === "0000") {
                            simcrash("Invaild Value for DAM-DRUMGATE");
                            break;
                        }
                        if (bindec($res["value"]) > 10) {
                            simcrash("Invaild Value for DAM-DRUMGATE");
                            break;
                        }
                        logme("Setting DAM-DRUMGATE to " . bindec($res["value"]) . "0%");
                        $m->set('DAM-DRUMGATE', bindec($res["value"]));
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
