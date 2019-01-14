<html>
<head>
<title>lamp: library access to music project</title>
<LINK REL=StyleSheet HREF="lamp-style.css" TYPE="text/css" MEDIA=screen>
</head>

<body>


<div class="sidebar">

<?

your_program();
now_playing();
?>
<div class="header-strip">
	library access to music project
</div>

<div class="search-strip">
<form action=process-requests.php style="margin: 0px; border: 0px; padding: 0px;">

<nobr>
<input type=hidden name="req" value="basicsearch">
<input type=text name=query>
<input type=submit value="find music">

</nobr>

<nobr>
 <a href="">advanced search</a>, <a href="">browse</a>, <a href="">request music</a></nobr>
</form> 
</div>

<div class="main-box">




