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
    
    for my $id (@finished_id) {
	my $language = $lang{ $id };
	$language =~ s/\(.*?\)//g;
	$language =~ s/^\s*//g;
	$language =~ s/\s*$//g;
	if ($language =~ /^(anglais|english)$/i) {
	    if ( $explanation{ $id } == 1 ) {
		$bucket{ $id } = 'english_silent';
	    } else {
		$bucket{ $id } = 'english_pronounce';
	    }
	} elsif ($language =~ /^(fran.ais|french)$/i) {
	    $bucket{ $id } = 'french_only';
	} elsif ($language =~ /^(mandarin|chinese)$/i) {
	    $bucket{ $id } = 'mandarin_only';
	} else {
	    $bucket{ $id } = 'multi_lang';
	}
    }
    
    $q = $dbh->prepare(q(SELECT page, vowel FROM vowel));
    $q->execute or die;
    
    my %vowel;
    while( my( $page, $vowel ) = $q->fetchrow_array ) {
	$vowel{ $page } = uc $vowel;
    }
    
    $q = $dbh->prepare(q(SELECT id, page, choice FROM outputs));
    $q->execute or die;
    
    my (%choice, %bucket_choice, %gender_choice, %total_choice);
    
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
    
    for my $id (@finished_id) {
	for my $page (keys %{ $choice{ $id } }) {
	    $bucket_choice{ $page }{ $bucket{ $id } }{ $choice{ $id }{ $page } }++;
	    $gender_choice{ $page }{ $gender{ $id } }{ $choice{ $id }{ $page } }++;
	    $total_choice{ $page }{ 'all' }{ $choice{ $id }{ $page } }++;
	}
    }
    
    my (%is_bucket, %is_gender);
    for (values %bucket) {
	$is_bucket{ $_ } = 1;
    }
    for (values %gender) {
	$is_gender{ $_ } = 1;
    }
    
    my @buckets = sort keys %is_bucket;
    my @genders = sort keys %is_gender;
    
    my %total;
    
    print "<TR><TD>page-$page_max</TD>", ( map { "<TD>$_</TD>" }
					   (map { ("$_-A", "$_-B") } (@buckets, @genders, 'all')), 'vowel' ), "</TR>\n";
    
    for my $page (sort {$a <=> $b} keys %bucket_choice) { # pages in order
	print "<TR><TD>$page</TD>";
	my @data = ((map { a $_ } map { ( $bucket_choice{ $page }{ $_ }{ A },
					  $bucket_choice{ $page }{ $_ }{ B } ) } (@buckets)),
		    (map { a $_ } map { ( $gender_choice{ $page }{ $_ }{ A },
					  $gender_choice{ $page }{ $_ }{ B } ) } (@genders)),
		    (map { a $_ } map { ( $total_choice{ $page }{ $_ }{ A },
					  $total_choice{ $page }{ $_ }{ B } ) } ('all')),
		    $vowel{ $page });
	
	for (my $i = 0; $i < $#data; $i++) {
	    $total{ total }[ $i ] += $data[ $i ];
	    $total{ $vowel{ $page } }[ $i ] += $data[ $i ];
	}
	print map { "<TD>$_</TD>" } @data;
	print "</TR>\n";
    }
    
    for (sort keys %total) {
	print "<TR><TD>$_</TD>", (map { "<TD>$_</TD>" } @{ $total{ $_ } }), "</TR>\n";
    }
}
