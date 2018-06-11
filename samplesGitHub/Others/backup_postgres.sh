#! /bin/sh
#############################################
# Date: 04/18/2013  
#############################################
BACKUP_DIR=/var/backups/postgresql/
BACKUP_DIR_REMOTO=REMOTE_HOST:/home/backup/
BACKUP_NUM=7
FECHA=$(date +%d-%m-%Y)
FECHA_BORRADO=$(date +%d-%m-%Y --date='30 days ago')
# Realizar Backup de las DB'S
databases=`su -l postgres -c 'psql -q -t -c "select datname from pg_database;" template1'`
for d in $databases; do
if [ ! -d $BACKUP_DIR/$d ];
then echo -n "Creando directorio de respaldo $BACKUP_DIR/$d... "
     su -l postgres -c "mkdir $BACKUP_DIR/$d" ] || continue
     echo "done."
fi
# Establecer cantidad maxima del mismo backup $BACKUP_NUM
archive=$BACKUP_DIR/$d/$d-$FECHA.gz
archive_delete=$BACKUP_DIR/$d/$d-$FECHA_BORRADO.gz
if [ -d $archive_delete ];
then
	rm $archive_delete
fi
echo -n "Respaldando la base $d... "
su -l postgres -c "(pg_dump $d |gzip -9) > $archive"
echo "Tarea Finalizada."
done
