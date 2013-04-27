#!/bin/sh
#
# Quickly update dates on copyright headers
#
# USAGE:
#
#   $ ./update-headers ../../src/
#   # => updates all the copyright dates on all files inside src folder

for f in $(find $1 -iname "*.php")
do
    sed -i '' -e "s#\(Copyright (c) 2008-\)[0-9]\{4\}#\1$(date +%Y)#g" $f
done
