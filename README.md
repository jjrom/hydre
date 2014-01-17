hyde
====

HyDe - Hydroweb Distribution server - est l'application de distribution des séries temporelles de hauteurs d'eau (lacs et fleuves) du projet Hydroweb.

L'application HyDe repose sur [RESTo] (http://github.com/jjrom/resto), [mapshup] (http://github.com/jjrom/mapshup) et [iTag] (http://github.com/jjrom/itag)

Installation
============

Pré-requis
----------

* git (ligne de commande)
* Apache (v2.0+) avec le support de mod_rewrite
* PHP (v5.3+) avec les extension curl et XMLWriter
* PostgreSQL (v9.0+) avec l'extension hstore (installée par défaut avec postgres)
* PostGIS (v1.5.1+)

Note: HyDe peut être installer avec des logiciels de versions inférieures à celles spécifiées...mais le fonctionnement nominale ne peut être garanti dans ce cas.


Préparation
-----------
Nous supposons que $HYDE_HOME est le répertoire dans lequel sont installées les sources de l'application

        export HYDE_HOME=/repertoire/sources/hyde

Par ailleurs, $HYDE_TARGET est le répertoire dans lequel sera installée l'application. Ce répertoire doit être vide !!

        export HYDE_TARGET=/repertoire/installation/hyde

Si ce n'est pas déjà fait, téléchargez les sources de HyDe dans $HYDE_HOME

        git clone https://github.com/jjrom/hyde.git $HYDE_HOME


Installation de RESTo
---------------------

### Téléchargement des sources

        # Positionnez la variable $RESTO_HOME
        export RESTO_HOME=$HYDE_HOME/resto
        rm -Rf $RESTO_HOME

        # Récupération des sources à jour de RESTo 
        git clone https://github.com//jjrom/resto.git/ $RESTO_HOME
        
        # Positionnement de RESTo sur une version stable pour HyDe
        cd $RESTO_HOME
        git checkout eaa77ab5d87400a732504d128d451489e2520feb
        
        # Suppression .git du répertoire $RESTO_HOME
        rm -Rf $RESTO_HOME/.git $RESTO_HOME/.gitignore
        

### Installation de la base de données

