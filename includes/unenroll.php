<?php

//util function for unenrolling students
//$cid = courseid
//$tounenroll = array of userids
//$delforum = delete all forum posts
//$deloffline = delete offline items from gradebook
//$unwithdraw = unset any withdrawn questions
//$delwikirev = delete wiki revisions, 1: all, 2: group wikis only
function unenrollstu($cid,$tounenroll,$delforum=false,$deloffline=false,$withwithdraw=false,$delwikirev=false) {
	$forums = array();
	$threads = array();
	$query = "SELECT id FROM imas_forums WHERE courseid='$cid'";
	$result = mysqli_query($GLOBALS['link'],$query) or die("Query failed : " . mysqli_error($GLOBALS['link']));
	while ($row = mysqli_fetch_row($result)) {
		$forums[] = $row[0];
		$q2 = "SELECT threadid FROM imas_forum_posts WHERE forumid='{$row[0]}'";
		$r2 = mysqli_query($GLOBALS['link'],$q2) or die("Query failed : " . mysqli_error($GLOBALS['link']));
		while ($rw2 = mysqli_fetch_row($r2)) {
			$threads[] = $rw2[0];
		}
	}
	$threadlist = implode(',',$threads);
	$forumlist = implode(',',$forums);
	
	$assesses = array();
	$query = "SELECT id FROM imas_assessments WHERE courseid='$cid'";
	$result = mysqli_query($GLOBALS['link'],$query) or die("Query failed : " . mysqli_error($GLOBALS['link']));
	while ($row = mysqli_fetch_row($result)) {
		$assesses[] = $row[0];
	}
	$aidlist =  implode(',',$assesses);
	
	$wikis = array();
	$grpwikis = array();
	$query = "SELECT id,groupsetid FROM imas_wikis WHERE courseid='$cid'";
	$result = mysqli_query($GLOBALS['link'],$query) or die("Query failed : " . mysqli_error($GLOBALS['link']));
	while ($row = mysqli_fetch_row($result)) {
		$wikis[] = $row[0];
		if ($row[1]>0) {
			$grpwikis[] = $row[0];
		}
	}
	$wikilist =  implode(',',$wikis);
	$grpwikilist = implode(',',$grpwikis);
	
	$drills = array();
	$query = "SELECT id FROM imas_drillassess WHERE courseid='$cid'";
	$result = mysqli_query($GLOBALS['link'],$query) or die("Query failed : " . mysqli_error($GLOBALS['link']));
	while ($row = mysqli_fetch_row($result)) {
		$drills[] = $row[0];
	}
	$drilllist =  implode(',',$drills);
	
	
	$stugroups = array();
	$query = "SELECT imas_stugroups.id FROM imas_stugroups JOIN imas_stugroupset ON imas_stugroups.groupsetid=imas_stugroupset.id WHERE imas_stugroupset.courseid='$cid'";
	$result = mysqli_query($GLOBALS['link'],$query) or die("Query failed : " . mysqli_error($GLOBALS['link']));
	while ($row = mysqli_fetch_row($result)) {
		$stugroups[] = $row[0];
	}
	$curdir = rtrim(dirname(__FILE__), '/\\');
	require_once("$curdir/filehandler.php");
	if (count($tounenroll)>0) {
		$gbitems = array();
		$query = "SELECT id FROM imas_gbitems WHERE courseid='$cid'";
		$result = mysqli_query($GLOBALS['link'],$query) or die("Query failed : " . mysqli_error($GLOBALS['link']));
		while ($row = mysqli_fetch_row($result)) {
			$gbitems[] = $row[0];
		}
		$gblist = implode(',',$gbitems);
		//new
		$stulist = "'".implode("','",$tounenroll)."'";
		if (count($assesses)>0) {
			deleteasidfilesbyquery2('userid',$tounenroll,$assesses);
			//deleteasidfilesbyquery(array('assessmentid'=>$assesses, 'userid'=>$tounenroll));
			$query = "DELETE FROM imas_assessment_sessions WHERE assessmentid IN ($aidlist) AND userid IN ($stulist)";
			mysqli_query($GLOBALS['link'],$query) or die("Query failed : $query" . mysqli_error($GLOBALS['link']));
			$query = "DELETE FROM imas_exceptions WHERE assessmentid IN ($aidlist) AND userid IN ($stulist)";
			mysqli_query($GLOBALS['link'],$query) or die("Query failed : $query" . mysqli_error($GLOBALS['link']));	
		}
		if (count($drills)>0) {
			$query = "DELETE FROM imas_drillassess_sessions WHERE drillassessid IN ($drilllist) AND userid IN ($stulist)";
			mysqli_query($GLOBALS['link'],$query) or die("Query failed : $query" . mysqli_error($GLOBALS['link']));
		}
		if (count($gbitems)>0) {
			$query = "DELETE FROM imas_grades WHERE gradetype='offline' AND gradetypeid IN ($gblist) AND userid IN ($stulist)";
			mysqli_query($GLOBALS['link'],$query) or die("Query failed : $query" . mysqli_error($GLOBALS['link']));
		}
		if (count($threads)>0) {
			$query = "DELETE FROM imas_forum_views WHERE threadid IN ($threadlist)  AND userid IN ($stulist)";
			mysqli_query($GLOBALS['link'],$query) or die("Query failed : $query" . mysqli_error($GLOBALS['link']));
		}
		if (count($wikis)>0) {
			$query = "DELETE FROM imas_wiki_views WHERE wikiid IN ($wikilist)  AND userid IN ($stulist)";
			mysqli_query($GLOBALS['link'],$query) or die("Query failed : $query" . mysqli_error($GLOBALS['link']));
		}
		
		if (count($stugroups)>0) {
			$stugrouplist = implode(',',$stugroups);
			$query = "DELETE FROM imas_stugroupmembers WHERE userid IN ($stulist) AND stugroupid IN ($stugrouplist)";
			mysqli_query($GLOBALS['link'],$query) or die("Query failed : $query" . mysqli_error($GLOBALS['link']));
		}
		
		
		
	}
	if ($delforum && count($forums)>0) {
		$query = "DELETE imas_forum_threads FROM imas_forum_posts JOIN imas_forum_threads ON imas_forum_posts.threadid=imas_forum_threads.id AND imas_forum_posts.posttype=0 WHERE imas_forum_threads.forumid IN ($forumlist)";
		mysqli_query($GLOBALS['link'],$query) or die("Query failed : " . mysqli_error($GLOBALS['link']));
		
		$query = "SELECT id FROM imas_forum_posts WHERE forumid IN ($forumlist) AND files<>''";
		$result = mysqli_query($GLOBALS['link'],$query) or die("Query failed : " . mysqli_error($GLOBALS['link']));
		while ($row = mysqli_fetch_row($result)) {
			deleteallpostfiles($row[0]);	
		}
		
		$query = "DELETE FROM imas_forum_posts WHERE forumid IN ($forumlist) AND posttype=0";
		mysqli_query($GLOBALS['link'],$query) or die("Query failed : " . mysqli_error($GLOBALS['link']));	
		
		if (count($tounenroll)>0) {
			$query = "DELETE FROM imas_grades WHERE gradetype='forum' AND gradetypeid IN ($forumlist) AND userid IN ($stulist)";
			mysqli_query($GLOBALS['link'],$query) or die("Query failed : " . mysqli_error($GLOBALS['link']));	
		}
		/* //old
		foreach ($forums as $fid) {
			$query = "DELETE imas_forum_threads FROM imas_forum_posts JOIN imas_forum_threads ON imas_forum_posts.threadid=imas_forum_threads.id AND imas_forum_posts.posttype=0 WHERE imas_forum_threads.forumid='$fid'";
			mysqli_query($GLOBALS['link'],$query) or die("Query failed : " . mysqli_error($GLOBALS['link']));
			
			$query = "DELETE FROM imas_forum_posts WHERE forumid='$fid' AND posttype=0";
			mysqli_query($GLOBALS['link'],$query) or die("Query failed : " . mysqli_error($GLOBALS['link']));	
		}*/
	}
	if ($delwikirev===1 && count($wikis)>0) {
		$query = "DELETE FROM imas_wiki_revisions WHERE wikiid IN ($wikilist)";
		mysqli_query($GLOBALS['link'],$query) or die("Query failed : " . mysqli_error($GLOBALS['link']));
	} else if ($delwikirev===2 && count($grpwikis)>0) {
		$query = "DELETE FROM imas_wiki_revisions WHERE wikiid IN ($grpwikilist)";
		mysqli_query($GLOBALS['link'],$query) or die("Query failed : " . mysqli_error($GLOBALS['link']));
	}
	if ($deloffline) {
		$query = "DELETE FROM imas_gbitems WHERE courseid='$cid'";
		mysqli_query($GLOBALS['link'],$query) or die("Query failed : " . mysqli_error($GLOBALS['link']));
	}
	if ($withwithdraw=='unwithdraw' && count($assesses)>0) {
		$query = "UPDATE imas_questions SET withdrawn=0 WHERE assessmentid IN ($aidlist)";
		mysqli_query($GLOBALS['link'],$query) or die("Query failed : " . mysqli_error($GLOBALS['link']));
		/*foreach ($assesses as $aid) {
			$query = "UPDATE imas_questions SET withdrawn=0 WHERE assessmentid='$aid'";
			mysqli_query($GLOBALS['link'],$query) or die("Query failed : " . mysqli_error($GLOBALS['link']));
		}*/
	} else if ($withwithdraw=='remove' && count($assesses)>0) {
		
	}
	if (count($tounenroll)>0) {
		$query = "DELETE FROM imas_students WHERE userid IN ($stulist) AND courseid='$cid'";
		mysqli_query($GLOBALS['link'],$query) or die("Query failed : $query" . mysqli_error($GLOBALS['link']));
		
		$query = "DELETE FROM imas_login_log WHERE userid IN ($stulist) AND courseid='$cid'";
		mysqli_query($GLOBALS['link'],$query) or die("Query failed : $query" . mysqli_error($GLOBALS['link']));
	}
		 
}

?>
