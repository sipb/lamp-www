#!/usr/bin/perl -w

use DBI;
use strict;

print "Content-type: text/html\n\n";

my $q;

my $dbh = DBI->connect( 'dbi:mysql:dbname=940db;host=localhost',
                        '940u', '940p' ) or die "$DBI::errstr";

print "<HTML><BODY>\n";

my @users_fields = qw(id age lang gender hostname joined explanation);
my @stimuli_fields = qw(id presentation_order page ordering completed);
my @outputs_fields = qw(id page choice);

sub print_table {
    my ($name, @fields) = @_;

    my $list = join ", ", @fields;

    print "<H1>$name</H1>\n";    
    print "<TABLE BORDER=1><TR>", (map { "<TD>$_</TD>" } @fields), "</TR>\n";
    $q = $dbh->prepare(qq(SELECT $list FROM $name));
    $q->execute or die;
    while ( my @x = $q->fetchrow_array ) {
	print "<TR>", (map { "<TD>$_</TD>" } @x), "</TR>\n";
    }
    print "</TABLE><HR>\n";
}

print_table( 'users', @users_fields );
print_table( 'stimuli', @stimuli_fields );
print_table( 'outputs', @outputs_fields );

print "</BODY></HTML>\n";