Puis effectuer UNIQUEMENT les étapes suivantes de la [procédure d'installation de RESTo] (https://github.com/jjrom/resto/blob/master/README.md) :
* Installation de la [base de données] (https://github.com/jjrom/resto/blob/master/README.md#install-resto-database)
* Installation du [Gazetteer] (https://github.com/jjrom/resto/blob/master/README.md#install-gazetteer)
* Installation de [iTag] (https://github.com/jjrom/resto/blob/master/README.md#install-itag-optional)


Installation de Hyde
--------------------

### Installation de la base de données

        # Creation du schema 'hyde' dans la base de donnees 'resto'
        $HYDE_HOME/installation/hydeInstallDB.sh -g $HYDE_HOME -s postgres -F


Deploiement
===========

Lancez le script de deploiement sur le repertoire cible $HYDE_TARGET

        $HYDE_HOME/installation/deploy.sh -s $HYDE_HOME -t $HYDE_TARGET


Configuration
=============

### Apache

Mettre en place un alias vers le repertoire d'installation $HYDE_TARGET. Pour cela editer le fichier de configuration
apache (généralement /etc/apache2/httpd.conf) et rajouter la règle suivante (en prenant soin de bien remplacer
$HYDE_TARGET par sa valeur) :

        Alias /hyde/ "$HYDE_TARGET"
        <Directory "$HYDE_TARGET">
            Options FollowSymLinks
            AllowOverride All
            Order allow,deny
            Allow from all
        </Directory>

### RESTo

Editer le fichier $RESTO_TARGET/.htaccess et remplacer la ligne

        RewriteBase /resto/

par la ligne 

        RewriteBase /hyde/

Editer le fichier $RESTO_TARGET/resto/resto.ini afin de mettre à jour les mots de passes
des comptes 'resto', 'sresto' et 'admin' [comme expliqué dans la procédure d'installation de RESTo] (https://github.com/jjrom/resto/blob/master/README.md#resto-configuration)

### Insertion de la collection dans RESTo

Lancer la commande suivante pour indiquer à RESTo les paramètres de la collection HyDe

        curl -X POST -F "file[]=@$HYDE_HOME/installation/Hyde.json" http://admin:nimda@localhost/hyde/

Note : remplacer 'admin:nimda' par le login et le mot de passe du compte admin défini dans RESTo (cf. étape précédente)


Utilisation
===========

### Insérer un fichier "rivière"

        curl -X POST -F "file[]=@$HYDE_HOME/examples/R_bra_bra_JA2_0053_01.txt" http://admin:nimda@localhost/hyde/hydroweb

### Insérer un fichier "lac"

        curl -X POST -F "file[]=@$HYDE_HOME/examples/L_albert.txt" http://admin:nimda@localhost/hyde/hydroweb

### Mettre à jour la description de la collection

Si vous modifiez la description de la collection (i.e. fichier $HYDE_HOME/installation/Hyde.json) il faut mettre à jour
la description de la collection dans RESTo. Pour cela il faut executer les commandes suivantes :

        # Suppresion de la collection
        curl --get -X DELETE -d "physical=true" http://admin:nimda@localhost/hyde/hydroweb
        
        # Insertion de la collection modifiée
        curl -X POST -F "file[]=@$HYDE_HOME/installation/Hyde.json" http://admin:nimda@localhost/hyde/

Installation (long story)
-------------------------
This is a step by step installation 

1. Set the paths

        export HYDROWEB_HOME=/your/hydroweb/homedir
        export HYDROWEB_TARGET=/your/hydroweb/targetdir

2.Install database

        $HYDROWEB_HOME/manage/installation/hydrowebInstallDB.sh -F -d $PATH_TO_POSTGIS_DIRECTORY -S DBSUPERSUSER -u $DBUSER -p $DBPASSWORD -g $HYDROWEB_HOME/mapserver/data

        # Exemple : $HYDROWEB_HOME/manage/installation/hydrowebInstallDB.sh -F -d /usr/local/share/postgis -S jrom -u hydroweb -p hydroweb
        
3. Ingest data files
        
        # Unzip data files
        cd $HYDROWEB_HOME/data
        unzip rivers.zip
        unzip lakes.zip

        # Ingest river files
        $HYDROWEB_HOME/manage/hydrowebIngestRiversFiles.php -f $HYDROWEB_HOME/data/rivers/

        # Ingest lakes files
        $HYDROWEB_HOME/manage/hydrowebIngestLakesFiles.php -f $HYDROWEB_HOME/data/lakes/

4. Tag with continents and countries

        # This step is optional
        # To perform this step you need to install iTag (https://github.com/jjrom/itag)
        #
        # Notes :
        #   - we suppose that iTag is installed in $ITAG_HOME
        #   - modify itag -d options accordingly to your database configuration
        #
        cd $ITAG_HOME
        php itag.php -d localhost:hydroweb:hydroweb:hydroweb:5432:rivers:identifier:geom -c -o hstore > /tmp/rivers.sql
        psql -d hydroweb -f /tmp/rivers.sql
        php itag.php -d localhost:hydroweb:hydroweb:hydroweb:5432:lakes:identifier:geom -c -o hstore > /tmp/lakes.sql
        psql -d hydroweb -f /tmp/lakes.sql
        rm -Rf /tmp/rivers.sql /tmp/lakes.sql

5. Install mapshup

        $HYDROWEB_HOME/build_mapshup.sh -a -t $HYDROWEB_TARGET


Notes
-----

1. How to create UTFGrids from basins shapefiles

        # Download create-utfgrids.git
        # Note that mapnik for python must be installed
        # (See https://github.com/mapnik/mapnik/wiki/MacInstallation_Homebrew for OS X)

        git clone https://github.com/Ecotrust/create-utfgrids.git

        # Generate UTFGrids
        ./create_utfgrids.py ../mapserver/data/basins/GRDC_405_basins_from_mouth.shp 0 12 basins


