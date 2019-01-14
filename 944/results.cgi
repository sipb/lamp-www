#!/usr/bin/perl -w

use CGI;
use DBI;
use strict;

print "Content-type: text/html\n\n";

my $dbh = DBI->connect( 'dbi:mysql:dbname=940db;host=localhost',
                        '940u', '940p' ) or die "$DBI::errstr";

my $id_query = $dbh->prepare( 'select id from 944stimuli
                               where presentation_order = 44
                               and completed = "yes"' )
    or die "$DBI::errstr";
$id_query->execute() or die "$DBI::errstr";

my @finished_ids;
while ( my @id = $id_query->fetchrow_array() ) {
    push @finished_ids, @id;
}

my $output_query = $dbh->prepare( 'select left_sound, right_sound, similarity
                                   from 944outputs where id = ?' )
    or die "$DBI::errstr";

my %similarity_sum;
my %similarity_count;
foreach ( @finished_ids ) {
    $output_query->execute( $_ ) or die "$DBI::errstr";    

    while( my ($left_sound, $right_sound, $similarity) = $output_query->fetchrow_array() ) {
	my ($a, $b) = sort ( $left_sound, $right_sound );
	$similarity_sum{ $a }{ $b } += $similarity;
	++$similarity_count{ $a }{ $b };
    }
}

my @sorted_sounds = sort keys %similarity_sum;

print "<html><body><table border=1>";

# print top row
print "<tr><td></td>";
print map { "<td bgcolor='lightblue'>$_</td>" } @sorted_sounds;
print "</tr>\n";

# print table
for my $first_sound ( @sorted_sounds ) {
    print "<tr><td bgcolor='lightblue'>$first_sound</td>";
    for my $second_sound ( @sorted_sounds ) {
	if ( exists $similarity_sum{ $first_sound }{ $second_sound } ) {
	    my $average = $similarity_sum{ $first_sound }{ $second_sound }
	    / $similarity_count{ $first_sound }{ $second_sound };
	    $average = sprintf( '%.1f', $average );
	    print "<td>$average</td>";
	} else {
	    print '<td>&nbsp;</td>';
	}
    }
    print "</tr>\n";
}
