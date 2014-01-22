#!/bin/bash
#
# HyDre
#
# mapshup build script
#
# Author : Jerome.Gasperi@cnes.fr
# Date   : 2013.12.10
# Version: 1.0
#

# Set default values - can be superseeded by command line
SRC=`pwd`
PROJECT=hydre
COMPILE=NO
CLONE=NO

# TARGET directory is mandatory from command line
usage="## HyDre mapshup build script\n\n  Usage $0 -t <HYDRE_TARGET> [-s <HYDRE_HOME> -a -c]\n\n  -a : performs steps 1. mapshup git clone + resto git clone, 2. mapshup compile and 3. build\n  -c : perform steps 1. mapshup compile and 2.build\n  (By default, only build step is performed)\n"
while getopts "act:s:h" options; do
    case $options in
        a ) CLONE=YES
            COMPILE=YES;;
        c ) COMPILE=YES;;
        t ) TARGET=`echo $OPTARG`;;
        s ) SRC=`echo $OPTARG`;;
        h ) echo -e $usage;;
        \? ) echo -e $usage
            exit 1;;
        * ) echo -e $usage
            exit 1;;
    esac
done
if [ "$TARGET" = "" ]
then
    echo -e $usage
    exit 1
fi

# git clone
if [ "$CLONE" = "YES" ]
then
    echo -e " -> Clone mapshup git repository to $SRC/mapshup directory"   
    git clone https://github.com/jjrom/mapshup.git $SRC/mapshup
    rm -Rf $SRC/mapshup/.git
    rm -Rf $SRC/mapshup/.gitignore
fi

if [ "$COMPILE" = "YES" ]
then
    echo -e " -> Compile mapshup to $TARGET directory"
    mkdir -p $TARGET 
    /bin/rm -Rf $TARGET/mapshup
    $SRC/mapshup/utils/packer/pack.sh $SRC/mapshup $TARGET/mapshup default 0 $SRC/src/mapshup/buildfile.txt 0
    rm -Rf $TARGET/mapshup/s/README_INSTALL.txt
    rm -Rf $TARGET/mapshup/s/_installdb
fi

echo -e " -> Copy $PROJECT files to $TARGET directory"
cp $SRC/src/mapshup/config.php $TARGET/mapshup/s/config.php
if [ ! -d $TARGET/mapshup/$PROJECT ]
then
    mkdir $TARGET/mapshup/$PROJECT
fi
cp $SRC/src/mapshup/index.html $TARGET/mapshup
cp $SRC/src/mapshup/indext.html $TARGET/mapshup
cp $SRC/src/mapshup/style.css $TARGET/mapshup/$PROJECT
cp $SRC/src/mapshup/config.js $TARGET/mapshup/$PROJECT
echo -e " -> done!\n"
