#!/bin/bash

RESTO_HOME=""
HYDRE_HOME=""
HYDRE_TARGET=""
# Paths are mandatory from command line
usage="## HyDre deployment\n\n  Usage $0 -r <RESTO_HOME> -s <HYDRE_HOME> -t <HYDRE_TARGET>\n"
while getopts "s:r:t:h" options; do
    case $options in
        r ) RESTO_HOME=`echo $OPTARG`;;
        s ) HYDRE_HOME=`echo $OPTARG`;;
        t ) TARGETDIR=`echo $OPTARG`;;
        h ) echo -e $usage;;
        \? ) echo -e $usage
            exit 1;;
        * ) echo -e $usage
            exit 1;;
    esac
done
if [ "$RESTO_HOME" = "" ]
then
    echo -e $usage
    exit 1
fi
if [ "$HYDRE_HOME" = "" ]
then
    echo -e $usage
    exit 1
fi
if [ "$TARGETDIR" = "" ]
then
    echo -e $usage
    exit 1
fi

if [ -d "$TARGETDIR" ]; then
    if [ "$(ls $DIR)" ]; then
        rm -Rf $TARGETDIR/css $TARGETDIR/js $TARGETDIR/resto $TARGETDIR/.htaccess $TARGETDIR/favicon.ico $TARGETDIR/index.php
    fi
fi

mkdir $TARGETDIR
echo " ==> Deploy RESTo to $TARGETDIR directory"
cp -Rf $RESTO_HOME/.htaccess $RESTO_HOME/favicon.ico $RESTO_HOME/index.php $RESTO_HOME/css $RESTO_HOME/js $RESTO_HOME/resto $TARGETDIR
cp -Rf $HYDRE_HOME/src/resto/HyDreController.php $TARGETDIR/resto/controllers/ && echo ' ==> Copy HyDreController to '$TARGETDIR'/resto/controllers/ directory'
cp -Rf $HYDRE_HOME/src/resto/HydreResourceManager.php $TARGETDIR/resto/modules/ && echo ' ==> Copy HyDreResourceManager to '$TARGETDIR'/resto/modules/ directory'
cp -Rf $HYDRE_HOME/src/resto/resto.ini $TARGETDIR/resto/ && echo ' ==> Copy HyDreResourceManager to '$TARGETDIR'/resto/ directory'
cp -Rf $HYDRE_HOME/src/resto/.htaccess $TARGETDIR/ && echo ' ==> Copy HyDreResourceManager to '$TARGETDIR'/ directory'
cp -Rf $HYDRE_HOME/src/mapshup/config.js $TARGETDIR/js/config/default/ && echo ' ==> Copy mapshup/config.js to '$TARGETDIR'/js/config/default/ directory'

echo " ==> Now, do not forget to check $TARGETDIR/resto/resto.ini configuration !"
