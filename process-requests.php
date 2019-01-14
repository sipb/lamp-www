<?
require_once("lamp.php");
db_connect();

$location = getenv("HTTP_REFERER");

$path = $_SERVER[PHP_SELF];
preg_match("|(.*)/|", $path, $match);
$path = $match[1];

$req = $_REQUEST["req"];
$query = $_REQUEST["query"];

switch($req)
{
	case "random":
		$location = process_random_search($query);
	break;

	case "find music":
	case "basicsearch":
		$location = process_basic_search($query);
	break;

	case "advancedsearch":
		$query = array("title"=>$_REQUEST["title"], "composer"=>$_REQUEST["composer"],"performer"=>$_REQUEST["performer"],"conductor"=>$_REQUEST["conductor"]);	
		$location = process_advanced_search($query);
		
	break;

	case "browse":
		$query = array($browseby=>$_REQUEST["letter"], "browse"=>'true');	
		$location = process_advanced_search($query);
		
	break;
	
	case "viewalbum":
		$location = process_view_album($query);
	break;

	case "viewsong":
		$location = process_view_song($query);
	break;

	case "programsearch":
		$location = process_program_search($query);
	break;

	case "playprogram":
		$location = process_play_program($query,$_REQUEST["step"]);
	break;

	case "trackdone":
		process_trackdone_request($_REQUEST["chan"], $_REQUEST["pass"]);
		break;

	case "joejoella":
		$_SERVER[SSL_CLIENT_EMAILADDRESS] = "background@MIT.EDU";
		play_progrow($_REQUEST["chan"], $_REQUEST["progrow"]);
		break;		

        case "give_up_channel":
		process_give_up_channel(get_username());
                break;

	case "pause":
		process_pause(get_username());
		break;

	case "resume":
		process_resume(get_username());
		break;

	case "addsong":
		process_addsong($query);
		break;

	case "delsong":
		process_delsong($query);
		break;

	case "upsong":
		process_upsong($query);
		break;
	
	case "downsong":
		process_downsong($query);
		break;

	case "submit_program":		
		process_submit_program();
		$location = "$path/index?action=program_submitted";
		break;

	case "create_program":
		process_create_program();
		$location = "index?action=create_program";
		break;

	case "erase_program":
		process_erase_program();
		break;

	case "cd_request_submit":
		process_cd_request($_REQUEST["upc"], $_REQUEST["comments"]);
		$location="$path/index??action=cd_request&thanks=1";
		break;

	case "buy_cd":
		process_buy_cd_request($_REQUEST["upc"]);
		$location="http://service.bfast.com/bfast/click?bfmid=2181&sourceid=40776771&bfpid=$upc&bfmtype=music";
	break;

	case "get_rec": 
		process_get_rec_request($query);
	break;

}

	if ($location == "") {
		$location = "/";
	}

#	echo "loc: $location";

	Header("Location:$location");

?>
