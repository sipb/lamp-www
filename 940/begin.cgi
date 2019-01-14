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

unless ($age =~ /^\d+$/) {
    print "<HTML><HEAD><TITLE>Invalid</TITLE></HEAD>\n";
    print "<BODY>Sorry, your age ($age) does not appear to be a number.\n";
    print qq{<P>Please <a href="/940/">try again.</a></body></html>};
    exit;
}

unless ($lang =~ /[a-zA-Z]/) {
    print "<HTML><HEAD><TITLE>Invalid</TITLE></HEAD>\n";
    print "<BODY>Sorry, your native language ($lang) does not look right.\n";
    print qq{<P>Please <a href="/940/">try again.</a></body></html>};
    exit;
}
unless ($gender eq "male" or $gender eq "female") {
    print "<HTML><HEAD><TITLE>Invalid</TITLE></HEAD>\n";
    print "<BODY>Please enter your gender.\n";
    print qq{<P>Please <a href="/940/">try again.</a></body></html>};
    exit;
}


my $dbh = DBI->connect( 'dbi:mysql:dbname=940db;host=localhost',
                        '940u', '940p' ) or die "$DBI::errstr";

my $hostname = $ENV{'REMOTE_ADDR'};

my $explanation = (rand() < 0.5) ? 1 : 2;

if (!($lang =~ /english/i)) {
    $explanation = 1;
}

my $id = int rand 1000000000; # can't get much bigger

my $q = $dbh->prepare('INSERT INTO users (id, age, lang, gender,
                       hostname, explanation)
                       VALUES (?,?,?,?,?,?);');
$q->execute( $id, $age, $lang, $gender, $hostname, $explanation ) or die;

my @pages = (shuffle(0 .. 35), map { $_ + 36 } shuffle(0 .. 35));
my @orders = map { (rand() < .5) ? 'left' : 'right' } (1 .. 36);

$q = $dbh->prepare('INSERT INTO stimuli (id, presentation_order,
                    page, ordering, completed) VALUES (?,?,?,?,?);');

for (my $i = 0; $i < scalar @pages; $i++) {
    my $ordering = $orders[ $pages[ $i ] % 36 ];
    $q->execute( $id, $i + 1, $pages[ $i ] + 1, $ordering, 'false' ) or die;
}

print <<"END";
<HTML>
<HEAD>
    <TITLE>9.40 experiment: carrien + evaenns &gt;&gt; instructions</TITLE>
</HEAD>
<BODY BGCOLOR="#bfbfbf">
<TABLE CELLPADDING="7" WIDTH="640" BGCOLOR="#8f8f8f">
  <TR>
    <TD bgcolor="#ffffff"><FONT face="trebuchet ms, verdana, arial" size="2"><B><I>Instructions</I></B><BR>
      <BR>
      You will be presented with a pair of images and a sound that accompanies
      each pair. <I>Please turn up your speakers so you can hear the sounds
      clearly!</I> Each sound is a word in an alien language. Play the sound and
      follow the instructions on the page to choose which of the two images you
      think the word describes.<BR>
      <BR>
      You will see two blocks of thirty-six pairs of images. The whole experiment
      should last about 10-15 minutes. If you feel uncomfortable at any time, you may discontinue the experiment.<BR><BR>
      </FONT> 
      <FORM METHOD=POST ACTION="page.cgi">
    <INPUT TYPE=hidden NAME=id VALUE="$id">
      <INPUT TYPE="SUBMIT" VALUE="next &gt;"></FORM>
    </TD>
  </TR>
</TABLE>
<P>
</BODY></HTML>
END