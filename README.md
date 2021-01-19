# CPTC-WEP-PLC

Please See "Spec.txt" for the protocol spec

Comms.php for a client to the PLCs

See the PLCs folder for PLCs

See Sim.php for a server

## Running via Docker
`docker build -t plc . && docker-compose up`

## Socatting to a PLC service
`socat file:plccommand.txt tcp:localhost:33023`

Replace the port with whatever Docker exposes on your local machine:

```
$ docker port wep-plc_plc_gen_flow_1_1
2023/tcp -> 0.0.0.0:33136
```

## Getting all IP addresses of docker containers

```for s in `docker-compose ps -q`; do echo ip of `docker inspect -f "{{.Name}}" $s` is `docker inspect -f '{{range .NetworkSettings.Networks}} {{.IPAddress}}{{end}}' $s`; done```