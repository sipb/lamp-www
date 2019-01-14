<?
require_once("lamp.php");

class Program {
	var $pid;
	var $aids;
	var $tids;
	var $length;
	var $more_progs_exist = 0;

	function Program($type, $id, $last_id = 0) {
		if ($type == "from_aid") {
			$this->Program_aid($id);	
		} else if ($type == "from_tid") {
			$this->Program_tid($id, $last_id); //Program_tid($id, $last_id);	
		} else if ($type == "from_pid") {
			$this->Program_pid($id);	
		}
	}


	function test() {
		echo "test.";
	}



	function Program_pid($pid) {
		$ids = db_query("select track_id t, album_id a from programs where
					program_id='$pid' 
					order by pos");

		$this->pid = $pid[program_id];
		while ($id = db_fetch_array($ids)) {
			if ($id[t] != "") {
				$this->tids[count($this->tids)] = $id[t];
			}
			if ($id[a] != "") {
				$this->aids[count($this->aids)] = $id[a];
			}
		}
	}		

	function Program_aid($aid) {
		$pid = db_query("select program_id from programs where 
					album_id='$aid' 
					order by program_id");
		$pid = db_fetch_array($pid);
		$this->pid = $pid[program_id];	
	}

	function Program_tid($tid, $last_id) {
		$pid = db_query("select program_id from programs where 
					track_id='$tid'
					order by program_id
					limit $last_id,2");
		if (db_numrows($pid) > 1) {
			$this->more_progs_exist = 1;
		}		
		$pid = db_fetch_array($pid);
		$this->pid = $pid[program_id];
	}

	function songs_list() {
		$i = 0;
		$r = array();
		$q = db_query("select track_id from programs where program_id='$this->pid'");
		
		while ($d = db_fetch_array($q)) {
			$r[$i++] = $d[track_id];
		}

		return $r;
	}

	function program_link() {	
	echo "process-requests?req=playprogram&query=$this->pid";
	}
}

?>
