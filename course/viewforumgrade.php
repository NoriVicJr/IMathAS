<?php
//IMathAS: View of forum grade details, to be linked from gradebook
//(c) 2011 David Lippman

	require("../validate.php");
	$cid = intval($_GET['cid']);
	
	if (isset($teacherid)) {
		$isteacher = true;
	} else {
		$isteacher = false;
	}
	$stu = intval($_GET['stu']);
	
	if ($isteacher) {
		$uid = intval($_GET['uid']);	
	} else {
		$uid = $userid;
	}
	$forumid = intval($_GET['fid']);
	
	if ($isteacher && (isset($_POST['score']) || isset($_POST['newscore']))) {
		//check for grades marked as newscore that aren't really new
		//shouldn't happen, but could happen if two browser windows open
		if (isset($_POST['newscore'])) {
			$keys = array_keys($_POST['newscore']);
			foreach ($keys as $k=>$v) {
				if (trim($v)=='') {unset($keys[$k]);}
			}
			if (count($keys)>0) {
				$kl = implode(',',$keys);
				$query = "SELECT refid FROM imas_grades WHERE gradetype='forum' AND gradetypeid='$forumid' AND userid='$uid' AND refid IN ($kl)";
				$result = mysqli_query($GLOBALS['link'],$query) or die("Query failed : " . mysqli_error($GLOBALS['link']));
				while($row = mysql_fetch_row($result)) {
					$_POST['score'][$row[0]] = $_POST['newscore'][$row[0]];
					unset($_POST['newscore'][$row[0]]);
				}
				
			}
		}
		if (isset($_POST['score'])) {
			foreach($_POST['score'] as $k=>$sc) {
				if (trim($k)=='') {continue;}
				$sc = trim($sc);
				if ($sc!='') {
					$query = "UPDATE imas_grades SET score='$sc',feedback='{$_POST['feedback'][$k]}' WHERE refid='$k' AND gradetype='forum' AND gradetypeid='$forumid' AND userid='$uid'";
					mysqli_query($GLOBALS['link'],$query) or die("Query failed : " . mysqli_error($GLOBALS['link']));
				} else {
					$query = "DELETE FROM imas_grades WHERE refid='$k' AND gradetype='forum' AND gradetypeid='$forumid' AND userid='$uid'";
					mysqli_query($GLOBALS['link'],$query) or die("Query failed : " . mysqli_error($GLOBALS['link']));
				}
			}
		}
		if (isset($_POST['newscore'])) {
			foreach($_POST['newscore'] as $k=>$sc) {
				if (trim($k)=='') {continue;}			
				if ($sc!='') {
					$query = "INSERT INTO imas_grades (gradetype,gradetypeid,refid,userid,score,feedback) VALUES ";
					$query .= "('forum','$forumid','$k','$uid','$sc','{$_POST['feedback'][$k]}')";
					mysqli_query($GLOBALS['link'],$query) or die("Query failed : " . mysqli_error($GLOBALS['link']));
				}
			}
		}
		header('Location: ' . $urlmode  . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/gradebook.php?stu=$stu&cid=$cid");
		exit;
	}
	
	$pagetitle = "View Forum Grade";
	require("../header.php");
	echo "<div class=breadcrumb>$breadcrumbbase <a href=\"course.php?cid=$cid\">$coursename</a> ";
	echo "&gt; <a href=\"gradebook.php?stu=$stu&cid=$cid\">Gradebook</a> ";
	echo "&gt; View Forum Grade</div>";
	
	$query = "SELECT iu.LastName,iu.FirstName,i_f.name,i_f.points FROM imas_users AS iu, imas_forums as i_f ";
	$query .= "WHERE iu.id='$uid' AND i_f.id='$forumid'";
	$result = mysqli_query($GLOBALS['link'],$query) or die("Query failed : " . mysqli_error($GLOBALS['link']));
	$row = mysql_fetch_row($result);
	$possiblepoints = $row[3];
	
	echo '<div id="headerviewforumgrade" class="pagetitle"><h2>View Forum Grade</h2></div>';
	echo "<p>Grades on forum <b>{$row[2]}</b> for <b>{$row[1]} {$row[0]}</b></p>";
	
	
	$scores = array();
	$query = "SELECT score,feedback,refid FROM imas_grades WHERE gradetype='forum' AND gradetypeid='$forumid' AND userid='$uid'";
	$result = mysqli_query($GLOBALS['link'],$query) or die("Query failed : " . mysqli_error($GLOBALS['link']));
	$totalpts = 0;
	while ($row = mysql_fetch_row($result)) {
		$scores[$row[2]] = $row;
		$totalpts += $row[0];
	}
	
	if ($possiblepoints==0) {
		echo '<p>This forum is not a graded forum</p>';
	} else {
		echo "<p>Total:  $totalpts out of $possiblepoints</p>";
	}
	
	if ($isteacher) {
		echo "<form method=\"post\" action=\"viewforumgrade.php?cid=$cid&fid=$forumid&stu=$stu&uid=$uid\">";
	}
	
	echo '<table class="gb"><thead><tr><th>Post</th><th>Points</th><th>Private Feedback</th></tr></thead><tbody>';
	$query = "SELECT id,threadid,subject FROM imas_forum_posts WHERE forumid='$forumid' AND userid='$uid'";
	$result = mysqli_query($GLOBALS['link'],$query) or die("Query failed : " . mysqli_error($GLOBALS['link']));
	while ($row = mysql_fetch_row($result)) {
		echo "<tr><td><a href=\"$imasroot/forums/posts.php?cid=$cid&forum=$forumid&thread={$row[1]}\">{$row[2]}</a></td>";
		if ($isteacher) {
			if (isset($scores[$row[0]])) {
				echo "<td><input type=text size=3 name=\"score[{$row[0]}]\" id=\"score{$row[0]}\" value=\"";
				echo $scores[$row[0]][0];
			} else {
				echo "<td><input type=text size=3 name=\"newscore[{$row[0]}]\" id=\"score{$row[0]}\" value=\"";
			}
			echo "\" /> </td>";
			echo "<td><textarea cols=40 rows=1 id=\"feedback{$row[0]}\" name=\"feedback[{$row[0]}]\">{$scores[$row[0]][1]}</textarea></td>";
		} else {
			if (isset($scores[$row[0]])) {
				echo '<td>'.$scores[$row[0]][0].'</td>';
			} else {
				echo "<td>-</td>";
			}
			echo '<td>'.$scores[$row[0]][1].'</td>';
		} 
		echo "</tr>";
	}
	if ($isteacher || isset($scores[0])) {
		echo '<tr><td>Additional score</td>';
		if ($isteacher) {
			if (isset($scores[0])) {
				echo "<td><input type=text size=3 name=\"score[0]\" id=\"score0\" value=\"";
				echo $scores[0][0];
			} else {
				echo "<td><input type=text size=3 name=\"newscore[0]\" id=\"score0\" value=\"";
			}
			echo "\" /> </td>";
			echo "<td><textarea cols=40 rows=1 id=\"feedback0\" name=\"feedback[0]\">{$scores[0][1]}</textarea></td>";
		} else {
			echo '<td>'.$scores[0][0].'</td>';
			echo '<td>'.$scores[0][1].'</td>';
		} 
		echo "</tr>";
	}
	echo '</tbody></table>';
	if ($isteacher) {
		echo '<p><input type="submit" value="Save Scores" /></p>';
		echo '</form>';
	}
	require("../footer.php");
?>
	
	
