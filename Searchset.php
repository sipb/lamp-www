<?

require_once("lamp.php");

class Searchset {
	var $id;
	var $uname = "";
	var $searchstring = "";
	var $querybox = "";
	var $tracks = array();
	var $albums = array();
	var $view = 0;
	var $page = 1;
	var $nalbum_pages = 0;
	var $ntrack_pages = 0;
	var $npages;
	var $type;
	var $pagesize = 100;	
	var $albumquery = "";
	var $songquery = "";

	function Searchset($stype, $query) {
		/* We need to make a new Searchset */
		$this->uname = get_username();
		db_query("insert into searchsets (val) values ('')");
		$this->id = db_insert_id();

		/* And set it up properly... */
		if ($stype == "basicsearch") {
			$this->initBasicSearch($query);
		} else if ($stype == "advancedsearch") {
			$this->initAdvancedSearch($query);
		} else if ($stype == "randomsearch") {
		        $this->initRandomSearch($query);
		} else if ($stype == "album") {
			$this->initViewAlbum($query);
		} else if ($stype == "song") {
			$this->initViewSong($query);
		} else if ($stype == "program_search") {
			$this->initProgramSearch($query);
		}
		
	}

	function save() {
		
		db_query("update searchsets set val='".$this->serialize()."' where id=$this->id ");
	}

	static function unserialize($id) {
		$ss = db_query("select val from searchsets where id=$id");
		$ss = db_fetch_array($ss);
		$ss = $ss[val];
		$obj = unserialize(gzuncompress($ss));
		if (get_username() != $obj->uname) {
			echo "No such searchset for $obj->uname.";
			die;
		}
		return $obj;
	}

	function serialize() {
		return addslashes(gzcompress(serialize($this)));
	}

	function initViewAlbum($aid) {
		$this->type = "album";
		$a = new Music("album", $aid);
		$this->albums[0] = $a;
		$this->searchstring = $a->title;

		$this->tracks = $a->get_tracks();
		$this->save();
	}

	function initViewSong($sid) {
//		echo "init: $sid";
		$this->type = "song";
		$s = new Music("song", $sid);
		$this->tracks[0] = $s;
		$this->searchstring = $s->title;

		$this->albums = array();
		$this->save();
	}

	function initProgramSearch($string) {
//		echo "init: $sid";
		$this->type = "program_search";
		$s = new Music("program_search", $string);
		$this->tracks[0] = $s;
		$this->searchstring = $string;

		$this->albums = array();
		$this->save();
	}


	function initRandomSearch($q) {
		$this->type = "both";
		$this->searchstring = stripslashes($q);
		if (strlen($this->searchstring) > 40) {
			$this->searchstring = substr($this->searchstring, 0, 37) . "...";
		}
		$this->querybox = $q;
		
		$this->albumquery = 	"select album_id from albums 
					where album_title like '%$q%' or 
					album_performer like '%$q%' or 
					album_composer like '%$q%' or 
					album_conductor like '%$q%'
					ORDER BY RAND()
					LIMIT 100";
		$aids = db_query($this->albumquery);

		$this->nalbum_pages = ceil(db_numrows($aids)/$this->pagesize);
		while ($aid = db_fetch_array($aids)) {
			$this->albums[count($this->albums)] = $aid[album_id];
		}
		$this->songquery = "select track_id from tracks 
					where track_title like '%$q%' or 
					track_performer like '%$q%' or 
					track_composer like '%$q%' or 
					track_conductor like '%$q%'
					ORDER BY RAND()
					LIMIT 100";
		$tids = db_query($this->songquery);
	
		$this->ntrack_pages = ceil(db_numrows($tids)/100);

		while ($tid = db_fetch_array($tids)) {
			$this->tracks[count($this->tracks)] = $tid[track_id];
		}
	
		$this->save();		
		log_event("SEARCH random for: $q");
	}



