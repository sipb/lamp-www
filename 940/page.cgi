#!/usr/bin/perl -w

use CGI;
use DBI;
use strict;

my @explanations = ("", "After hearing the word, repeat it aloud three times, paying special attention to the pronunciation.");

my $cgi = new CGI;

print "Content-type: text/html\n\n";

my $id = $cgi->param("id");

my $dbh = DBI->connect( 'dbi:mysql:dbname=940db;host=localhost',
                        '940u', '940p' ) or die "$DBI::errstr";

my $page_returned = $cgi->param("page");
my $choice_returned = $cgi->param("choice");

my $q;

if ((defined $choice_returned) and (defined $page_returned)) {
    $q = $dbh->prepare('UPDATE stimuli SET completed = "true" WHERE
                       (id = ? AND page = ?);');
    $q->execute( $id, $page_returned ) or die;
    
    $q = $dbh->prepare('INSERT INTO outputs (id,page,choice) VALUES (?,?,?);');
    $q->execute( $id, $page_returned, $choice_returned ) or die;
}

$q = $dbh->prepare('SELECT presentation_order, page, ordering FROM
                    stimuli WHERE (id = ? AND completed = "false") ORDER BY
                    presentation_order LIMIT 1;');
$q->execute( $id ) or die;

my ($num, $page, $order) = $q->fetchrow_array;

unless (defined $num) {

    print <<END;
    <HTML>
	<HEAD>
	<TITLE>experiment over</TITLE>
</HEAD>
<BODY BGCOLOR="#bfbfbf">
<TABLE CELLPADDING="7" WIDTH="640" BGCOLOR="#8f8f8f">
  <TR>
    <TD bgcolor="#ffffff"><FONT face="trebuchet ms, verdana, arial" size="2"><B><I>-fin-</I></B><BR>
      <BR>
      The experiment is over. &nbsp;Thank you!<BR>
      </FONT></TD>
  </TR>
</TABLE>
<P>
</BODY></HTML>
END

    exit;
}

$q = $dbh->prepare('SELECT explanation FROM users WHERE (id = ?);');
$q->execute( $id ) or die;

my ($explanation_num) = $q->fetchrow_array;

my $explanation = $explanations[ $explanation_num - 1 ];

my ($left, $right);

my $pagedown = (($page - 1) % 36) + 1;

if ($order eq "left") {
    $left = $pagedown . "A";
    $right = $pagedown . "B";
} elsif ($order eq "right") {
    $left = $pagedown . "B";
    $right = $pagedown . "A";
} else {
    exit;
}

print <<"END";
<HTML>
<HEAD>
  <TITLE>9.40 experiment: carrien + evaenns</TITLE>
</HEAD>
<BODY BGCOLOR="#bfbfbf">
<TABLE CELLPADDING="7" WIDTH="640" BGCOLOR="#8f8f8f">
  <TR>
    <TD bgcolor="#ffffff"><FONT face="trebuchet ms, verdana, arial" size="2">
    Please examine the pictures below. Then, click play below to listen to the
    word.<br>
      <OBJECT>
    <EMBED src="http://web.mit.edu/carrien/Public/9.40/sounds/$page.wav"
    type="video/quicktime" height="16" width="100" autoplay="false">
          </embed></OBJECT> <BR><BR>
    $explanation
      <BR>
      <FORM METHOD=POST ACTION="page.cgi">
      Which picture does this word describe?</FONT><BR>
      <P>
      <TABLE>
        <TR>
          <TD><IMG SRC="images/${left}.jpg"></TD>
          <TD><IMG SRC="images/${right}.jpg"></TD>
        </TR>
        <TR>
          <TD align="center">
            <INPUT type=radio name="choice" value="$left"></TD>
          <TD align="center">
            <INPUT type=radio name="choice" value="$right"></TD>
        </TR>
      </TABLE>
      <P align="center">
      <INPUT TYPE="SUBMIT" VALUE="next &gt;"> <BR>
        <INPUT TYPE="hidden" NAME="id" VALUE="$id">
        <INPUT TYPE="hidden" NAME="page" VALUE="$page">
      </FORM>
    </TD>
  </TR>
</TABLE>
<P>
</BODY></HTML>
END
