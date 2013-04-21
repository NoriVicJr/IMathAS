<?php
//IMathAS:  Add/modify blocks of items on course page
//(c) 2006 David Lippman

/*** master php includes *******/
require("../validate.php");

/*** pre-html data manipulation, including function code *******/

//set some page specific variables and counters
$overwriteBody = 0;
$body = "";
$pagetitle = "Delete Course Block";
$curBreadcrumb = "$breadcrumbbase <a href=\"course.php?cid={$_GET['cid']}\">$coursename</a> &gt; Remove Block";

if (!(isset($_GET['cid']))) { //if the cid is missing go back to the index page
	$overwriteBody = 1;
	$body = "You need to access this page from the link on the course page";
} elseif (!(isset($teacherid))) {  //there is a cid but the user isn't a teacher
	$overwriteBody = 1;
	$body = "You need to log in as a teacher to access this page";
} elseif (isset($_GET['remove'])) { // a valid delete request loaded the page
	if ($_GET['remove']=="really") { // the request has been confirmed, delete the block
		$blocktree = explode('-',$_GET['id']);
		$blockid = array_pop($blocktree) - 1; //-1 adjust for 1-index
			
		$query = "SELECT itemorder FROM imas_courses WHERE id='{$_GET['cid']}'";
		$result = mysqli_query($GLOBALS['link'],$query) or die("Query failed : " . mysqli_error($GLOBALS['link']));
		$items = unserialize(mysqli_fetch_first($result));
		$sub =& $items;
		if (count($blocktree)>1) {
			for ($i=1;$i<count($blocktree);$i++) {
				$sub =& $sub[$blocktree[$i]-1]['items']; //-1 to adjust for 1-indexing
			}
		}
		if (is_array($sub[$blockid])) { //make sure it's really a block
			$blockitems = $sub[$blockid]['items'];
			$obid = $sub[$blockid]['id'];
			if (count($blockitems)>0) {
				if (isset($_POST['delcontents']) && $_POST['delcontents']==1) { //clear out contents of block
					require("delitembyid.php");
					delrecurse($blockitems);
					array_splice($sub,$blockid,1); //remove block and contained items from itemorder
				} else {
					array_splice($sub,$blockid,1,$blockitems); //remove block, replace with items in block
				}
			} else {
				array_splice($sub,$blockid,1); //empty block; just remove block
			}
		}
		$itemlist = addslashes(serialize($items));
		$query = "UPDATE imas_courses SET itemorder='$itemlist' WHERE id='{$_GET['cid']}'";
		mysqli_query($GLOBALS['link'],$query) or die("Query failed : " . mysqli_error($GLOBALS['link']));
		$obarr = explode(',',$_COOKIE['openblocks-'.$_GET['cid']]);
		$obloc = array_search($obid,$obarr);
		array_splice($obarr,$obloc,1);
		setcookie('openblocks-'.$_GET['cid'],implode(',',$obarr));
		header('Location: ' . $urlmode  . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/course.php?cid={$_GET['cid']}");
			
	} else {
		$blocktree = explode('-',$_GET['id']);
		$blockid = array_pop($blocktree) - 1; //-1 adjust for 1-index
			
		$query = "SELECT itemorder FROM imas_courses WHERE id='{$_GET['cid']}'";
		$result = mysqli_query($GLOBALS['link'],$query) or die("Query failed : " . mysqli_error($GLOBALS['link']));
		$items = unserialize(mysqli_fetch_first($result));
		$sub =& $items;
		if (count($blocktree)>1) {
			for ($i=1;$i<count($blocktree);$i++) {
				$sub =& $sub[$blocktree[$i]-1]['items']; //-1 to adjust for 1-indexing
			}
		}
		$itemname =  $sub[$blockid]['name'];
	}

}
/******* begin html output ********/
require("../header.php");

/**** post-html data manipulation ******/
// this page has no post-html data manipulation

/***** page body *****/
/***** php display blocks are interspersed throughout the html as needed ****/
if ($overwriteBody==1) {
	echo $body;
} else {
?>
	<div class=breadcrumb><?php echo $curBreadcrumb ?></div>
	<h3><?php echo $itemname; ?></h3>
	<form method=post action="deleteblock.php?cid=<?php echo $_GET['cid'] ?>&id=<?php echo $_GET['id'] ?>&remove=really">
	<p>Are you SURE you want to delete this Block?</p>
	<p><input type=radio name="delcontents" value="0" checked="checked"/>Move all items out of block<br/>
	<input type=radio name="delcontents" value="1"/>Also Delete all items in block</p>
	<p><input type=submit value="Yes, Remove">
	<input type=button value="Nevermind" onClick="window.location='course.php?cid=<?php echo $_GET['cid'] ?>'"></p>
<?php
}

require("../footer.php");
/**** end html code ******/
//nothing after the end of html for this page
/***** cleanup code ******/
//no cleanup code for this page

?>