	function initBasicSearch($q) {
		$q = trim($q);
		$this->type = "both";
		$this->searchstring = stripslashes($q);
		if (strlen($this->searchstring) > 40) {
			$this->searchstring = substr($this->searchstring, 0, 37) . "...";
		}
		$this->querybox = $q;
		
		$words = preg_split("/[\s,]/", $q);
		if  (sizeof($words) == 0 || $words[0] == "") {
			$which_tokens = "";
		} else {
			$which_tokens = " where ";
			for ($i = 0; $i < sizeof($words); $i++) {
				$words[$i] = " tokens like '%$words[$i]%'  ";
			}
			$which_tokens .=  implode($words, " and ");
		}
		$this->albumquery = 	"select album_id from albums $which_tokens order by album_upc, disc_num
					LIMIT 1000";
		$aids = db_query($this->albumquery);

		$this->nalbum_pages = ceil(db_numrows($aids)/$this->pagesize);
		while ($aid = db_fetch_array($aids)) {
			$this->albums[count($this->albums)] = $aid[album_id];
		}
		$this->songquery = "select track_id from tracks $which_tokens
					LIMIT 5000";
		$tids = db_query($this->songquery);
	
		$this->ntrack_pages = ceil(db_numrows($tids)/100);

		while ($tid = db_fetch_array($tids)) {
			$this->tracks[count($this->tracks)] = $tid[track_id];
		}
	
		$this->save();		
		log_event("SEARCH basic for: $q");
	}



	function initAdvancedSearch($q) {
		$this->type = "both";
		if ($q[browse]) {
			$this->type = "browse";
		}

		$this->searchstring = implode($q," ");
		
		$conductor = $q[conductor];
			if ($conductor != '') {
				$aorder = "album_conductor,";
				$torder = "track_conductor";					
				$this->querybox = "conductor";
			}
		$composer = $q[composer];
			if ($composer != '') {
				$aorder = "album_composer,";
				$torder = "track_composer";
				$this->querybox = "composer";
			}
		$performer = $q[performer];
			if ($performer != '') {
				$aorder = "album_performer,";
				$torder = "track_performer";
				$this->querybox = "performer";
			}
		$title = $q[title];
			if ($title != '') {
				$aorder = "album_title,";
				$torder = "track_title";
				$this->querybox = "title";
			}
		$justbegin = $q[begin];

		$p = "";
		if (! $q[browse] ) {
			$p = "%";
		} 

		$this->albumquery = "select album_id from albums 
					where album_title  like '$p$title%' and
					album_performer like '$p$performer%' and 
					album_composer like '$p$composer%' and
					album_conductor like '$p$conductor%'
					order by $aorder album_upc, disc_num LIMIT 1000";
		$aids = db_query($this->albumquery);

		$this->nalbum_pages = ceil(db_numrows($aids)/$this->pagesize);
		while ($aid = db_fetch_array($aids)) {
			$this->albums[count($this->albums)] = $aid[album_id];
		}

		$this->songquery = "select track_id from tracks 
					where track_title like '$p$title%' and 
					track_performer like '$p$performer%' and 
					track_composer like '$p$composer%' and
					track_conductor like '$p$conductor%'
					LIMIT 5000";
		$tids = db_query($this->songquery);


#		echo $this->songquery;

		$this->ntrack_pages = ceil(db_numrows($tids)/100);

		while ($tid = db_fetch_array($tids)) {
			$this->tracks[count($this->tracks)] = $tid[track_id];
		}
	
		$this->save();		
		if ($this->type == "browse") 
		{
			log_event("BROWSE for: title=$title performer=$performer composer=$composer coductor=$conductor");
		} else  { 
			log_event("SEARCH advanced for: title=$title performer=$performer composer=$composer coductor=$conductor");
		}
	}





	function results($view, $page) {
		if ($view=="") {$view=0;}
		if ($page=="") {$page=1;}
		$this->view = $view;
		$this->page = $page;
		if ($this->type == "both") {
			Display::both_results($this, $view, $page);
		} else if ($this->type == "browse") {
			Display::both_results($this, $view, $page, "true");
		} else if ($this->type == "album") {
			Display::album_result($this);
		} else if ($this->type == "song") {
			Display::song_result($this);
		} else if ($this->type == "program_search") {
			Display::program_search_result($this);
		}

	}




	function link() {
		return "./?searchset=".$this->uname."."."$this->id";
	}

}


?>
