<?php
/// 111 1 1 0000 0 0000 0 1110 1 1101 1 1100 000
/// 111 1 1 1011 1 1100 1 1110 1 1110
///  STX R P 0 0 RND P RND P CHK ETX
// Reads from Port 0, Sending Zeros, with a checksum of 12
readline_callback_handler_install('', function(){});
//STATIC VALUES
///$input = str_replace(' ', '', "1111110111011001111011110100100000000000");
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

$m = new Memcached();
$m->addServer('localhost', 11211);

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
function simcrash($text)
{
    logme("SIM CRASH! $text");
}
function logme($line)
{
    $date = date(DATE_ATOM);
    fwrite(STDERR, "[$date]> $line" . PHP_EOL);
}

function read_data($data)
{
    global $enckey;
    global $fourbit;
    //patch for removing NULL BYTE at the front?
    logme("Reading Data : $data");
    if (strlen($data) !== 40) {
        simcrash("Message invaild! Len:" . strlen($data));
        return false;
    }
    //check if header bits exist
    $header = substr($data, 0, 3);
    if ($header !== "111") {
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

    //Get Set Value
    $setvalue = substr($data, 10, 4);
    if (!checkpar($setvalue, substr($data, 14, 1))) {
        logme("Set Par Failure!");
        return false;
    }

    // check mfg bit for par, reject message if not set
    $randdata1 = substr($data, 15, 4);
    if (!checkpar($randdata1, substr($data, 19, 1))) {
        logme("Set Par Failure!");
        return false;
    }
    // check mfg bit for par, reject message if not set
    $randdata2 = substr($data, 20, 4);
    if (!checkpar($randdata2, substr($data, 24, 1))) {
        logme("Set Par Failure!");
        return false;
    }

    //check footer
    $foot = substr($data, 29, 3);
    if ($foot !== "000") {
        logme("Message had no feet! $foot");
        return false;
    }
    //calc checksum
    //lookup check in conversion list
    $chksum= $fourbit[substr($data, 25, 4)];
    $chkstring = $header . $actionbit . $port . $setvalue . $randdata1 . $randdata2;
    $count = substr_count($chkstring, '1');
    $truechksum = $enckey[(int) $count];
    if ($truechksum === $chksum) {
        logme("Good Checksum! Data OK!");
        $output = ["act" => $actionbit, "port" => $port, "value" => $setvalue, "mfg1" => $randdata1, "mfg2" => $randdata2];
        return $output;
    } else {
        logme("Bad Chksum! $chksum | $count | $truechksum | " . substr($data, 25, 4) );
        return false;
    }
    return false;
}

function write_data($type, $port, $number, $mfg1)
{
    global $enckey;
    global $fourbit;
    logme("Writing: Type: $type, Port: $port, Number: $number, Mfg1: $mfg1");
    if(abs($number) > 126.00){
        simcrash("LOGIC ERROR, TRIED TO WRITE $number > 126.00");
        return false;
    }
    if(abs($mfg1) > 15){
        simcrash("LOGIC ERROR, TRIED TO WRITE $mfg1 > 15");
        return false;
    }
    $output = "00000" . calcpar($type) . calcpar($port);

    //calc number output
    if ($number < 0) {
        $sign = "0";
    } else {
        $sign = "1";
    }
    $output .= $sign;

    $explodingnumbers = explode(".", $number);
    $binnumwhole = sprintf("%07b", abs($explodingnumbers[0]));
    logme("Wholenumberbin: $binnumwhole");
    if (!empty($explodingnumbers[1])) {
        $binnumnotwhole = sprintf("%07b", substr($explodingnumbers[1], 0, 2));
        $dec = "0";
        $output .= calcpar($binnumwhole . $dec) . calcpar($binnumnotwhole);
    } else {
        $binnumnotwhole = sprintf("%07b", floor(rand(0, 126)));
        $dec = "1";
        $output .= calcpar($binnumwhole . $dec) . calcpar($binnumnotwhole);
    }
    logme("Wholenumberbin: $dec $binnumnotwhole");
    //mfg code
    $output .= calcpar(sprintf("%06b", $mfg1));
    logme("Mfg1binnum: " . sprintf("%06b", $mfg1));

    $chkstring = "000" . $type . $port . $sign . $binnumwhole . $dec . $binnumnotwhole . sprintf("%06b", $mfg1);
    //chksum calc
    $count = substr_count($chkstring, '1');
    $truechksum = $enckey[(int) $count];
    logme("Chkv $truechksum");
    $output .= sprintf("%04b", $truechksum). "111000000";
    logme("Data Sent: $output");
    return $output;
}
