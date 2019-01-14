#!/usr/bin/perl -w

print "Content-type: application/octet-stream\n\n";

exec 'cat /home/keithw/public_html/mythbusters/MYTHDVD.UDF';

