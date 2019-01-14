#!/usr/bin/perl -w

use CGI;
use DBI;
use strict;

print "Content-type: text/plain\n\n";

my $dbh = DBI->connect( 'dbi:mysql:dbname=940db;host=localhost',
                        '940u', '940p' ) or die "$DBI::errstr";

my $id_query = $dbh->prepare( 'select id from 961stimuli
                               where presentation_order = 44
                               and completed = "yes"' )
    or die "$DBI::errstr";
$id_query->execute() or die "$DBI::errstr";

my @finished_ids;
while ( my @id = $id_query->fetchrow_array() ) {
    push @finished_ids, @id;
}

my $output_query = $dbh->prepare( 'select left_sound, right_sound, similarity
                                   from 961outputs where id = ?' )
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

# print top row
print "fields = [";
print join ", ", map { qq{'$_'} } @sorted_sounds;
print "]\n\n";

print "similarity = [\n";

# print table
for my $first_sound ( @sorted_sounds ) {
    print "[";
    my @fields;

    for my $second_sound ( @sorted_sounds ) {
	my ($a, $b) = sort ( $first_sound, $second_sound );
	if ( exists $similarity_sum{ $a }{ $b } ) {
	    my $average = $similarity_sum{ $a }{ $b }
	    / $similarity_count{ $a }{ $b };
	    $average = sprintf( '%.1f', $average );
	    push @fields, $average;
	} else {
	    push @fields, "NaN";
	}
    }

    print join ", ", @fields;
    print "],\n";
}
print "]\n\n";

print "count = [\n";
# print table
for my $first_sound ( @sorted_sounds ) {
    print "[";
    my @fields;

    for my $second_sound ( @sorted_sounds ) {
	my ($a, $b) = sort ( $first_sound, $second_sound );
	if ( exists $similarity_count{ $a }{ $b } ) {
	    my $count = $similarity_count{ $a }{ $b };
	    push @fields, sprintf( '%2d', $count );
	} else {
	    push @fields, " 0";
	}
    }

    print join ", ", @fields;
    print "],\n";
}
print "]\n";
