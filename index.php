<?
require_once("lamp.php");
db_connect();
output_header();
$u = get_username();

if (isSet($searchset)) {
	list($uin, $sid) = split("\.", $searchset);
	$s = new Searchset("unserialize", $sid);
	$s->results($view, $page);
}

?>

<?
output_footer();
?>
