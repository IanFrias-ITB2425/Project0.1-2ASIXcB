#!/bin/bash

# Leemos la URL desde el archivo secreto
URL=$(cat /usr/local/bin/.discord_secret)

IP=$1
JAIL=$2

# El resto del script se mantiene igual...
PAYLOAD="{\"embeds\": [{
  \"title\": \"🛡️ Fail2Ban: Bloqueig Activat\",
  \"color\": 15158332,
  \"fields\": [
    {\"name\": \"IP Atacant\", \"value\": \"$IP\", \"inline\": true},
    {\"name\": \"Servei detectat\", \"value\": \"$JAIL\", \"inline\": true},
    {\"name\": \"Acció\", \"value\": \"La IP ha estat banejada per seguretat.\", \"inline\": false}
  ],
  \"footer\": { \"text\": \"Servidor Ubuntu AWS\" }
}]}"

curl -X POST -H "Content-Type: application/json" -d "$PAYLOAD" "$URL"
