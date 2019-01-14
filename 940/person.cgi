#!/usr/bin/perl -w

use DBI;
use strict;

print "Content-type: text/html\n\n";

my $dbh = DBI->connect( 'dbi:mysql:dbname=940db;host=localhost',
                        '940u', '940p' ) or die "$DBI::errstr";

print "<HTML><BODY><TABLE>\n";
print_db( 72 );
print "</TABLE></BODY></HTML>\n";

sub a {
    my $x = shift @_;
    if (defined $x) {
	return $x;
    } else {
	return 0;
    }
}

sub print_db {
    my $page_max = shift @_;

    my $q;

    $q = $dbh->prepare(qq(SELECT users.id, lang, explanation, gender FROM users, stimuli
			  WHERE presentation_order=$page_max
			  AND completed='true' AND users.id = stimuli.id));
    $q->execute or die;

    my @finished_id;
    my (%lang, %explanation, %gender);
    my %bucket;
    
    while( my($id, $lang, $explanation, $gender) = $q->fetchrow_array ) {
	push @finished_id, $id;
	$lang{ $id } = $lang;
	$explanation{ $id } = $explanation;
	$gender{ $id } = $gender;
    }
    
    $q = $dbh->prepare(q(SELECT page, vowel FROM vowel));
    $q->execute or die;
    my %vowel;
    while( my( $page, $vowel ) = $q->fetchrow_array ) {
	$vowel{ $page } = $vowel;
    }

    $q = $dbh->prepare(q(SELECT page, attribute FROM attributes));
    $q->execute or die;
    my %attribute;
    while( my( $page, $attribute ) = $q->fetchrow_array ) {
	$attribute{ $page } = $attribute;
    }
    
    $q = $dbh->prepare(q(SELECT id, page, choice FROM outputs));
    $q->execute or die;
    my %choice;
    while( my($id, $page, $choice) = $q->fetchrow_array ) {
	my $pagedown = (($page - 1) % 36) + 1;
	if ($choice eq ($pagedown . "A")) {
	    $choice{ $id }{ $page } = 'A';
	} elsif ($choice eq ($pagedown . "B")) {
	    $choice{ $id }{ $page } = 'B';
	} else {
	    die "Bad user $id $page $choice";
	}
    }
    
    my %av_choice;
    my %av_combinations;
    for my $id (@finished_id) {
	for my $page (keys %{ $choice{ $id } }) {
	    if ( $choice{ $id }{ $page } eq 'A' ) {
		$av_choice{ $id }{ $attribute{ $page } . "-" . $vowel{ $page } }++;
		$av_combinations{ $attribute{ $page } . "-" . $vowel{ $page } } = 1;
	    }
	}
    }

    my @av_combinations;
    for (sort keys %av_combinations) {
	push @av_combinations, $_;
    }

    print "<TR>";
    print map { "<TD><B>$_</B></TD>" } (qw[id language gender pronounced], @av_combinations);
    print "</TR>\n";

    for my $id (@finished_id) {
	print "<TR>";
	print map { "<TD>$_</TD>" } ($id, $lang{ $id },
				     $gender{ $id },
				     $explanation{ $id } == 2 ? "yes" : "no",
				     map { a $av_choice{ $id }{ $_ } } @av_combinations);
	print "</TR>\n";
    }

}
