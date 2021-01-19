#!/usr/bin/php
<?php
function logme($line)
{
    $plcname = gethostname() . "-debug";
    $date = date(DATE_ATOM);
    $team = file_get_contents("/opt/plc/team");
    if(empty($team)){
        $team = "NOTEAM-".gethostname();
    }
    fwrite(STDERR, "[$team][$plcname][$date]> $line" . PHP_EOL);
        $data = [
            "event" =>[
                "team" => $team,
                "message" => $line,
            ],
            "sourcetype" => "wepplc:debug",
            "host" => $plcname
        ];
        $json = json_encode($data);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,"https://splunk.cyberrange.rit.edu:8088/services/collector");
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json);  //Post Fields
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

        $headers = [
            'Authorization: Splunk 8025e831-0c37-4c1f-976b-2421aebe1e01'
        ];

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_HEADER, 1);
        $server_output = curl_exec ($ch);
}

function output($text)
{
        $output = str_split($text);
        foreach($output as $line){
                $add = rand(1, 20);
                if($add == 10){
                        $add = 500;
                }
                usleep($add*500);
                echo $line;
        }
}
while(1){
$ipaddress = trim(`hostname --ip-address`);
$firstmenu = <<<EOF


PLC DEBUG v0.1
[c] PLC-R-US 1994
======================
1> READ CPU REG
2> READ STATE DEBUG
3> DUMP FIRMWARE
4> DUMP CONFIG
5> CHANGE SAVED PARAM
6> ENABLE DEV MODE
7> PRINT DEBUG LOG
======================

EOF;

$reg1 = strtoupper(bin2hex(random_bytes(4)));
$reg2 = strtoupper(bin2hex(random_bytes(4)));
$reg3 = strtoupper(bin2hex(random_bytes(4)));
$reg4 = strtoupper(bin2hex(random_bytes(4)));
$reg5 = strtoupper(bin2hex(random_bytes(4)));
$reg6 = strtoupper(bin2hex(random_bytes(4)));
$reg7 = strtoupper(bin2hex(random_bytes(4)));
$reg8 = strtoupper(bin2hex(random_bytes(4)));

output($firstmenu);
$statedebug = <<<EOF

A: {$reg1}
B: {$reg2}
C: {$reg3}
D: {$reg4}
E: {$reg5}
X: {$reg6}
Y: {$reg7}
Z: {$reg8}

EOF;
$crashstack = <<<EOF
Exception (0): epc1={$reg1} epc2={$reg2} epc3={$reg3} excvaddr=0x{$reg4} depc=0x{$reg7}

ctx: sys
sp: {$reg7} end: {$reg8} offset: 01a0

>>>stack>>>
3ffffdb0:  40223e00 3fff6f50 00000010 60000600
3ffffdc0:  00000001 4021f774 3fffc250 4000050c
3ffffdd0:  400043d5 00000030 00000016 ffffffff
3ffffde0:  400044ab 3fffc718 3ffffed0 08000000
3ffffdf0:  60000200 08000000 00000003 00000000
3ffffe00:  0000ffff 00000001 04000002 003fd000
3ffffe10:  3fff7188 000003fd 3fff2564 00000030
3ffffe20:  40101709 00000008 00000008 00000020
3ffffe30:  c1948db3 394c5e70 7f2060f2 c6ba0c87
3ffffe40:  3fff7058 00000001 40238d41 3fff6ff0
3ffffe50:  3fff6f50 00000010 60000600 00000020
3ffffe60:  402301a8 3fff7098 3fff7014 40238c77
3ffffe70:  4022fb6c 40230ebe 3fff1a5b 3fff6f00
3ffffe80:  3ffffec8 00000010 40231061 3fff0f90
3ffffe90:  3fff6848 3ffed0c0 60000600 3fff6ae0
3ffffea0:  3fff0f90 3fff0f90 3fff6848 3fff6d40
3ffffeb0:  3fff28e8 40101233 d634fe1a fffeffff
3ffffec0:  00000001 00000000 4022d5d6 3fff6848
3ffffed0:  00000002 4000410f 3fff2394 3fff6848
3ffffee0:  3fffc718 40004a3c 000003fd 3fff7188
3ffffef0:  3fffc718 40101510 00000378 3fff1a5b
3fffff00:  000003fd 4021d2e7 00000378 000003ff
3fffff10:  00001000 4021d37d 3fff2564 000003ff
3fffff20:  000003fd 60000600 003fd000 3fff2564
3fffff30:  ffffff00 55aa55aa 00000312 0000001c
3fffff40:  0000001c 0000008a 0000006d 000003ff
3fffff50:  4021d224 3ffecf90 00000000 3ffed0c0
3fffff60:  00000001 4021c2e9 00000003 3fff1238
3fffff70:  4021c071 3ffecf84 3ffecf30 0026a2b0
3fffff80:  4021c0b6 3fffdab0 00000000 3fffdcb0
3fffff90:  3ffecf40 3fffdab0 00000000 3fffdcc0
3fffffa0:  40000f49 40000f49 3fffdab0 40000f49
<<<stack<<<

cpu frozečĆÀAĄDçcbʙ^M^M^M^M
EOF;

$config = <<<EOF
PLC DEBUG v0.1
[c] PLC-R-US 1994
======================
IP: $ipaddress
SUBNET: 255.255.255.0
COMPORT: TCP/502
PROTO: WEP PROTOv1
======================

CONFIG STACK:
$statedebug


EOF;
function crashplc(){
logme(`cat /dev/urandom | head -n 4 | nc 127.0.0.1 502 | xxd`);
}
output("CMD: ");
$line = readline("");
logme("Processing: ".$line);
switch ($line) {
        case '1':
		logme("state debug called");
                output($statedebug);
                break;
        case '2':
		logme("random crap being printed");
                output(`cat /dev/urandom | head | xxd`);
                break;
        case '3':
		logme("dumping firmware");
		//arduino mega signal logic-analyzer firmware 
                output(`cat firmware.txt`);
                break;
        case '4':
		logme("dumping config");
		output($config);
        break;
        case '5':
		logme("reading values and crashing");
		output("LOC>");
                $newline = readline("");
		output("VAL>");
                $newline2 = readline("");
		logme("LOC> $newline VAL> $newline2");
                output($crashstack);
		crashplc();
                die();
        break;  
        case '6':
		logme("fake debug reset and crash");
                output("RESETTING FIRMWARE... PLEASE WAIT...".PHP_EOL);
                $i = 0;
                while ($i <= 2048) {
                        output(bin2hex($i)."\r");
                        usleep(50000);
                        $i++;
                }
                output($crashstack);
		        crashplc();
                die();     
        case '7':
		logme("fake debug and crash");
                output($crashstack);
		        crashplc();
                die();
                break;
        default:
		logme("unknown data, crashing");
                output(`cat /dev/urandom | head -c 64`);
                output($crashstack);
		        crashplc();
                die();
                break;
}
}
