#!/usr/bin/perl -w

use strict;
use CGI;
use DBI;

print "Content-type: text/html\n\n";

my $dbh = DBI->connect( 'dbi:mysql:dbname=sportsurvey;host=localhost',
                        'sports', 'sc4st' ) or die "$DBI::errstr";

my $survey = new CGI;

print <<END;
<html>
<head>
<title>Sportcast Survey: Thanks for submitting!</title>
<LINK REL=StyleSheet HREF="lamp-style.css" TYPE="text/css" MEDIA=screen>
</head>

<body>

<div class="header-strip">
Sportcast: Thanks for filling out the survey.
</div>

<p>

We appreciate it! Please encourage your friends to fill out the survey themselves, at:<P>

<ul><a href="http://lamp.mit.edu/sports">http://lamp.mit.edu/sports</a>

</body>
</html>
END

my $sql_submit
    = $dbh->prepare( 'insert into survey (ip, been, sports, email, comments) values (?, ?, ?, ?, ?)' );

my ($been, $sports, $email, $comments) = map { join " ", $survey->param( $_ ) }
    qw[been sports email comments];

$sql_submit->execute( $ENV{'REMOTE_ADDR'}, $been, $sports, $email, $comments );


