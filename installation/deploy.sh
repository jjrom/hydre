#!/bin/bash

# Paths are mandatory from command line
usage="## HyDre deployment\n\n  Usage $0 -s <HYDRE_HOME> -t <HYDRE_TARGET>\n"
while getopts "s:t:h" options; do
    case $options in
        s ) SRCDIR=`echo $OPTARG`;;
        t ) TARGETDIR=`echo $OPTARG`;;
        h ) echo -e $usage;;
        \? ) echo -e $usage
            exit 1;;
        * ) echo -e $usage
            exit 1;;
    esac
done
if [ "$SRCDIR" = "" ]
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
cp -Rf $SRCDIR/resto/.htaccess $SRCDIR/resto/favicon.ico $SRCDIR/resto/index.php $SRCDIR/resto/css $SRCDIR/resto/js $SRCDIR/resto/resto $TARGETDIR
echo ' ==> Deploy RESTo to $TARGETDIR directory'
cp -Rf $SRCDIR/src/resto/HyDreController.php $TARGETDIR/resto/controllers/ && echo ' ==> Copy HyDreController to '$TARGETDIR'/resto/controllers/ directory'
cp -Rf $SRCDIR/src/resto/HydreResourceManager.php $TARGETDIR/resto/modules/ && echo ' ==> Copy HyDreResourceManager to '$TARGETDIR'/resto/modules/ directory'
cp -Rf $SRCDIR/src/resto/resto.ini $TARGETDIR/resto/ && echo ' ==> Copy HyDreResourceManager to '$TARGETDIR'/resto/ directory'
cp -Rf $SRCDIR/src/resto/.htaccess $TARGETDIR/ && echo ' ==> Copy HyDreResourceManager to '$TARGETDIR'/ directory'
echo ' ==> Now, do not forget to check $TARGETDIR/resto/resto.ini configuration !'
