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
psql -d $DB -U $SUPERUSER << EOF
-- ===================
--
-- Hyde - set up hydre database for Hydroweb project
--
-- Author : Jerome Gasperi @ CNES
-- Date   : 2014.01.17
-- Version: 2.0
--
-- ==================

-- ==================
--
-- Hydroweb tables are stored within 'hydre' schema
--
-- ==================

CREATE SCHEMA hydre;

-- ===================
--
-- HyDre 'products' table contains two differents objects - Rivers and Lakes
--
--
-- Rivers
--
-- Exemple :
--
--  station=bra_bra_JA2_0053_01;river=Brahmaputra;basin=Brahmaputra;lat=26.7606;lon=93.4872;ref=GGM02C;ref_value=0.000;date=2011/11/15;type=operational;diff=restricted
--  #
--  # water height from satellite altimetry : Jason2 Track 0053
--  # corrections applied : solid earth tide, pole tide, ionospheric delay
--  #                       wet and dry tropospheric delay
--  # The heights are computed above a geoid given in the header (ref, ref_value (m))
--  #
--  # Data file format
--  # (1): decimal year, (2): date = aaaa/mm/dd, (3): time = hh:mn
--  # (4): height above surface of ref (m), (5): standard deviation from height (m),
--  # (6): number high frequency measurements to compute the height, (7): cycle number
--  # undef values=999.999
--  #
--  2008.5331 ; 2008/06/11 ; 16:59 ;    75.611 ;    1.426 ;    0 ;  ; 
--  2008.5602 ; 2008/06/21 ; 14:32 ;    76.072 ;    0.163 ;    0 ;  ; 
--
-- Remarque sur le niveau des produits en entrée
--
--      Pacholczyk Philippe <Philippe.Pacholczyk@cnes.fr>
-- 	29 novembre 2013 09:56:46 HNEC
--
--      A mon avis, mais cela n'engage que moi :
--      1) Les mesures en entrée seront des IGDR et des GDR. Il n'y a pas d'autres mesures
--      que les IGDR et GDR.Une mesure unitaire en sortie est issue soit d'IGDRs, soit de GDRs,
--      mais pas des deux. Dans une série temporelles de mesures sur un lac ou un fleuve il peut y avoir
--      des mesures issues des deux entrées. Il me semble important de savoir de quel type de mesure en entrée
--      est issue une mesure en sortie. Cela peut aussi intéresser certains utilisateurs de le savoir lors de la sélection.
--      Les mesures IGDR et GDR proviennent de différentes missions (Envisat, ERS, jason, ...) qui sont des critères
--      de sélection pour l'utilisateur.
--      2) Dans la classification du Pôle Théia ce sont des niveaux 3B : synthèses temporelles de paramètres
--      biophysiques (cf spec CGTD Théia).
--
--
-- Lakes
--
-- Exemple :
--
--  lake=balkhash;country=Kazakstan;basin=Balkhash;lat= 45.73;lon= 76.42;date=2012/12/12;first_date= 1992.738;last_date= 2011.861;type=operationnal;diff=public
--   
--  # length: 600 km
--  # width: 70 km
--  # maximum of depth: 26 m
--  # Mean area: 18200 km2
--  # Catchment Area: 413000 km2
--  # Mean Volume: 106 km3
--  
--  # water height from satellite altimetry:
--  # topex / poseidon  track number: 233  90 166  55 166 242
--  # jason2            track number: 233  90  55 166
--  # jason             track number: 233  90  55 166 166
--  # envisat           track number: 812 268 726 395 853 309 767 223 182 640  96 554 681 137 595  51
--  
--  # corrections applied: Soldi Earth tide, pole tide, ionospheric delay
--  # wet and dry tropospheric delay, altimeter biaises
--  
--  # surface of reference: GGMO2C; high resolution global gravity model
--  # developped to degree and order 200 at CSR
--  # Center for Space research, university of Texas, austin, USA
--  # ref: Tapley B, Ries J, Bettatpur S, et al., (2005),
--  # GGM02 - an improved Earth Gravity field from GRACE, J.geod. 79: 467-478
--  
--  # first date: 1992 9 26  yr month day  2.0  hours  35  minutes
--  # last date: 2011 11 10  yr month day  6.0  hours 21  minutes
--  
--  # data file format
--  # (1): decimal year, (2): date = yyyy/mm/dd, (3): time = hh:mm
--  # (4): heigth above surface of ref (m), (5): standard deviation from heigth (m)
--  # (6): area (km2), (7): volume with respect to volume of first date (km3)
--  
--  # The water level, surface and volume algorithm developed at Legos, Toulouse, France
--  
--  1992.738 ; 1992/09/26 ; 02:35 ;  341.28 ;    0.01 ;  ;  ; 
--  1992.794 ; 1992/10/16 ; 14:29 ;  341.32 ;    0.15 ;  ;  ; 
--
--
--
--
-- ===================
CREATE TABLE hydre.products (

        -- For rivers - header : station
        -- For lakes - header : lake
	identifier              VARCHAR(50) PRIMARY KEY, -- RESTo mapping = identifier
        
        -- For rivers - 'R'
        -- For lakes - 'L'
        hydretype                VARCHAR(1), -- Define the type of product : 'R' (river) or 'L' (lake)

        -- For rivers - track 
        -- For lakes - NULL
        tracknumber             INTEGER, -- RESTo mapping = orbitNumber

        -- For rivers - 'WATERHEIGHT'
        -- For lakes - 'WATERHEIGHT'
        producttype             VARCHAR(50), -- RESTo mapping = productType

        -- For rivers - 'LEVEL3B'
        -- For lakes - 'LEVEL3B'
        processinglevel         VARCHAR(50), -- RESTo mapping = processingLevel

        -- For rivers - header: YYY dans station xxx_xxx_YYY_xxxx_xx
        -- For lakes - NULL
        platformname            VARCHAR(10), -- RESTo mapping = platform

        -- For rivers - header : date
        -- For lakes - header : date
        modifieddate            TIMESTAMP, -- RESTo mapping = updated

        -- For rivers - now()
        -- For lakes - now()
        publisheddate           TIMESTAMP, -- RESTo mapping = published

        -- For rivers - first date of measure
        -- For lakes - first date of measure
        startdate               TIMESTAMP, -- RESTo mapping = startDate

        -- For rivers - last date of measure
        -- For lakes - last date of measure
        completiondate          TIMESTAMP, -- RESTo mapping = endDate

        -- For rivers - header : river
        -- For lakes - NULL
        river                   VARCHAR(50), -- name of river 

        -- For rivers - NULL
        -- For lakes - header : lake
        lake                   VARCHAR(50), -- name of lake

        -- For rivers - header : basin
        -- For lakes - header : basin
        basin                   VARCHAR(50), -- name of basin
        
        -- For rivers - header : ref
        -- For lakes - NULL
        geoidref                VARCHAR(50), -- geoid reference

        -- For rivers - header : ref_value
        -- For lakes - NULL
        geoidval                NUMERIC(12,4), -- geoid reference value

        -- For rivers - NULL
        -- For lakes - header : country
        country                 VARCHAR(50), -- name of country where the lake is

        -- For rivers - header : type
        -- For lakes - header : type
        status                  VARCHAR(20), 

        -- For rivers - header : diff
        -- For lakes - header : diff
        diffusion               VARCHAR(20),

        -- For rivers - Every lines starting with '#'
        -- For lakes - Every lines starting with '#'
        comments                TEXT,

        keywords                hstore DEFAULT ''
);

