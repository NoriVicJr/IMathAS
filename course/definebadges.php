<?php

require("../validate.php");

if (!isset($teacherid)) {
	echo "You are not authorized to view this page";
	exit;
}

$curBreadcrumb = "$breadcrumbbase <a href=\"course.php?cid={$_GET['cid']}\">$coursename</a> ";

if (empty($_GET['badgeid'])) {
	require("../header.php");
	
	echo '<div class="breadcrumb">'.$curBreadcrumb.' &gt; Badge Settings</div>';
	echo '<div id="headerbadgesettings" class="pagetitle"><h2>Badge Settings</h2></div>';

	$query = "SELECT id,name FROM imas_badgesettings WHERE courseid='$cid'";
	$result = mysqli_query($GLOBALS['link'],$query) or die("Query failed : " . mysqli_error($GLOBALS['link']));
	if (mysqli_num_rows($result)!=0) {
		echo '<ul>';
		while ($row=mysqli_fetch_row($result)) {
			echo '<li><a href="definebadges.php?cid='.$cid.'&amp;badgeid='.$row[0].'">'.$row[1].'</a> ';
			echo '<a class="small" href="definebadges.php?cid='.$cid.'&amp;badgeid='.$row[0].'&amp;delete=true" onclick="return confirm(\'Are you sure you want to delete this badge definition and invalidate all awarded badges?\');">[Delete]</a> ';
			echo '<br/><a href="claimbadge.php?cid='.$cid.'&amp;badgeid='.$row[0].'">Link to claim badge</a> (provide to students)';
			echo '</li>';
		}
		echo '</ul>';
	} else {
		echo '<p>No badges have been defined</p>';
	}
	echo '<p><a href="definebadges.php?cid='.$cid.'&amp;badgeid=new">Add New Badge</a></p>';
	require("../footer.php");
	
	
} else {
	if (!empty($_GET['delete'])) {
		$badgeid = intval($_GET['badgeid']);
		if ($badgeid==0) { echo 'Can not delete - invalid badgeid'; exit;}
		$query = "SELECT courseid FROM imas_badgesettings WHERE id=$badgeid";
		$result = mysqli_query($GLOBALS['link'],$query) or die("Query failed : " . mysqli_error($GLOBALS['link']));
		if (mysql_fetch_first($result) != $cid) { echo 'Can not delete - badgeid is for a different course'; exit;}
		
		$query = "DELETE FROM imas_badgesettings WHERE id=$badgeid";
		mysqli_query($GLOBALS['link'],$query) or die("Query failed : " . mysqli_error($GLOBALS['link']));
		$query = "DELETE FROM imas_badgerecords WHERE badgeid=$badgeid";
		mysqli_query($GLOBALS['link'],$query) or die("Query failed : " . mysqli_error($GLOBALS['link']));
		header('Location: ' . $urlmode  . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/definebadges.php?cid=$cid");
		exit;
	}
	if (!empty($_POST['badgename'])) { //postback
		$badgename = $_POST['badgename'];
		$badgetext = $_POST['badgetext'];
		$descr = $_POST['description'];
		$longdescr = $_POST['longdescription'];
		
		$req = array('data'=>array());
		$i = 0;
		while (isset($_POST['catselect'.$i])) {
			$_POST['catscore'.$i] = preg_replace('/[^\d\.]/','',$_POST['catscore'.$i]);
			if ($_POST['catselect'.$i]!='NS' && $_POST['catscore'.$i]!='' && is_numeric($_POST['catscore'.$i])) {
				$req['data'][] = array($_POST['catselect'.$i], $_POST['cattype'.$i], $_POST['catscore'.$i]);
			}
			$i++;
		}
		$req = addslashes(serialize($req));
		if ($_GET['badgeid']=='new') {
			$query = "INSERT INTO imas_badgesettings (name, badgetext, description, longdescription, courseid, requirements) VALUES ('$badgename','$badgetext','$descr','$longdescr','$cid','$req')";
			mysqli_query($GLOBALS['link'],$query) or die("Query failed : " . mysqli_error($GLOBALS['link']));
		} else {
			$badgeid = intval($_GET['badgeid']);
			$query = "UPDATE imas_badgesettings SET name='$badgename',badgetext='$badgetext',description='$descr', longdescription='$longdescr', requirements='$req' WHERE id='$badgeid' AND courseid='$cid'";
			mysqli_query($GLOBALS['link'],$query) or die("Query failed : " . mysqli_error($GLOBALS['link']));
		}
		header('Location: ' . $urlmode  . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/definebadges.php?cid=$cid");
		exit;
		
	} else {  // create form
		require("../includes/htmlutil.php");
		if ($_GET['badgeid']=='new') {
			$name = "Enter badge title";
			$badgetext = "Enter text";
			$descr = "";
			$longdescr = "";
			$badgeid = 'new';
			$req = array('data'=>array());
		} else {
			$badgeid = intval($_GET['badgeid']);
			$query = "SELECT name,badgetext,description,longdescription,requirements FROM imas_badgesettings WHERE id=$badgeid AND courseid='$cid'";
			$result = mysqli_query($GLOBALS['link'],$query) or die("Query failed : " . mysqli_error($GLOBALS['link']));
			if (mysqli_num_rows($result)==0) { echo 'Invalid badge id for this course'; exit;}
			
			list($name, $badgetext, $descr, $longdescr, $req) = mysqli_fetch_row($result);
			$req = unserialize($req);
		}
		$query = "SELECT id,name FROM imas_gbcats WHERE courseid='$cid' ORDER BY name";
		$result = mysqli_query($GLOBALS['link'],$query) or die("Query failed : " . mysqli_error($GLOBALS['link']));
		$gbvals = array('-1');
		$gblabels = array('Course total'); 
		while ($row=mysqli_fetch_row($result)) {
			$gbvals[]= $row[0];
			$gblabels[] = $row[1];
		}
		
		$gtvals = array('0','3','1','2');
		$gtlabels = array('Past Due', 'Past and Attempted', 'Past and Available', 'All (including future)'); 
		
		
		require("../header.php");
		echo '<div class="breadcrumb">'.$curBreadcrumb.' &gt; <a href="definebadges.php?cid='.$cid.'">Badge Settings</a> ';
		echo '&gt; Details</div>';
		echo '<div id="headerbadgesettings" class="pagetitle"><h2>Badge Setting Details</h2></div>';
		
		echo '<form method="post" action="definebadges.php?cid='.$cid.'&amp;badgeid='.$badgeid.'">';
		
		echo '<p>Badge Name: <input type="text" size="80" maxlength="128" name="badgename" value="'.$name.'"/><br/>Max 128 characters</p>';
		echo '<p>Badge Short Name: <input type="text" size="30" maxlength="128" name="badgetext" value="'.$badgetext.'"/> <br/>';
		echo 'This text also displays on the badge image. <br/>Keep it under 24 characters, and not more than 12 characters in a single word.';
		echo '<br>Alternatively, provide a URL for a 90x90 .png to use as the badge image</p>';
		
		echo '<p>Badge Short Description: <input type="text" size="80" maxlength="128" name="description" value="'.$descr.'"/><br/>Max 128 characters</p>';
		
		echo '<p>Badge Long Description:<br/> <textarea name="longdescription" cols="80" rows="5">'.$longdescr.'</textarea></p>';
		
		echo '<p>Select the badge requirements.  All conditions must be met for the badge to be earned</p>';
		
		
		echo '<table class="gb"><thead><tr><th>Gradebook Category Total</th><th>Score to Use</th><th>Minimum score required (%)</th></tr></thead><tbody>';
		for ($i=0; $i<count($gbvals)+4; $i++) {
			echo '<tr><td>';
			writeHtmlSelect("catselect$i",$gbvals,$gblabels,isset($req['data'][$i])?$req['data'][$i][0]:null,'Select...','NS');
			echo '</td><td>';
			writeHtmlSelect("cattype$i",$gtvals,$gtlabels,isset($req['data'][$i])?$req['data'][$i][1]:0);
			echo '</td><td><input type="text" size="3" name="catscore'.$i.'" value="'.(isset($req['data'][$i])?$req['data'][$i][2]:'').'"/>%</td></tr>';
		}
		echo '</tbody></table>';
		echo '<p><input type="submit" value="Save"/></p>';
		echo '</form>';
		require("../footer.php");
	}	
}



?>
