<?
require_once("Music.php");
require_once("Searchset.php");
require_once("Program.php");

function getmicrotime(){ 
    list($usec, $sec) = explode(" ",microtime()); 
    return ((float)$usec + (float)$sec); 
    } 

function get_username()
{
$u =  $_SERVER[SSL_CLIENT_EMAILADDRESS];
$u = ereg_replace("@MIT.EDU","",$u);
return $u;
}

function db_query($s) {
#	echo "s: $s<br>";
	return mysql_query($s);
}

function db_fetch_array($s) {
	return mysql_fetch_array($s);
}

function db_numrows($s) {
	return mysql_numrows($s);
}
function db_num_rows($s) {
	return db_numrows($s);
}

function db_insert_id() {
	return mysql_insert_id();
}

function output_header($style="")
{
  include("header.php");
}

function output_footer() {
	include("footer.php");
}
function db_connect()
{
        $db = mysql_connect("localhost","lamp","skampI3");
	mysql_select_db("lampnew",$db);

}

function log_event($event, $u='') {
	if ($u=="") {
		$u = get_username();
	}
	$event = addslashes($event);
	db_query("insert into eventlog (username, time, event)
			values ('$u', NOW(), '$event')");
}

function track_from_program_row($r) {
	$r = db_fetch_array(db_query("select track_id from programs where program_row_id=$r"));
	return new Music("song", $r[track_id]);
}
function program_from_program_row($r) {
	$r = db_fetch_array(db_query("select program_id from programs where program_row_id=$r"));
	$ts = db_query("select program_row_id, track_id from programs where program_id='$r[program_id]'");
	

	$i = 0; 

	while ($ts_row = db_fetch_array($ts)) {
		$row_ids[$i] = $ts_row[program_row_id];
		$tracks[$i++] = new Music("song", $ts_row[track_id]);
	}
	return array($row_ids, $tracks);
}
function now_playing()
{
?>
<div class="sidebar-box">
<div class="text-announce">
Now Playing on LAMP:
</div>
<?
$chans = db_query("select * from channels");
while ($chan = db_fetch_array($chans)) {

$n = $chan[program_row_id];
if ($n > 0 && $chan[username] != '') {
	$t = track_from_program_row($n);
	$s = "  
		<a href=\"". $t->link()."\">
			$t->title</a> 
		(<a href=\"process-requests?req=basicsearch&query=$t->artist\">$t->artist</a>) [$chan[username]]";
} else {
	$s = "available";
}
echo "Channel <b>$chan[channel_num]</b>: $s<br>";
echo "<div class=\"white-line\"></div>\n";
	
}
?>
</div>
</div>

<?
/*


        $q =db_query("select now_playing from channels where channel_num=$channel");
        if (db_numrows($q) > 0) {
	$q = db_fetch_array($q);
        return $q[now_playing];
	}
*/
	return "";
}

function process_cd_request($upc, $comments) {
	$u = get_username();
	db_query("insert into requests (username, upc, comments)
			values ('$u', '$upc', '$comments')");
}

function output_cd_request_form($thanks = "") {
if ($thanks) {
	echo "<div class=\"text-announce\">Thank you for your request! We will include your CD in our next purchase.</div>Feel free to make more suggestions below.<div class=\"hr\"><br><br>";
}

?>
<div class="text-announce">Request Music</div><br>
To request a CD for LAMP, please submit its UPC (included in album listings at <a href="http://music.barnesandnoble.com/index.asp?">Barnes & Noble.com</a>).<br><br>

<form action="process-requests">
<input type="hidden" name="req" value="cd_request_submit">
UPC: <input type="text" name="upc"><br>
<br>
Comments (optional):<br>
<textarea name="comments" rows="5" cols="35" wrap="soft"></textarea>
<br><br>
<input type="submit" value="submit request">
</form>
<?
}

function submit_suggestion($upc, $comments) {
	if ($upc != "" || $comments != "")  {
	db_query("insert into suggestions (upc, username, comments) values ('$upc', '".get_username()."', '$comments	')");

	}	
}


function process_random_search($t) {
	$s = new Searchset("randomsearch", $t);
	return $s->link();
}


function process_basic_search($t) {
	$s = new Searchset("basicsearch", $t);
	return $s->link();
}


function process_advanced_search($t) {
	$s = new Searchset("advancedsearch", $t);
	return $s->link();
}

function process_view_album($aid) {
	$s = new Searchset("album", $aid);
	return $s->link();
}

function process_view_song($sid) {
	$s = new Searchset("song", $sid);
	return $s->link();
}

function process_addsong($tid) {
	$u = get_username();
	$already = db_query("select pos from programs_tmp where username='$u' and track_id='$tid'");
	if (db_num_rows($already) > 0) {
		return;
	}

	db_query("insert into programs_tmp (username, track_id) values ('$u', '$tid')");
	return;
}

function process_delsong($tid) {
	$pos = intval($pos);
	$u = get_username();
	db_query("delete from programs_tmp where username='$u' and track_id=$tid");
}

function process_upsong($thisid) {
	$u = get_username();
	$pos = intval($pos);

	$thisrow = db_fetch_array(db_query("select pos from programs_tmp where track_id=$thisid and username='$u'"));
	$pos = $thisrow[pos];

	$prev = db_query("select track_id, pos from programs_tmp where pos < $pos and username='$u' order by pos desc limit 1");
	if (db_num_rows($prev) > 0) {
		$prev = db_fetch_array($prev);
		$prevpos = $prev[pos];
		$previd = $prev[track_id];
		db_query("update programs_tmp set track_id=$previd where pos=$pos");
		db_query("update programs_tmp set track_id=$thisid where pos=$prevpos");
	}
}


function process_downsong($thisid) {
	$u = get_username();
	$pos = intval($pos);

	$thisrow = db_fetch_array(db_query("select pos from programs_tmp where track_id=$thisid and username='$u'"));
	$pos = $thisrow[pos];

	$next = db_query("select track_id, pos from programs_tmp where pos > $pos and username='$u' order by pos asc limit 1");
	if (db_num_rows($next) > 0) {
		$next = db_fetch_array($next);
		$nextpos = $next[pos];
		$nextid = $next[track_id];
		db_query("update programs_tmp set track_id=$nextid where pos=$pos");
		db_query("update programs_tmp set track_id=$thisid where pos=$nextpos");
	}
}

function process_play_program($pid) {
	$u = get_username();
	$c = db_query("select channel_num from channels where username='$u'");

	$already_playing = db_numrows($c);
	if ($already_playing) {
		return "/?action=already_playing";
	}

	$free = db_query("select channel_num from channels where username=''");
	$none_free = !db_numrows($free);
	if ($none_free) {
		return "none-free";
	}

	$free = db_fetch_array($free);
	$free = $free[channel_num];

	$r = get_next_program_row($pid, -1);
	play_progrow($free, $r);
//	echo "Program $pid -- start with" . get_next_program_row($pid, -1);
	log_event("PLAY program $pid", $u);
	return getenv("HTTP_REFERER");
}

function process_trackdone_request($chan,  $pass) {
	if ($pass != "krautlefob") {
		return -1;
	}

	play_next_track($chan);
}

function process_give_up_channel( $user ) {
	$channel = get_channel_by_user( $user );

        if ( $channel == 0 ) {
            return;
        }


        db_query("update channels set username='', program_row_id=1 where
channel_num=$channel");
        exec("/usr/local/bin/lamp-stop $channel");
        exec("/usr/local/bin/lamp-channel-status $channel available");

	log_event("GIVE UP channel $channel", $user);

	return;
}

function process_pause( $user ) {
	$channel = get_channel_by_user( $user );
        if ( $channel == 0 ) {
            return;
        }
        exec("/usr/local/bin/lamp-pause $channel");

	log_event("PAUSE channel $channel", $user);

	return;
}

function process_resume( $user ) {
	$channel = get_channel_by_user( $user );
        if ( $channel == 0 ) {
            return;
        }
        exec("/usr/local/bin/lamp-resume $channel");

	log_event("RESUME channel $channel", $user);

	return;
}

function play_progrow($c, $r) {
        $username = get_username();
        if ($username == "") {
                $username = get_user_by_channel($c);
        }

        $s = song_from_progrow($r);
        $start_info = "0";
        db_query("update channels set username='$username', program_row_id=$r where channel_num=$c");
        $fn = db_fetch_array(db_query("select filename from programs where program_row_id=$r"));
        $filename = $fn[filename];

        if (preg_match('/\/0\.mp3$/', $filename)) {
                $start_info = "starting";
        }

        $clean_name = str_replace( "'", '${###SINGLEQUOTATIONMARK}', $s->get_tvname() );

        $exec_string = '/usr/local/bin/lamp-play ' . '"' . $filename . '"' . " $c $username " . "'" . $clean_name . "'" . " 0 $start_info 2>>/tmp/lamp-dbg.err >>/tmp/lamp-dbg.out&";

	exec($exec_string);

	if ( $username != "background" ) {
		log_event("PLAY song $s->music_id (".$s->get_tvname().")", $username);
	}

//	echo "PLAY THE FILE $fn for row $r";
}

function play_next_track($c) {
	$rid = db_query("select program_row_id from channels where channel_num=$c");
	$rid = db_fetch_array($rid);
	$rid = $rid[program_row_id];

	$lastrow = db_fetch_array(db_query("select program_id, pos from programs where program_row_id=$rid"));
	$nextprogrow = get_next_program_row($lastrow[program_id], $lastrow[pos]);

	if ($nextprogrow < 0) {
		// KICK USER OFF
	        db_query("update channels set username='', program_row_id=1
		where channel_num=$c");
	        exec("/usr/local/bin/lamp-channel-status $c available");
		return;
	}
	play_progrow($c, $nextprogrow);
}

function song_from_progrow($prow) {
	$tid = db_query("select track_id from programs where program_row_id=$prow");
	$tid = db_fetch_array($tid);
	$tid = $tid[track_id];

	return new Music("song", $tid);
}

function get_next_program_row($p, $s) {
	$t = db_query("select program_row_id from programs
			where program_id='$p' and
			pos>$s
			order by pos");

	if (db_numrows($t) == 0) {
		return -1;
	}

	$t = db_fetch_array($t);
	return $t[program_row_id];
}


function output_now_playing() {
$active_channels = db_query("select * from channels");


echo "<Div class=\"playlist_box\" id=\"color0\"><b>Now playing:</b></div>

<div class=\"playlist_box\">";

while ($chan=db_fetch_array($active_channels))
{
        $song = new Music("song", $chan[now_playing]);
        $user = $chan[username];
	$aid = $song->get_album();
	$aid = $aid->music_id;
        $line =  "Channel <span id=\"channelnum\">$chan[channel_num]</span>: <span id=\"extra\">".
        (($song->music_id=="")?(($user != "")?"reserved":"available"):"<a href=\"?action=album_detail&album=".$aid."\">".$song->get_title()."</a>").
 	"</span>".

       (($user != "")?" [$user]":"").
	" </div><div class=\"hr\">";

	echo "<div class=\"now_playing\" id=\"color".(($user!="")?"1":"0")."\"> $line </div>";
	$i++;
}
echo "</div>";

}

function get_location() {
	return urlencode($_SERVER[REQUEST_URI]);
}

function get_free_channels() {
	return db_query("select * from channels where username='' order by channel_num");
}

function get_channel_by_user($username) {
        $q = db_query("select channel_num from channels where username='$username'");
        if (db_numrows($q)==0) {
                return 0;
	}
        else
        {
             $q = db_fetch_array($q);
             return $q[channel_num];
        }

}

function get_user_by_channel($chan) {
        $q = db_query("select username from channels where channel_num=$chan");
	$q = db_fetch_array($q);
	return $q[username];
}

function get_program_by_album($aid) {
	$p = db_query("select program_id from programs 
			where album_id=$aid and 
			whole_album=1 LIMIT 1");
	$p = db_fetch_array($p);
	return $p[program_id];
}

function your_program() {
	$u = get_username();
	$any = db_query("select program_row_id, channel_num
			 from channels
			 where username='$u'");
	if (db_numrows($any) < 1) {
		return;
	}
	$any = db_fetch_array($any);
?>
<div class="sidebar-box">
<div class="your-channel">
<b>Channel <?echo $any[channel_num];?></b>
<br><?echo $u;?></div>
<div class="text-announce">On Your Channel</div>
<?

list($row_ids, $tracks) = program_from_program_row($any[program_row_id]);

//$prow = get_next_program_row($p, -1);
//$i = 0;
//while ($prow > -1) {

//	$t = track_from_program_row($prow);

for ($i = 0; $i < sizeof($tracks); $i++) {
	$t = $tracks[$i];
	if ($row_ids[$i] == $any[program_row_id]){
		echo "<b>";
	}

	echo $i+1 .". $t->title";

	if ($row_ids[$i] == $any[program_row_id]) {
		echo "</b>";
	}
	echo "<br>\n";
}

?>
<ul>
<li><a href="process-requests?req=pause">Pause program</a>
<li><a href="process-requests?req=resume">Resume program</a>
<li><a href="process-requests?req=give_up_channel">Give up channel</a>
</ul>

</div>

<?
}
function user_creating($u="") {
	if ($u=="") {
		$u = get_username();
	}

	$is_c = mysql_numrows(db_query("select creating_program 
					from users where username='$u' and creating_program=1"));
	return $is_c;
}


function create_program_box() {
	if (!user_creating()) {
		return;
	}

$percent = 0;
$u = get_username();

$numtracks = 0;
$progtime = 0;
$tracks = db_query("select track_id, pos from programs_tmp where username='$u' order by pos asc");
while ($track = db_fetch_array($tracks)) {
	$numtracks++;
	$t = new Music("song", $track[track_id]);
	$proglist .= $t->result_create_program();
	list($h, $m, $s) = split(":", $t->time);
	$progtime += ($h*60+$m+$s/60);
}
$percent = round(min(max($progtime / 30 * 100, $numtracks / 6 * 100), 100));


?>
<div class="sidebar-box">
<div class="text-announce">Create a new program: (<?echo $percent;?>%)</div>
<div class="progress-bar-box"><div class="progress-bar" style="width: <?echo min(98,$percent);?>%"></div>
</div>
<ul>
<?
if ($percent==100 && $progtime < 85) {
?>
<li> <a href="process-requests?req=submit_program">Submit program</a> to LAMP
<?
} else if ($percent==100){
?>
<li> Submit program [too long]
<?

} else {
?>
<li> Submit program [too short]
<?
}
?>
<li> <a href="process-requests?req=erase_program">Erase program</a>

</ul>
<?
echo $proglist;

?>

<i>Search for music and use the [<a href="">+P</a>] links to add songs to your program. 
When you have six songs or thirty minutes of music, click "submit program" above.</i>
</div>
<?
}

function process_submit_program() {
$u = get_username();

$program_number = db_query("select program_number from programs_queue order by program_number desc limit 1");
if (db_numrows($program_number) == 0) {
	$program_number = 1;
} else {
	$program_number = db_fetch_array($program_number);
	$program_number = $program_number[program_number] + 1;
}

$tracks = db_query("select track_id from programs_tmp where username='$u' order by pos asc");
while ($track = db_fetch_array($tracks)) {
	db_query("insert into programs_queue (program_number, username, track_id) values ($program_number, '$u', $track[track_id])");
}

process_erase_program();
}

function process_create_program() {
	$u = get_username();
	create_user_row($u);
	db_query("update users set creating_program=1 where username='$u'");
}

function process_erase_program() {
	$u = get_username();
	db_query("delete from programs_tmp where username='$u'");
	db_query("update users set creating_program=0 where username='$u'");
}

function create_user_row($u) {
	$q = db_query("select username from users where username='$u'");
	if (db_numrows($q) > 0) {
		return;
	}

	db_query("insert into users (username) values ('$u')");
}

function output_advanced_search() {
?>
<form action="process-requests">
<div class="text-announce">Advanced Search</div><br>
<table><tr>
<td>
Title:</td><td > <input type="text" name="title"></td></tr>
<tr><td >Performer:</td><td class="search_cell"> <input type="text" name="performer"></td></tr>
<tr><td >Composer:</td><td class="search_cell"> <input type="text" name="composer"></td></tr>
<tr><td >Conductor:</td><td class="search_cell"> <input type="text" name="conductor"></td></tr>
</table>
<input type="hidden" name="req" value="advancedsearch">
<input type="submit" value="find music">
</form>
<?
}

function output_create_program() {
?>
<div class="text-announce">Create a new program</div><br>
Search for music and use the [<a href="">+P</a>] links to add songs to your program. Change the order of tracks and submit your program using the box on the right-hand side of the screen.

<?
}

function output_program_submitted() {
?>
<div class="text-announce">Program submitted</div><br>
Your program has been submitted and will be available online as soon as possible. Recording ususally takes about ninety minutes.
<?
}

function output_already_playing() {
?>
<div class="text-announce">Already Playing</div><br>
Sorry, you are playing a program already.
<?
}



?>
