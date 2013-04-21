<?php
//IMathAS:  View login record
//(c) 2011 David Lippman

require("../validate.php");
$cid = intval($_GET['cid']);
if (!isset($teacherid)) {
	$uid = $userid;
} else {
	$uid = intval($_GET['uid']);
}

$curBreadcrumb = "$breadcrumbbase <a href=\"course.php?cid=$cid\"> $coursename</a>\n";
if (isset($teacherid)) {
	if (isset($_GET['from']) && $_GET['from']=='gb') {
		$curBreadcrumb .= " &gt; <a href=\"gradebook.php?cid=$cid&stu=0\">Gradebook</a> ";
		$curBreadcrumb .= " &gt; <a href=\"gradebook.php?cid=$cid&stu=$uid\">Student Detail</a> ";
	} else {
		$curBreadcrumb .= " &gt; <a href=\"listusers.php?cid=$cid\">Roster</a> ";
	}
}
$curBreadcrumb .= "&gt; View Login Log\n";	
$pagetitle = "View Login Log";
require("../header.php");
echo "<div class=\"breadcrumb\">$curBreadcrumb</div>";
echo '<div id="headerloginlog" class="pagetitle"><h2>'.$pagetitle. '</h2></div>';

$query = "SELECT LastName,FirstName FROM imas_users WHERE id='$uid'";
$result = mysqli_query($GLOBALS['link'],$query) or die("Query failed : " . mysqli_error($GLOBALS['link']));
$row = mysqli_fetch_row($result);
echo '<h3>Login Log for '.$row[1].', '.$row[0].'</h3>';
echo '<ul class="nomark">';

$query = "SELECT logintime,lastaction FROM imas_login_log WHERE userid='$uid' AND courseid='$cid' ORDER BY logintime DESC";
$result = mysqli_query($GLOBALS['link'],$query) or die("Query failed : " . mysqli_error($GLOBALS['link']));
while ($row = mysqli_fetch_row($result)) {
	echo '<li>'.tzdate("l, F j, Y, g:i a",$row[0]);
	if ($row[1]>0) {
		echo '.  On for about '.round(($row[1]-$row[0])/60).' minutes.';
	}
	echo '</li>';
}

echo '</ul>';
require("../footer.php");

?>
