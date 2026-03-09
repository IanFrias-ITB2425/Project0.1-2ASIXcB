# Autor: ASIXcB G5 - Alberto Trujillo, Rehan Farooq, Aleix Tomas, Ian Frias Reyes
# ------------------------------------------------------------------------------
#!/bin/bash
# Definir nom del fitxer amb data i hora (ex: db_backup_2026-02-10_1530.sql)
FILENAME="/docker/public/backups/db_backup_$(date +\%F_\%H\%M).sql"

# Executar el dump des de fora del contenidor
docker exec s6_mysql mysqldump -u root -p1234 extagram_db > $FILENAME

# (Opcional) Esborrar backups més vells de 7 dies per no omplir el disc
find /docker/public/backups -type f -name "*.sql" -mtime +7 -delete
