#!/bin/bash
#
# HyDre - install hydre schema within RESTo database
#
# Author : Jerome Gasperi @ CNES
# Date   : 2014.01.17
# Version: 2.0
#

# Paths are mandatory from command line
SUPERUSER=postgres
DROPFIRST=NO
RESTO_USER=resto
RESTO_SUSER=sresto
DB=resto
SCHEMA=`dirname $0`/hydreDB.sql
HYDRE_HOME=
usage="## HyDre schema installation\n\n  Usage $0 -g <HYDRE_HOME> [-s <database SUPERUSER (default postgres)> -F (WARNING - suppress existing hydre schema within $DB database)]\n\n   IMPORTANT : resto database must be installed before launching this script\n\n"
while getopts "g:s:hF" options; do
    case $options in
        g ) HYDRE_HOME=`echo $OPTARG`;;
        s ) SUPERUSER=`echo $OPTARG`;;
        F ) DROPFIRST=YES;;
        h ) echo -e $usage;;
        \? ) echo -e $usage
            exit 1;;
        * ) echo -e $usage
            exit 1;;
    esac
done
if [ "$HYDRE_HOME" = "" ]
then
    echo -e $usage
    exit 1
fi
if [ "$DROPFIRST" = "YES" ]
then
psql -U $SUPERUSER -d $DB << EOF
DROP SCHEMA hydre CASCADE;
EOF
fi

# Install schema
psql -d $DB -U $SUPERUSER -f $SCHEMA

# Set indices
psql -U $SUPERUSER -d $DB << EOF
CREATE INDEX products_keywords_idx ON hydre.products USING GIN (keywords);
CREATE INDEX products_startdate_idx ON hydre.products (startdate);
CREATE INDEX products_completiondat_idx ON hydre.products (completiondate);
CREATE INDEX products_geometry_idx ON hydre.rivers USING GIST (geom);
CREATE INDEX measures_parentidentifier_idx ON hydre.measures (parentidentifier);
EOF

# Insert basins
shp2pgsql -d -W LATIN1 -s 4326 -I $HYDRE_HOME/installation/geodata/basins/GRDC_405_basins_from_mouth.shp basins | psql -d $DB -U $SUPERUSER
psql -U $SUPERUSER -d $DB << EOF
ALTER TABLE basins SET SCHEMA hydre;
ALTER TABLE hydre.basins ADD COLUMN bbox VARCHAR(250);
UPDATE hydre.basins SET bbox=replace(replace(replace(box2D(geom)::text, 'BOX(', ''), ')', ''), ' ', ',');
EOF

# Rights
psql -U $SUPERUSER -d $DB << EOF
GRANT SELECT on hydre.products to $RESTO_USER;
GRANT ALL ON SCHEMA hydre to $RESTO_USER;
GRANT SELECT on hydre.products to $RESTO_USER;
GRANT SELECT on hydre.measures to $RESTO_USER;

ALTER SCHEMA hydre OWNER TO $RESTO_SUSER;
GRANT ALL ON SCHEMA hydre to $RESTO_SUSER;
GRANT SELECT,INSERT,UPDATE,DELETE ON hydre.products TO $RESTO_SUSER;
GRANT SELECT,INSERT,UPDATE,DELETE ON hydre.measures TO $RESTO_SUSER;

EOF
