<?php

$input = str_replace(' ', '', "00011000 00000011 00000001 10000011 11000111 11000000");
$enckey = [0, 9, 15, 14, 8, 2, 0, 7, 12, 3, 15, 13, 8, 5, 13, 1, 9, 9, 0, 8, 12, 13, 8, 2, 11, 1, 7, 11, 8, 12, 4, 15, 12, 14, 1, 7, 10, 6, 7, 12, 4, 8, 15, 0, 6, 2, 12, 10, 9, 14, 0, 13, 14, 12, 15, 0, 13, 0, 14, 15, 15, 13, 5, 0, 13, 9, 4, 8, 3, 12, 6, 6, 10, 14, 14, 8, 14, 1, 1, 8, 2, 12, 8, 4, 0, 7, 3, 9, 3, 12, 5, 5, 2, 0, 14, 7, 8, 15, 15, 7, 13, 5, 8, 8, 13, 11, 9, 13, 3, 15, 3, 13, 3, 3, 11, 12, 2, 9, 7, 15, 9, 3, 11, 0, 8, 6, 2, 1, 5]; // done with a D16 Dice but I substraced by one for everything
$fourbit = [
    "0000" => 0,
    "0001" => 1,
    "0010" => 2,
    "0011" => 3,
    "0100" => 4,
    "0101" => 5,
    "0110" => 6,
    "0111" => 7,
    "1000" => 8,
    "1001" => 9,
    "1010" => 10,
    "1011" => 11,
    "1100" => 12,
    "1101" => 13,
    "1110" => 14,
    "1111" => 15
];


readline_callback_handler_install('', function(){});
//checks the par, give it data and the par bit in string binary
function checkpar($data, $par)
{
    $count = substr_count($data, '1');
    $one = 1;
    $bitwiseAnd = $count & $one;
    if ($bitwiseAnd == 1) {
        $outpar = 1;
    } else {
        $outpar = 0;
    }
    if ($par == $outpar) {
        return true;
    } else {
        return false;
    }
}


//give it string binary to get back a par
function calcpar($data)
{
    $count = substr_count($data, '1');
    $one = 1;
    $bitwiseAnd = $count & $one;
    if ($bitwiseAnd == 1) {
        $outpar = "1";
    } else {
        $outpar = "0";
    }
    return $data . $outpar;
}


//log shit to screen
function logme($line)
{
    $date = date(DATE_ATOM);
    fwrite(STDERR, "[$date]> $line" . PHP_EOL);
}


//reads data as a string of 1/0s and gives its output from WEP
//$output = ["port" => $port, "action" => $actionbit, "value" => $decoded_value, "mfg" => $mfg];
function data_reader($data)
{
    global $enckey;
    global $fourbit;
    //monkey patch to fix padding issues
    $data = "000".base_convert(bin2hex($data), 16, 2);
    if(strlen($data) !== 40){
        logme("Bad Data Len " . strlen($data) . " !== 40");
        return false;
    }
    $data = substr($data, 0, 42);
    logme("Reading Data : $data");
    //check if header bits exist
    $header = substr($data, 0, 3);
    if ($header !== "000") {
        logme("INVAILD HEADER: $header");
        return false;
    }

    //Get Action Bit
    $actionbit = substr($data, 3, 1);

    if (!checkpar($actionbit, substr($data, 4, 1))) {
        logme("Action Bit Par Failure!");
        return false;
    }

    //Get Port
    $port = substr($data, 5, 4);
    //Check Port Parity
    if (!checkpar($port, substr($data, 9, 1))) {
        logme("Port Par Failure!");
        return false;
    }

    //Get Sign
    $numbersign = substr($data, 10, 1);
    if ($numbersign === "0") {
        $sign = "-";
    } else {
        $sign = "";
    }
    //splitting number apart
    $wholenumber = bindec(substr($data, 11, 7));
    $decsign = substr($data, 18, 1);
    //check value par
    if (!checkpar(substr($data, 11, 7) . substr($data, 18, 1), substr($data, 19, 1))) {
        logme("ValueWhole Par Failure!");
        return false;
    }
    if ($decsign === "0") {
        $subnumber = "." . bindec(substr($data, 20, 7));
    } else {
        $subnumber = ".0";
    }
    //checking subnumber par
    if (!checkpar(substr($data, 20, 7), substr($data, 27, 1))) {
        logme("SubNumber Par Failure!");
        return false;
    }
    //force to float
    $decoded_value = "$sign$wholenumber$subnumber" + 0;

    //Get Mfg
    $mfg = bindec(substr($data, 28, 6));
    //Check Mfg Par
    if (!checkpar(substr($data, 28, 6), substr($data, 34, 1))) {
        logme("Mfg Par Failure!");
        return false;
    }
    logme("Port: $port Value: $decoded_value Mfg: $mfg Action: $actionbit");

    //calc checksum

    $chkstring = $header . $actionbit . $port . substr($data, 10, 1) . substr($data, 11, 7) . substr($data, 18, 1) . substr($data, 20, 7) . substr($data, 28, 6);
    $countchk = substr_count($chkstring, '1');
    $truechksum = $enckey[$countchk];
    if ($truechksum == bindec(substr($data, 35, 4))) {
        //logme("Good Checksum! Data OK!");
        $output = ["port" => $port, "action" => $actionbit, "value" => $decoded_value, "mfg" => $mfg];
        return $output;
    } else {
        logme("Bad Chksum!");
        return false;
    }
    return false;
}
/// See protocol docs for your PLC to see what to write to. for basic reads of ports, do data_writer("1", "0000", (int) 5, "0000", "0000") and it will return the binary to be sent to the PLC in a string format
function data_writer($action, $port, $value, $mfg1, $mfg2){
    global $enckey;
    global $fourbit;
    //sane check
    if($value > 15 ){
        logme("ERROR: $value > 15");
        return false;
    }
    //logme("Writing: action: $action, Port: $port, Number: $value, Mfg1: $mfg1, Mfg2: $mfg2");
    //calc checksum
    $chkstring = "111" . $action . $port . (string) sprintf("%04b",$value) . $mfg1 . $mfg2;
    $countchk = substr_count($chkstring, '1');
    $truechksum = $enckey[$countchk];
    $chksum = sprintf("%04b", $truechksum);
    //logme("Chksum:  $chksum | $countchk | $truechksum");
    //make final output
    //logme("Value: ". (string) sprintf("%04b",$value));
    $output = "111" . calcpar($action) . calcpar($port) . calcpar((string) sprintf("%04b",$value)) . calcpar($mfg1) . calcpar($mfg2) . $chksum . "00000000000";
    if(strlen($output) !== 40){logme("Output too short! Something is wrong with your inputs!");}
    return pack('H*', base_convert($output, 2, 16));

}

for ($x = 0; $x <= 15; $x++) {
    $fp = fsockopen("127.0.0.1", 2023, $errno, $errstr, 30);
    if (!$fp) {
        logme("TCP: $errstr");
    } else {
        echo PHP_EOL."=========== reading from port $x".PHP_EOL;
        $data = data_writer("1", (string) sprintf("%04b",$x), floor(rand(0, 15)), "1111", "1111");
        fwrite($fp, $data);
        while (!feof($fp)) {
            $data = data_reader(fgets($fp, 6));
            echo PHP_EOL.json_encode($data).PHP_EOL;
            continue;
            break;
        }
        fclose($fp);
    }
}
