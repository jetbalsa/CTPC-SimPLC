# CPTC-WEP-PLC

Please See "Spec.txt" for the protocol spec

Comms.php for a client to the PLCs

See the PLCs folder for PLCs

See Sim.php for a server

See things under GAMEMASTER for the backend state

See things under PLCS for the on-network PLCs

See PLC-HONEY is the debug server on port 8080

See Supervisor for something that talks to the PLCs and manages the dam

## Socatting to a PLC service
`socat file:plccommand.txt tcp:localhost:502`

`/usr/bin/socat tcp-l:8080,reuseaddr,fork exec:"/usr/bin/php /opt/plc/plc-honey/server.php`

`while true; do /usr/bin/php /opt/plc/GAMEMASTER/dam-gamemaster.php; sleep 15; done`

`/usr/bin/socat tcp-l:502,reuseaddr,fork exec:"/usr/bin/php /opt/plc/PLCs/dam/lake-level.php`

