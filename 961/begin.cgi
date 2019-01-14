#!/usr/bin/perl -w

use CGI;
use DBI;
use strict;
use List::Util 'shuffle';

my $cgi = new CGI;

print "Content-type: text/html\n\n";

my $age = $cgi->param("age");
my $lang = $cgi->param("language");
my $gender = $cgi->param("gender");

unless ((defined $age) and $age =~ /^\d+$/) {
    print "<HTML><HEAD><TITLE>Invalid</TITLE></HEAD>\n";
    print "<BODY>Sorry, your age ($age) does not appear to be a number.\n";
    print qq{<P>Please <a href="/961/">try again.</a></body></html>};
    exit;
}

unless ((defined $lang) and $lang =~ /[a-zA-Z]/) {
    print "<HTML><HEAD><TITLE>Invalid</TITLE></HEAD>\n";
    print "<BODY>Sorry, your native language ($lang) does not look right.\n";
    print qq{<P>Please <a href="/961/">try again.</a></body></html>};
    exit;
}
unless ((defined $gender) and ($gender eq "male" or $gender eq "female")) {
    print "<HTML><HEAD><TITLE>Invalid</TITLE></HEAD>\n";
    print "<BODY>Please enter your gender.\n";
    print qq{<P>Please <a href="/961/">try again.</a></body></html>};
    exit;
}

my $dbh = DBI->connect( 'dbi:mysql:dbname=940db;host=localhost',
                        '940u', '940p' ) or die "$DBI::errstr";

my $hostname = $ENV{'REMOTE_ADDR'};

my $q = $dbh->prepare('INSERT INTO 961users (age, lang, gender,
                       hostname, joined )
                       VALUES (?,?,?,?,NOW());') or die "$DBI::errstr";
$q->execute( $age, $lang, $gender, $hostname ) or die "$DBI::errstr";

my $id_query = $dbh->prepare( "select last_insert_id()" )
    or die "$DBI::errstr";
$id_query->execute() or die "$DBI::errstr";
my @user_id = $id_query->fetchrow_array();
my $user_id = $user_id[ 0 ];

my $set = 1;

my $sounds_query = $dbh->prepare( 'SELECT name FROM 961sounds
                                   WHERE (961sounds.set = ?)' ) or die "$DBI::errstr";
$sounds_query->execute( $set ) or die "$DBI::errstr";
my @sound_names;
while ( my @name = $sounds_query->fetchrow_array() ) {
    push @sound_names, @name;
}

@sound_names = shuffle @sound_names;

my %pairings;

for my $first_sound ( @sound_names ) {
    for my $second_sound ( @sound_names ) {
	my @two_sounds = sort ($first_sound, $second_sound);
	$pairings{ "@two_sounds" } = 1;
    }
}

my @shuffled_pairings;
for my $x (keys %pairings) {
    my ($a, $b) = split ' ', $x;
    if ( rand() < 0.5 ) {
	push @shuffled_pairings, [$a, $b];
    } else {
	push @shuffled_pairings, [$b, $a];
    }
}

@shuffled_pairings = shuffle @shuffled_pairings;

$q = $dbh->prepare('INSERT INTO 961stimuli (id, presentation_order,
                    left_sound, right_sound) VALUES (?,?,?,?);')
    or die "$DBI::errstr";

for ( my $presentation = 0; $presentation < scalar @shuffled_pairings;
      $presentation++ ) {
    $q->execute( $user_id, $presentation,
		 $shuffled_pairings[ $presentation ][ 0 ],
		 $shuffled_pairings[ $presentation ][ 1 ] )
	or die "$DBI::errstr";
}

my $sound_explanation = join "",
    map { qq{<OBJECT><EMBED src="${_}.wav"
		type="video/quicktime" height="25" width="100"
		autoplay="false"></embed></OBJECT><br>} } @sound_names;

print <<"END";
<HTML>
<HEAD>
    <TITLE>24.961 experiment: carrien &gt;&gt; instructions</TITLE>
</HEAD>
<BODY BGCOLOR="#bfbfbf">
<TABLE CELLPADDING="7" WIDTH="640" BGCOLOR="#8f8f8f">
  <TR>
    <TD bgcolor="#ffffff"><FONT face="trebuchet ms, verdana, arial"
size="2"><B><I>Instructions</I></B><BR>
      <BR>
    On each page of the experiment, you will be presented with a pair of
sounds. <I>Please turn up your speakers so you can hear the sounds
clearly!</I><br>
<br>
To give you an idea of the range of sounds, here are the nine sounds you
will hear in this experiment:<br>

$sound_explanation

<br>On each page of the experiment, play both sounds and then give a
similarity rating from 1 to 7.  A rating of 1 means the two sounds are
    very dissimilar; a rating of 7 means the two sounds are identical.  You
should feel comfortable using the full range of the scale.<BR>
      <BR>
      There are forty-five pairs of sounds. The whole experiment should
last about 5-10 minutes. If you feel uncomfortable at any time, you may
discontinue the experiment.<BR><BR>


      </FONT>
      <FORM METHOD=POST ACTION="page.cgi">
    <INPUT TYPE=hidden NAME=id VALUE="$user_id">
      <INPUT TYPE="SUBMIT" VALUE="next &gt;"></FORM>
    </TD>
  </TR>
</TABLE>
<P>
</BODY></HTML>
END
