#!/usr/bin/php
<?php
$plcname = "WW-INPUTEAST";
require "template.php";
/// Waste Water System, Main City Inlet EAST SIDE!
/// 
/// PORT 0000 - input rate in KGPM
//////////////////////////////////////////
/// MEMCACHE KEYS
/////////////////////////////////////////
/// WW-INPUTEAST = Last Mesured Input of Input Rates on the west side of the plant
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
                        //get input rate from sensor
                        $lakelevel = $m->get('WW-INPUTEAST');
                        if (empty($lakelevel)) {
                            //empty lake, lets refill it!
                            $m->set('WW-INPUTEAST', 20);
                            $lakelevel = 20;
                            logme('WW-INPUTEAST Empty, Resetting to 20kGPM');
                        }
                        logme('Sending WW-INPUTEAST of ' . $lakelevel);
                        //crash on invaild data
                        if ($res["value"] !== "0000") {
                            simcrash("Invaild Value for Reading WW-INPUTEAST, CRASHING!");
                            break;
                        }
                        ///write_data($type, $port, $number, $mfg1)
                        $data = write_data("1", "0000", $lakelevel, 0);
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
                        simcrash("Tried to write to read only port for Ww-INPUTEAST");
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
