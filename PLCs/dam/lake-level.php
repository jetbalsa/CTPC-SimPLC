#!/usr/bin/php
<?php
$plcname = "DAM-LAKELEVEL";
require "template.php";
/// DAM LAKE LEVEL SENSOR AND ALARM
/// READING DATA RETURNS DATA IN FEET OF CALIBRATED LAKE LEVEL
/// 
/// PORT 0000 - LAKE LEVEL IN FEET
/// PORT 0001 - SOUND ALARM 1 - LARGE RELEASE OF WATER
/// PORT 0010 - SOUND ALARM 2 - TOWN EVACUATION ALARM
/// PORT 0100 - SLIENCE AUTOMATED ALARMS
//////////////////////////////////////////
/// MEMCACHE KEYS
/////////////////////////////////////////
/// PLC_LAKE_LEVEL = Lake Level in Feet
/// LAKE_ALARM_1 = if alarm is going OFF or ON 
/// LAKE_ALARM_2 = if town evacuation alarm is running
/// LAKE_ALARM_BYPASS = if lake alarm bypass is currently enabled.
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
                        //get last lake level from memcache
                        $lakelevel = $m->get('PLC_LAKE_LEVEL');
                        if (empty($lakelevel)) {
                            //empty lake, lets refill it!
                            $m->set('PLC_LAKE_LEVEL', 77.01);
                            $lakelevel = 77;
                            logme('Memcache Lake Level Empty, Reint to 77ft');
                        }
                        logme('Sending PLC_LAKE_LEVEL of ' . $lakelevel);
                        //crash on invaild data
                        if ($res["value"] !== "0000") {
                            simcrash("Invaild Value for Reading Lake Level, CRASHING!");
                            break;
                        }
                        ///write_data($type, $port, $number, $mfg1)
                        $data = write_data("1", "0000", $lakelevel, 0);
                        fwrite(STDOUT, pack('H*', base_convert("00" . $data, 2, 16)));
                    break;
                case "0001":
                        //get alarm status
                        $lakealarm1 = $m->get('LAKE_ALARM_1');
                        if (empty($lakealarm1)) {
                            //alarm not set, setting to OFF
                            $m->set('LAKE_ALARM_1', "OFF");
                            $lakealarm1  = "OFF";
                            logme('resetting LAKE_ALARM_1 to OFF');
                        }
                        logme('Sending LAKE_ALARM_1 Status ' . $lakealarm1);
                        if ($lakealarm1 === "OFF") {
                            $data = write_data("1", "0001", 0, 0);
                        }
                        if ($lakealarm1 === "ON") {
                            $data = write_data("1", "0001", floor(rand(5, 10)), 0);
                        }
                        fwrite(STDOUT, pack('H*', base_convert("00" . $data, 2, 16)));
                    break;
                    case "0010":
                        //get alarm status
                        $lakealarm1 = $m->get('LAKE_ALARM_2');
                        if (empty($lakealarm1)) {
                            //alarm not set, setting to OFF
                            $m->set('LAKE_ALARM_2', "OFF");
                            $lakealarm1  = "OFF";
                            logme('resetting LAKE_ALARM_2 to OFF');
                        }
                        logme('Sending LAKE_ALARM_2 Status ' . $lakealarm1);
                        if ($lakealarm1 === "OFF") {
                            $data = write_data("1", "0010", 0, 0);
                        }
                        if ($lakealarm1 === "ON") {
                            $data = write_data("1", "0010", floor(rand(5, 10)), 0);
                        }
                        fwrite(STDOUT, pack('H*', base_convert("00" . $data, 2, 16)));
                    break;
                    case "0100":
                        //get alarm status
                        $lakealarm1 = $m->get('LAKE_ALARM_BYPASS');
                        if (empty($lakealarm1)) {
                            //alarm not set, setting to OFF
                            $m->set('LAKE_ALARM_BYPASS', "OFF");
                            $lakealarm1  = "OFF";
                            logme('resetting LAKE_ALARM_BYPASS to OFF');
                        }
                        logme('Sending LAKE_ALARM_BYPASS Status ' . $lakealarm1);
                        if ($lakealarm1 === "OFF") {
                            $data = write_data("1", "0100", 0, 0);
                        }
                        if ($lakealarm1 === "ON") {
                            $data = write_data("1", "0100", floor(rand(5, 10)), 0);
                        }
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
                        simcrash("Tried to write to read only port for lake level!");
                    break;
                    case "0001":
                        logme("Setting LAKE_ALARM_1 to ".$res["value"]);
                        if ($res["value"] === "0000") {
                            $m->set('LAKE_ALARM_1', "OFF");
                            $data = write_data("0", $res["port"], 0, 0);
                            fwrite(STDOUT, pack('H*', base_convert("00" . $data, 2, 16)));
                        } else {
                            $m->set('LAKE_ALARM_1', "ON");
                            $data = write_data("0", $res["port"], floor(rand(5, 10)), 0);
                            fwrite(STDOUT, pack('H*', base_convert("00" . $data, 2, 16)));
                        }
                    break;
                    case "0010":
                        logme("Setting LAKE_ALARM_2 to ".$res["value"]);
                        if ($res["value"] === "0000") {
                            $m->set('LAKE_ALARM_2', "OFF");
                            $data = write_data("0", $res["port"], 0, 0);
                            fwrite(STDOUT, pack('H*', base_convert("00" . $data, 2, 16)));
                        } else {
                            $m->set('LAKE_ALARM_2', "ON");
                            $data = write_data("0", $res["port"], floor(rand(5, 10)), 0);
                            fwrite(STDOUT, pack('H*', base_convert("00" . $data, 2, 16)));
                        }
                    break;
                    case "0001":
                        logme("Setting LAKE_ALARM_BYPASS to ".$res["value"]);
                        if ($res["value"] === "0000") {
                            $m->set('LAKE_ALARM_BYPASS', "OFF");
                            $data = write_data("0", $res["port"], 0, 0);
                            fwrite(STDOUT, pack('H*', base_convert("00" . $data, 2, 16)));
                        } else {
                            $m->set('LAKE_ALARM_BYPASS', "ON");
                            $data = write_data("0", $res["port"], floor(rand(5, 10)), 0);
                            fwrite(STDOUT, pack('H*', base_convert("00" . $data, 2, 16)));
                        }
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
