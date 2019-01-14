#!/usr/bin/perl -w

use strict;
use DBI;

print "Content-type: text/html\n\n";

my $dbh = DBI->connect( 'dbi:mysql:dbname=sportsurvey;host=localhost',
                        'sports', 'sc4st' ) or die "$DBI::errstr";

print <<END;
<html>
<head>
<title>Sportcast Survey: Results</title>
<LINK REL=StyleSheet HREF="lamp-style.css" TYPE="text/css" MEDIA=screen>
</head>

<body>

<div class="header-strip">
Sportcast Survey: Results
</div>

<p>
END

my $sql_query
    = $dbh->prepare( 'select DATE_FORMAT(date, "%W %r"), ip, been, sports, email, comments from survey order by date' ) or die "$DBI::errstr";

$sql_query->execute() or die "$DBI::errstr";

my $table_output;
my ($num_results, $been, $any_sport, %sportcount) = (0, 0, 0);

my $index = 1;
while ( my @row = $sql_query->fetchrow_array ) {
    $num_results++;
    $been++ if ( $row[ 2 ] eq "yes" );
    for ( split / /, $row[ 3 ] ) {
	$sportcount{ $_ }++;
    }

    if ( $row[ 3 ] =~ /\w/ ) {
	$any_sport++;
    }

    unshift @row, $index++;
    $table_output .= "<tr>";
    $table_output .= join "", map { "<td>$_</td>" } @row;
    $table_output .= "</tr>\n";
}

print "<table>";
print "<tr><td>Number of responses:</td><td>$num_results</td></tr>\n";
print "<tr><td>Been to a game:</td><td>", int(100*$been/$num_results), " %</td></tr>\n";
print "<tr><td>Interested in any sport:</td><td>", int(100*$any_sport/$num_results), " %</td></tr>\n";
print "<tr><td><hr></td></tr>";
for (sort { $sportcount{ $b } <=> $sportcount{ $a } } keys %sportcount) {
    print "<tr><td>Interested in $_:</td><td>", int(100*$sportcount{ $_ }/$num_results), " %</td></tr>\n";
}
print "</table><hr>\n";

print <<END;
Individual responses:<p>
<table border=1>
<tr><td></td><td>Timestamp</td><td>IP address</td><td>Attended</td><td>Sports</td><td>Email</td><td>Comments</td>

END

print $table_output;

print "</table></body></html>";



