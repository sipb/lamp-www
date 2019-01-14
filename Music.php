<?

require_once("lamp.php");
class Music {
	
	var $music_type = "";
	var $music_id = "";
	var $artist = "";
	var $title = "";
	var $on_album = "";
	var $tracks = array();
	var $filename = "";
	var $time = "";
	var $numtracks = 0;
	var $composer = "";
	var $conductor = "";
	var $copyright = "";
	var $upc = "";
	var $work = "";
	var $genre = "";

	function Music($type, $id) {
		$this->music_type = $type;
		$this->music_id = $id;
		$this->lookup_info();
	}

	function get_tvname() {
		$myalbum = $this->get_album();
		$extra = $myalbum->title . " / " . $this->artist;

		return "$this->title ($extra)";
	}

	function get_filename() {

	}

	function get_title($withartist=1) {
		return $this->title;
	}
	function get_artist() {
		return $this->artist;
	}

	function get_album() {
		$t = $this->on_album;
		if ($t != "") {
			return $this->on_album;
		}	
	}

	function get_time() {
		return $this->time;
	}

	function link() {
		if ($this->is_album()) {
			return $this->track_list_link();
		} else {
			return $this->on_album->track_list_link();
		}
	}

	function track_list_link() {
		return "process-requests?req=viewalbum&query=$this->music_id";
	}

	function is_album() {
		if ($this->music_type == "album" ) {
			return true;
		}
		return false;
	}
	
	function get_enqueue_link() {
		return "process-requests.php?req=enqueue&music_type=$this->music_type&music_id=$this->music_id&location=".get_location();
	}
	
	function lookup_info() {
		if ($this->is_album() ) {

			$q = "select album_title as title, 
				album_performer as artist, 
				numtracks as numtracks, 
				album_composer as composer, 
				album_conductor as conductor, 
				album_upc 
				from albums 
				where albums.album_id='".$this->music_id."'";
		} else {
			$q =  "select track_title as title, 
				track_composer as composer, 
				track_conductor as conductor, 
				track_album as aid, 
				track_performer as artist, 
				track_length as duration
				from tracks 
				where track_id = '".$this->music_id."'";
		}
		$q = db_query($q);
		if ($r = db_fetch_array($q)) {
			$this->artist = $r[artist];
			$this->title  = $r[title];
			$this->work = $r[work];
			$this->time = $r[duration];	
			$this->numtracks = $r[numtracks];
			$this->copyright = $r[copyright] . ", " . $r[copyright_year];
			$this->composer = $r[composer];
			$this->conductor = $r[conductor];
			$this->upc = $r[album_upc];
			$this->genre = $r[genre];
			if ($r[aid] != "") {
				$this->on_album = new Music("album", $r[aid]);
			
		}
		}

	}

	function get_tracks() {
		if (count($this->tracks) > 0) {
			return $this->tracks;
		}
			if ($this->is_album()) {
			$q = db_query("select track_id from tracks 
				where track_album=$this->music_id");
			while ($r = db_fetch_array($q)) {
//				echo "one song $r[track_id]";
				$this->tracks[count($this->tracks)] = new Music("song", $r[track_id]);
			}
			return $this->tracks;
		}

		return 0;
	}

	function result_detail() {

		$s = "<div class=\"result\">

                <div class=\"result-title\">";
			$s .= "$this->title</a>";

		$s .="
		</div>
                <div> $this->artist</div>
                <div> $this->numtracks tracks</div>
                </div>";
 
		return $s;

	}

function result_text($flags="") {
	if (user_creating()) {
		$d = 1;
		$d_link = $this->add_link();
		
	}

	if (ereg("album", $flags)) {
		$a = 1;
		$a_link = $this->link();
	}
	
	if (ereg("program", $flags)) {
		$p = 1;
		$p_link = $this->program_link();
	} else {
		$d = 0; # not program view AND add-to-program link.

	}

	if ($this->is_album()) {
		$a_name = "list tracks";
		$p_name = "play entire CD";
		$d = 0;
	} else {
		$a_name = "view album";
		$p_name = "play program";
		$d_name = "+P";
	}

	$ret = "<div class=\"result\">
		<div class=\"result-float\">" .
		($a?"<a href=\"$a_link\">$a_name</a> | ":"") .
		($p?"<a href=\"$p_link\">$p_name</a> | ":"") .  
		($d?"<a href=\"$d_link\">$d_name</a> | ":"") . 
		" </div>
                	
		<div class=\"result-title\">
		$this->title
		</div>
			
		<div> 
			<a href=\"process-requests?req=basicsearch&query=".$this->artist."\">
			$this->artist</a>
		</div>
                
		<div> $this->time</div>
                </div>";
		
	return $ret;
}

	function result_create_program() {
	$ret = "<div class=\"result\">
		<div class=\"result-float\">" .
		"<a href=\"process-requests?req=upsong&query=$this->music_id\">up</a> | " .
		"<a href=\"process-requests?req=downsong&query=$this->music_id\">down</a> | ".  
		"<a href=\"process-requests?req=delsong&query=$this->music_id\">X</a>". 
		" </div>
                	
		<div class=\"result-title\">
		$this->title;
		</div>
			
		<div> 
		$this->artist
		</div>
                
		<div> $this->time</div>
                </div>";
	return $ret;

}

	function add_link() {

		return "process-requests?req=addsong&query=$this->music_id";
	}

	function program_link() {

		if ($this->is_album()) {
			$x = get_program_by_album($this->music_id);
			if ($x != "") {
				return "process-requests?req=playprogram&query=$x";
			} else {
				return "";
			}

		} else {

			return "process-requests?req=viewsong&query=$this->music_id";
		}
		
	}
}
?>
