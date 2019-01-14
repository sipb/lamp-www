#!/usr/bin/perl -w

use CGI;
use DBI;
use strict;

$| = 1;

my $cgi = new CGI;

print "Content-type: text/html\n\n";

my $user_id = $cgi->param("id");

my $dbh = DBI->connect( 'dbi:mysql:dbname=940db;host=localhost',
                        '940u', '940p' ) or die "$DBI::errstr";

my $presentation_returned = $cgi->param( "presentation" );
my $similarity_returned = $cgi->param( "similarity" );

my $q;

unless (defined $user_id ) {
print <<END;
    <HTML>
	<HEAD>
	<TITLE>error</TITLE>
	</HEAD>
	<body>Sorry, there was an error.</body></HTML>
END

exit 0;
}

if ((defined $presentation_returned) and (defined $similarity_returned)) {
    $q = $dbh->prepare('UPDATE 944stimuli SET completed = "yes" WHERE
                       (id = ? AND presentation_order = ?)')
	or die "$DBI::errstr";
    $q->execute( $user_id, $presentation_returned ) or die "$DBI::errstr";

    $q = $dbh->prepare('SELECT left_sound, right_sound FROM
                        944stimuli WHERE (id = ? AND presentation_order = ?)')
	or die "$DBI::errstr";
    $q->execute( $user_id, $presentation_returned ) or die "$DBI::errstr";

    my ($left_sound, $right_sound) = $q->fetchrow_array()
	or die "$DBI::errstr";

    $q = $dbh->prepare('INSERT INTO 944outputs
                        (id, left_sound, right_sound, similarity)
                        VALUES (?,?,?,?);');
    $q->execute( $user_id, $left_sound, $right_sound, $similarity_returned )
	or die "$DBI::errstr";
}

$q = $dbh->prepare('SELECT presentation_order, left_sound, right_sound FROM
                    944stimuli WHERE (id = ? AND completed = "no") ORDER BY
                    presentation_order LIMIT 1') or die "$DBI::errstr";
$q->execute( $user_id ) or die "$DBI::errstr";

my ($presentation_order, $left_sound, $right_sound) = $q->fetchrow_array(); #don't die

unless (defined $presentation_order) {
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

print <<"END";
<HTML>
<HEAD>
  <TITLE>24.944 experiment: carrien</TITLE>
</HEAD>
<BODY BGCOLOR="#bfbfbf">
<TABLE CELLPADDING="7" WIDTH="640" BGCOLOR="#8f8f8f">
  <TR>
    <TD bgcolor="#ffffff"><FONT face="trebuchet ms, verdana, arial" size="2">
    Please listen to the two sounds below.<br>
      <OBJECT>
    <EMBED src="$left_sound.wav"
    type="video/quicktime" height="25" width="100" autoplay="false">
          </embed></OBJECT><br><OBJECT>
    <EMBED src="$right_sound.wav"
    type="video/quicktime" height="25" width="100" autoplay="false">
          </embed></OBJECT>  <BR><BR>
      <BR>
      <FORM METHOD=POST ACTION="page.cgi">

      <b>How similar are these two sounds?</b>  </FONT><BR>
      <P>
      <TABLE>
        <TR>
        <td rowspan="2"><FONT face="trebuchet ms, verdana, arial"
                                                                                
    size="2">less similar &nbsp;</font></td>
          <TD align="center">
            <INPUT type=radio name="similarity" value="1"></TD>
          <TD align="center">
            <INPUT type=radio name="similarity" value="2"></TD>
          <TD align="center">
            <INPUT type=radio name="similarity" value="3"></TD>
          <TD align="center">
            <INPUT type=radio name="similarity" value="4"></TD>
          <TD align="center">
            <INPUT type=radio name="similarity" value="5"></TD>
          <TD align="center">
            <INPUT type=radio name="similarity" value="6"></TD>
          <TD align="center">
            <INPUT type=radio name="similarity" value="7"></TD>
        <td rowspan="2"><FONT face="trebuchet ms, verdana, arial"
                                                                                
    size="2">&nbsp; more similar</font></td>
        </TR>
        <tr>
<td align="center"><FONT face="trebuchet ms, verdana, arial"
                                                                                
size="2">1</font></td>
<td align="center"><FONT face="trebuchet ms, verdana, arial"
                                                                                
size="2">2</font></td>
<td align="center"><FONT face="trebuchet ms, verdana, arial"
                                                                                
size="2">3</font></td>
<td align="center"><FONT face="trebuchet ms, verdana, arial"
                                                                                
size="2">4</font></td>
<td align="center"><FONT face="trebuchet ms, verdana, arial"
                                                                                
size="2">5</font></td>
<td align="center"><FONT face="trebuchet ms, verdana, arial"
                                                                                
size="2">6</font></td>
<td align="center"><FONT face="trebuchet ms, verdana, arial"
                                                                                
size="2">7</font></td>
        </tr>
      </TABLE>


      <P align="center">
      <INPUT TYPE="SUBMIT" VALUE="next &gt;"> <BR>
        <INPUT TYPE="hidden" NAME="id" VALUE="$user_id">
        <INPUT TYPE="hidden" NAME="presentation" VALUE="$presentation_order">
      </FORM>
    </TD>
  </TR>
</TABLE>
<P>
</BODY></HTML>
END