--
-- Height measures
-- 
-- Each product is associated with several measures (one per date)
--
CREATE TABLE hydre.measures (

        -- For rivers : concatenation of 'parentidentifier:decimal year'
        -- For lakes : concatenation of 'parentidentifier:decimal year'
	identifier              VARCHAR(50) PRIMARY KEY,

        -- For rivers : hydre.products.identifier
        -- For lakes : hydre.products.identifier
        parentidentifier        VARCHAR(50),

        -- For rivers : -- data : (1)
        -- For lakes : -- data : (1)
        measuredate             NUMERIC(8,4),

        -- For rivers : -- data : (4)
        -- For lakes : -- data : (4)
        height                  NUMERIC(12,4),

        -- For rivers : -- data : (5)
        -- For lakes : -- data : (5)
        stdev                   NUMERIC(12,4),

        -- For rivers : -- data : (6)
        -- For lakes : NULL
        hfm                     INTEGER,
        
        -- For rivers : -- data : (7)
        -- For lakes : NULL
        cyclenumber             INTEGER,

        -- For rivers : NULL
        -- For lakes : -- data : (6)
        area                    INTEGER,

        -- For rivers : NULL
        -- For lakes : -- data : (7)
        volume                  INTEGER,

        -- For rivers : -- data : (8)
        -- For lakes : -- data : (8)
        flag                  INTEGER
);

-- ============================================================
-- GEOMETRY COLUMNS
-- ============================================================
SELECT AddGeometryColumn('hydre', 'products','geom','4326','POINT',2);

EOF

# Set indices
psql -U $SUPERUSER -d $DB << EOF
CREATE INDEX products_keywords_idx ON hydre.products USING GIN (keywords);
CREATE INDEX products_startdate_idx ON hydre.products (startdate);
CREATE INDEX products_completiondat_idx ON hydre.products (completiondate);
CREATE INDEX products_geometry_idx ON hydre.rivers USING GIST (geom);
CREATE INDEX measures_parentidentifier_idx ON hydre.measures (parentidentifier);
EOF

# Insert basins
shp2pgsql -g geom -d -W LATIN1 -s 4326 -I $HYDRE_HOME/mapserver/geodata/basins/GRDC_405_basins_from_mouth.shp basins | psql -d $DB -U $SUPERUSER
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
