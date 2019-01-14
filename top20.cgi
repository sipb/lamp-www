#!/usr/bin/perl -w

use strict;
use DBI;

my $dbh = DBI->connect( 'dbi:mysql:dbname=lampnew;host=localhost',
                        'lamp', 'skampI3' ) or die "$DBI::errstr";

print <<END;
Content-type: text/html

<html>
<head><title>LAMP's Top 20 Albums</title></head>
<body bgcolor="white">
<h1>LAMP Top 20</h1><p>

The Library Access to Music Project is SIPB's campus-wide jukebox on
MIT cable. (To use LAMP, visit http://lamp.mit.edu.) Here are the top
20 albums from the last month, ranked by how many people played
them:  <p>

<table border=1>
<tr><td><b>Rank</b></td><td><b>Players</b></td><td><b>Title</b></td><td><b>Credit</b></td></tr>
<!-- <tr><td colspan=4><hr></td></tr> -->
END

my $q = $dbh->prepare( "SELECT username, event FROM eventlog WHERE (time > date_sub(NOW(),interval 31 day)) AND event regexp '^PLAY song'" ) or die "$DBI::errstr";
$q->execute or die "$DBI::errstr";
my %played_by;
while ( my ($username, $event) = $q->fetchrow_array() ) {
    my ($song) = $event =~ /^PLAY song (\d+) /;
    next unless ($song =~ /^\d+$/);
    $played_by{ $song }{ $username } = 1;
}

$q = $dbh->prepare( "SELECT track_id, album_upc FROM tracks, albums where track_album = album_id" ) or die "$DBI::errstr";
$q->execute or die "$DBI::errstr";
my %track_to_album;
while ( my ($track, $album) = $q->fetchrow_array() ) {
    $track_to_album{ $track } = $album;
}

$q = $dbh->prepare( "SELECT album_upc, album_title, album_performer FROM albums" ) or die "$DBI::errstr";
$q->execute or die "$DBI::errstr";
my %album_id_to_name;
while ( my ($album_id, $album_title, $album_performer) = $q->fetchrow_array() ) {
    $album_id_to_name{ $album_id } = "<td>$album_title</td><td>$album_performer</td>";
}

my %cd_played_by;
for my $song ( keys %played_by ) {
    for my $username ( keys %{ $played_by{ $song } } ) {
	$cd_played_by{ $track_to_album{ $song } }{ $username } = 1;
    }
}

my $row = 0;
my $rank = 0;
my $last_num = 0;

my @upcs = sort { (scalar keys %{ $cd_played_by{ $b } }) <=> (scalar keys %{ $cd_played_by{ $a } }) } keys %cd_played_by;
my @nums = map { scalar keys %{ $cd_played_by{ $_ } } } @upcs;

while ( 1 ) {
    my $upc = $upcs[ $row ];
    my $num = $nums[ $row ];
    last if ($row > 20 and $last_num != $num);
    $rank = ($row+1) if ( $num != $last_num );

    my $tie = "";
    if ( $nums[ $row ] == $nums[ $row + 1 ] or $nums[ $row ] == $nums[ $row - 1 ] ) {
	$tie = " (tie)";
    }

    print "<tr><td>$rank$tie</td><td>$num</td>";
    print $album_id_to_name{ $upc };

    $row++;
    $last_num = $num;
}

print <<END;
</table>
</body></html>
END
