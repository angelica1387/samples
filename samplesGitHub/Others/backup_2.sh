#!/bin/bash
 ######## Bash script para hacer mantenimiento y backup de bases de datos especificas ########
 
 dbcxn="-h localhost -p 5432 -U postgres -p postgres";    # Datos de conexion
 
 rtdir="/var/backups/postgresql";        # Directorio de backups
 rtdir_remota="/home/angelina/carpeta_remota";

 fecha_borrado=$(date +%d-%m-%Y --date='30 days ago')
 
 # Base de datos a resguardar (nombres_de_dbs_en_ascii_sin_espacios separados_por_espacios)
 dblst=( SIDDHH bd_sigesp_2012 );
 
 # Directorio de backup
 dirdt=`eval date +%Y%m%d`;             # Fecha para el directorio
 bkdir=$rtdir"/backup-"$dirdt;            # Direccion absoluta directorio
 bkdir_remota=$rtdir_remota"/backup-"$dirdt;
 if [ ! -d $bkdir ]; then
   echo "Creando directorio: "$bkdir" ";
   /bin/mkdir $bkdir
 fi
if [ ! -d $bkdir_remota ]; then
   echo "Creando directorio: "$bkdir_remota" ";
   /bin/mkdir $bkdir_remota
 fi
 
 # Boocle para vacum, reparacion, y backup
 dbsc=0;
 dbst=${#dblst[@]};
 while [ "$dbsc" -lt "$dbst" ]; do
   dbsp=${dblst[$dbsc]};
   dbspf=""$bkdir"/"$dbsp"";            # Prefijo (dir+nom+fecha) nombre de archivo
   echo "";
   echo "######################################################";
   echo "Procesando base de datos '"$dbsp"'";
 
   echo "  * Realizando reindexado de: '"$dbsp"'";
   ridt=`eval date +%Y%m%d`;
   /usr/bin/reindexdb $dbcxn -d $dbsp -e > $dbspf"-"$ridt"-reindexdb.log" 2>&1
   cp $dbspf"-"$ridt"-reindexdb.log" $bkdir_remota
   echo "  * Realizando vacuum de: '"$dbsp"'";
   vadt=`eval date +%Y%m%d`;
   /usr/bin/vacuumdb $dbcxn -f -v -d $dbsp > $dbspf"-"$vadt"-vacuumdb.log" 2>&1
 
   echo "  * Realizando copia de seguridad de: '"$dbsp"'";
   bkdt=`eval date +%Y%m%d`;
   /usr/bin/pg_dump -i $dbcxn -F c -b -v -f $dbspf"-"$bkdt".backup" $dbsp > $dbspf"-"$bkdt"-backup.log" 2>&1
 
   echo "######################################################";
   echo "";
 
   dbsc=`expr $dbsc + 1`;
 done
 exit 0;
