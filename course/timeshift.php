<?php
//IMathAS:  Shift Course dates; made obsolete by mass change dates 
//(c) 2006 David Lippman

/*** master php includes *******/
require("../validate.php");

function writeHtmlSelect ($name,$valList,$labelList,$selectedVal=null,$defaultLabel=null,$defaultVal=null,$actions=null) {
	//$name is the html name for the select list
	//$valList is an array of strings for the html value tag
	//$labelList is an array of strings that are displayed as the select list
	//$selectVal is optional, if passed the item in $valList that matches will be output as selected

	echo "<select name=\"$name\" ";
	echo (isset($actions)) ? $actions : "" ;
	echo ">\n";
	if (isset($defaultLabel) && isset($defaultVal)) {
		echo "		<option value=\"$defaultVal\" selected>$defaultLabel</option>\n";
	}
	for ($i=0;$i<count($valList);$i++) {
		if ((isset($selectedVal)) && ($valList[$i]==$selectedVal)) {
			echo "		<option value=\"$valList[$i]\" selected>$labelList[$i]</option>\n";
		} else {
			echo "		<option value=\"$valList[$i]\">$labelList[$i]</option>\n";
		}
	}
	echo "</select>\n";	
	
} 

function shiftsub(&$itema) {
	global $shift;
	foreach ($itema as $k=>$item) {
		if (is_array($item)) {
			if ($itema[$k]['startdate'] > 0) {
				$itema[$k]['startdate'] += $shift;
			}
			if ($itema[$k]['enddate'] < 2000000000) {
				$itema[$k]['enddate'] += $shift;
			}
			shiftsub($itema[$k]['items']);
		}
	}
}
	
	
 //set some page specific variables and counters
$overwriteBody = 0;
$body = "";
$pagetitle = "Shift Course Dates";

	
	//CHECK PERMISSIONS AND SET FLAGS
