<?php
//IMathAS:  Redeem latepasses
//(c) 2007 David Lippman

	require("../validate.php");
	$cid = $_GET['cid'];
	$aid = $_GET['aid'];
	
	$query = "SELECT latepasshrs FROM imas_courses WHERE id='$cid'";
	$result = mysqli_query($GLOBALS['link'],$query) or die("Query failed : " . mysqli_error($GLOBALS['link']));
	$hours = mysqli_fetch_first($result);
		
	if (isset($_GET['undo'])) {
		require("../header.php");
		echo "<div class=breadcrumb>$breadcrumbbase ";
		if ($cid>0 && (!isset($sessiondata['ltiitemtype']) || $sessiondata['ltiitemtype']!=0)) {
			echo " <a href=\"../course/course.php?cid=$cid\">$coursename</a> &gt; ";
		}
		echo "Un-use LatePass</div>";
		$query = "SELECT enddate,islatepass FROM imas_exceptions WHERE userid='$userid' AND assessmentid='$aid'";
		$result = mysqli_query($GLOBALS['link'],$query) or die("Query failed : " . mysqli_error($GLOBALS['link']));
		if (mysqli_num_rows($result)==0) {
			echo '<p>Invalid</p>';
		} else {
			$row = mysqli_fetch_row($result);
			if ($row[1]==0) {
				echo '<p>Invalid</p>';
			} else {
				$now = time();
				$query = "SELECT enddate FROM imas_assessments WHERE id='$aid'";
				$result = mysqli_query($GLOBALS['link'],$query) or die("Query failed : " . mysqli_error($GLOBALS['link']));
				$enddate = mysqli_fetch_first($result);
				//if it's past original due date and latepass is for less than latepasshrs past now, too late
				if ($now > $enddate && $row[0] < $now + $hours*60*60) {
					echo '<p>Too late to un-use this LatePass</p>';
				} else {
					if ($now < $enddate) { //before enddate, return all latepasses
						$n = $row[1];
						$query = "DELETE FROM imas_exceptions WHERE userid='$userid' AND assessmentid='$aid'";
						mysqli_query($GLOBALS['link'],$query) or die("Query failed : " . mysqli_error($GLOBALS['link']));
					} else { //figure how many are unused
						$n = floor(($row[0] - $now)/($hours*60*60));
						$newend = $row[0] - $n*$hours*60*60;
						if ($row[1]>$n) {
							$query = "UPDATE imas_exceptions SET islatepass=islatepass-$n,enddate=$newend WHERE userid='$userid' AND assessmentid='$aid'";
							mysqli_query($GLOBALS['link'],$query) or die("Query failed : " . mysqli_error($GLOBALS['link']));
						} else {
							$query = "DELETE FROM imas_exceptions WHERE userid='$userid' AND assessmentid='$aid'";
							mysqli_query($GLOBALS['link'],$query) or die("Query failed : " . mysqli_error($GLOBALS['link']));
							$n = $row[1];
						}
					}
					echo "<p>Returning $n LatePass".($n>1?"es":"")."</p>";
					$query = "UPDATE imas_students SET latepass=latepass+$n WHERE userid='$userid' AND courseid='$cid'";
					mysqli_query($GLOBALS['link'],$query) or die("Query failed : " . mysqli_error($GLOBALS['link']));
				}
			}
		}
		
		if ((!isset($sessiondata['ltiitemtype']) || $sessiondata['ltiitemtype']!=0)) {
			echo "<p><a href=\"course.php?cid=$cid\">Continue</a></p>";
		} else {
			echo "<p><a href=\"../assessment/showtest.php?cid=$cid&id={$sessiondata['ltiitemid']}\">Continue</a></p>";
		}
		require("../footer.php");
		
	} else if (isset($_GET['confirm'])) {
		$addtime = $hours*60*60;
		$query = "SELECT allowlate,enddate,startdate FROM imas_assessments WHERE id='$aid'";
		$result = mysqli_query($GLOBALS['link'],$query) or die("Query failed : " . mysqli_error($GLOBALS['link']));
		list($allowlate,$enddate,$startdate) =mysqli_fetch_row($result);
		if ($allowlate==1) {
			$query = "UPDATE imas_students SET latepass=latepass-1 WHERE userid='$userid' AND courseid='$cid' AND latepass>0";
			$result = mysqli_query($GLOBALS['link'],$query) or die("Query failed : " . mysqli_error($GLOBALS['link']));
			if (mysqli_affected_rows($GLOBALS['link'])>0) {
				$query = "SELECT enddate FROM imas_exceptions WHERE userid='$userid' AND assessmentid='$aid'";
				$result = mysqli_query($GLOBALS['link'],$query) or die("Query failed : " . mysqli_error($GLOBALS['link']));
				if (mysqli_num_rows($result)>0) { //already have exception
					$query = "UPDATE imas_exceptions SET enddate=enddate+$addtime,islatepass=islatepass+1 WHERE userid='$userid' AND assessmentid='$aid'";
					mysqli_query($GLOBALS['link'],$query) or die("Query failed : " . mysqli_error($GLOBALS['link']));
				} else {
					$enddate = $enddate + $addtime;
					$query = "INSERT INTO imas_exceptions (userid,assessmentid,startdate,enddate,islatepass) VALUES ('$userid','$aid','$startdate','$enddate',1)";
					mysqli_query($GLOBALS['link'],$query) or die("Query failed : " . mysqli_error($GLOBALS['link']));
				}
			}
		}
		if ((!isset($sessiondata['ltiitemtype']) || $sessiondata['ltiitemtype']!=0)) {
			header('Location: ' . $urlmode  . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/course.php?cid=$cid");
		} else {
			header('Location: ' . $urlmode  . $_SERVER['HTTP_HOST'] . $imasroot . "/assessment/showtest.php?cid=$cid&id={$sessiondata['ltiitemid']}");
		}
	} else {
		require("../header.php");
		echo "<div class=breadcrumb>$breadcrumbbase ";
		if ($cid>0 && (!isset($sessiondata['ltiitemtype']) || $sessiondata['ltiitemtype']!=0)) {
			echo " <a href=\"../course/course.php?cid=$cid\">$coursename</a> &gt; ";
		}
		echo "Redeem LatePass</div>\n"; 
		//$curBreadcrumb = "$breadcrumbbase <a href=\"course.php?cid=$cid\"> $coursename</a>\n";
		//$curBreadcrumb .= " Redeem LatePass\n";
		//echo "<div class=\"breadcrumb\">$curBreadcrumb</div>";
		
		$query = "SELECT latepass FROM imas_students WHERE userid='$userid' AND courseid='$cid'";
		$result = mysqli_query($GLOBALS['link'],$query) or die("Query failed : " . mysqli_error($GLOBALS['link']));
		$numlatepass = mysqli_fetch_first($result);
		
		if ($numlatepass==0) { //shouldn't get here if 0
			echo "<p>You have no late passes remaining</p>";
		} else {
			echo '<div id="headerredeemlatepass" class="pagetitle"><h2>Redeem LatePass</h2></div>';
			echo "<form method=post action=\"redeemlatepass.php?cid=$cid&aid=$aid&confirm=true\">";
			echo "<p>You have $numlatepass LatePass(es) remaining.  You can redeem one LatePass for a $hours hour ";
			echo "extension on this assessment.  Are you sure you want to redeem a LatePass?</p>";
			echo "<input type=submit value=\"Yes, Redeem LatePass\"/>";
			if ((!isset($sessiondata['ltiitemtype']) || $sessiondata['ltiitemtype']!=0)) {
				echo "<input type=button value=\"Nevermind\" onclick=\"window.location='course.php?cid=$cid'\"/>";
			} else {
				echo "<input type=button value=\"Nevermind\" onclick=\"window.location='../assessment/showtest.php?cid=$cid&id={$sessiondata['ltiitemid']}'\"/>";
			}
			echo "</form>";
		}
		require("../footer.php");
	}
	
?>
