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

cp -Rf $SRCDIR/src/resto/HyDreController.php $TARGETDIR/resto/controllers/ && echo ' ==> Copy HyDreController to '$TARGETDIR'/resto/controllers/ directory'
cp -Rf $SRCDIR/src/resto/HydreResourceManager.php $TARGETDIR/resto/modules/ && echo ' ==> Copy HyDreResourceManager to '$TARGETDIR'/resto/modules/ directory'