if (!(isset($teacherid))) {
 	$overwriteBody = 1;
	$body = "You need to log in as a teacher to access this page";
} else {	//PERMISSIONS ARE OK, PERFORM DATA MANIPULATION	

	$cid = $_GET['cid'];
	
	if (isset($_POST['sdate'])) {
		
		$query = "SELECT startdate,enddate FROM imas_assessments WHERE id='{$_POST['aid']}'";
		$result = mysqli_query($GLOBALS['link'],$query) or die("Query failed : " . mysqli_error($GLOBALS['link']));
		$row = mysqli_fetch_row($result);
		$basedate = $row[intval($_POST['base'])];

		preg_match('/(\d+)\s*\/(\d+)\s*\/(\d+)/',$_POST['sdate'],$dmatches);
		$newstamp = mktime(date('G',$basedate),date('i',$basedate),0,$dmatches[1],$dmatches[2],$dmatches[3]);
		$shift = $newstamp-$basedate;
		
		$query = "SELECT itemorder FROM imas_courses WHERE id='$cid'";
		$result = mysqli_query($GLOBALS['link'],$query) or die("Query failed : $query" . mysqli_error($GLOBALS['link']));
		$items = unserialize(mysqli_fetch_first($result));

		shiftsub($items);
		$itemorder = addslashes(serialize($items));
		$query = "UPDATE imas_courses SET itemorder='$itemorder' WHERE id='$cid'";
		mysqli_query($GLOBALS['link'],$query) or die("Query failed : $query" . mysqli_error($GLOBALS['link']));
		
		$query = "SELECT itemtype,typeid FROM imas_items WHERE courseid='$cid'";
		$result = mysqli_query($GLOBALS['link'],$query) or die("Query failed : $query" . mysqli_error($GLOBALS['link']));
		while ($row=mysqli_fetch_row($result)) {
			if ($row[0]=="InlineText") {
				$table = "imas_inlinetext";
			} else if ($row[0]=="LinkedText") {
				$table = "imas_linkedtext";
			} else if ($row[0]=="Forum") {
				$table = "imas_forums";
			} else if ($row[0]=="Assessment") {
				$table = "imas_assessments";
			} else if ($row[0]=="Calendar") {
				continue;
			} else if ($row[0]=="Wiki") {
				$table = "imas_wikis";
			}
			$query = "UPDATE $table SET startdate=startdate+$shift WHERE id='{$row[1]}' AND startdate>0";
			mysqli_query($GLOBALS['link'],$query) or die("Query failed : $query" . mysqli_error($GLOBALS['link']));
			$query = "UPDATE $table SET enddate=enddate+$shift WHERE id='{$row[1]}' AND enddate<2000000000";
			mysqli_query($GLOBALS['link'],$query) or die("Query failed : $query" . mysqli_error($GLOBALS['link']));
			
			if ($row[0]=="Wiki") {
				$query = "UPDATE $table SET editbydate=editbydate+$shift WHERE id='{$row[1]}' AND editbydate>0 AND editbydate<2000000000";
				mysqli_query($GLOBALS['link'],$query) or die("Query failed : $query" . mysqli_error($GLOBALS['link']));
			} else if ($row[0]=="Forum") {
				$query = "UPDATE $table SET replyby=replyby+$shift WHERE id='{$row[1]}' AND replyby>0 AND replyby<2000000000";
				mysqli_query($GLOBALS['link'],$query) or die("Query failed : $query" . mysqli_error($GLOBALS['link']));
				
				$query = "UPDATE $table SET postby=postby+$shift WHERE id='{$row[1]}' AND postby>0 AND postby<2000000000";
				mysqli_query($GLOBALS['link'],$query) or die("Query failed : $query" . mysqli_error($GLOBALS['link']));
			}
		}
		
		//update Calendar items
		$query = "UPDATE imas_calitems SET date=date+$shift WHERE courseid='$cid'";
		mysqli_query($GLOBALS['link'],$query) or die("Query failed : $query" . mysqli_error($GLOBALS['link']));
			
		header('Location: ' . $urlmode  . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/course.php?cid=$cid");

		exit;
	} else { //DEFAULT DATA MANIPULATION
		$curBreadcrumb = "$breadcrumbbase <a href=\"course.php?cid=$cid\">$coursename</a>";
		$curBreadcrumb .= " &gt; Shift Course Dates ";
	
		$sdate = tzdate("m/d/Y",time());
	
		$query = "SELECT id,name from imas_assessments WHERE courseid='$cid'";
		$result = mysqli_query($GLOBALS['link'],$query) or die("Query failed : " . mysqli_error($GLOBALS['link']));
		$i=0;
		while ($line=mysqli_fetch_assoc($result)) {
			$page_assessmentList['val'][$i] = $line['id'];
			$page_assessmentList['label'][$i] = $line['name'];
			$i++;
		}
	
	}
}
	
/******* begin html output ********/
$placeinhead = "<script type=\"text/javascript\" src=\"$imasroot/javascript/DatePicker.js\"></script>";

require("../header.php");

if ($overwriteBody==1) {
	echo $body;
} else {		
?>
	<div class=breadcrumb><?php echo $curBreadcrumb ?></div>	
	<h3>Shift Course Dates</h3>
	<p>
		This page will change <b>ALL</b> course available dates and due dates based on 
		changing one item.  This is intended to allow you to reset all course item 
		dates for a new term in one action.
	</p>
	<form method=post action="timeshift.php?cid=<?php echo $cid ?>">
		<span class=form>Select an assessment to base the change on</span>
		<span class=formright>
			<?php writeHtmlSelect ("aid",$page_assessmentList['val'],$page_assessmentList['label'],null,null,null,$actions=" id=aid "); ?>
		</span><br class=form>
		<span class=form>Change dates based on this assessment's:</span>
		<span class=formright>
			<input type=radio id=base name=base value=0 >Available After date<br/>
			<input type=radio id=base name=base value=1 checked=1>Available Until date (Due date) <br/>
		</span><br class=form>
		<span class=form>Change date to:</span>
		<span class=formright>
			<input type=text size=10 name="sdate" value="<?php echo $sdate ?>"> 
			<a href="#" onClick="displayDatePicker('sdate', this); return false">
			<img src="../img/cal.gif" alt="Calendar"/>
			</a>
		</span><br class=form>
		<div class=submit><input type=submit value="Change Dates"></div>
	</form>
<?php
}
	
require("../footer.php");
	
	
?>
