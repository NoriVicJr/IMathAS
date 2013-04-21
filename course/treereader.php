<?php
//IMathAS:  Tree-style framed content reading based on block structure
//(c) 2011 David Lippman

require("../validate.php");
if (!isset($teacherid) && !isset($tutorid) && !isset($studentid) && !isset($guestid)) { // loaded by a NON-teacher
	echo "You are not enrolled in this course. Please return to the <a href=\"../index.php\">Home Page</a> and enroll";
	exit;
}
if ((!isset($_GET['folder']) || $_GET['folder']=='') && !isset($sessiondata['folder'.$cid])) {
	$_GET['folder'] = '0';  
	$sessiondata['folder'.$cid] = '0';
	writesessiondata();
} else if ((isset($_GET['folder']) && $_GET['folder']!='') && (!isset($sessiondata['folder'.$cid]) || $sessiondata['folder'.$cid]!=$_GET['folder'])) {
	$sessiondata['folder'.$cid] = $_GET['folder'];
	writesessiondata();
} else if ((!isset($_GET['folder']) || $_GET['folder']=='') && isset($sessiondata['folder'.$cid])) {
	$_GET['folder'] = $sessiondata['folder'.$cid];
}

if (isset($_GET['recordbookmark'])) {  //for recording bookmarks into the student's record
	$query = "UPDATE imas_bookmarks SET value='{$_GET['recordbookmark']}' WHERE userid='$userid' AND courseid='$cid' AND name='TR{$_GET['folder']}'";
	mysqli_query($GLOBALS['link'],$query) or die("Query failed : " . mysqli_error($GLOBALS['link']));
	if (mysqli_affected_rows($GLOBALS['link'])==0) {
		$query = "INSERT INTO imas_bookmarks (userid,courseid,name,value) VALUES ('$userid','$cid','TR{$_GET['folder']}','{$_GET['recordbookmark']}')";
		mysqli_query($GLOBALS['link'],$query) or die("Query failed : " . mysqli_error($GLOBALS['link']));
	}
	return "OK";
	exit;
}

$cid = intval($_GET['cid']);
$query = "SELECT name,itemorder,hideicons,picicons,allowunenroll,msgset,chatset,topbar,cploc FROM imas_courses WHERE id=$cid";
$result = mysqli_query($GLOBALS['link'],$query) or die("Query failed : " . mysqli_error($GLOBALS['link']));
$line = mysqli_fetch_assoc($result);
$items = unserialize($line['itemorder']);		

if ($_GET['folder']!='0') {
	$now = time() + $previewshift;
	$blocktree = explode('-',$_GET['folder']);
	$backtrack = array();
	for ($i=1;$i<count($blocktree);$i++) {
		$backtrack[] = array($items[$blocktree[$i]-1]['name'],implode('-',array_slice($blocktree,0,$i+1)));
		if (!isset($teacherid) && !isset($tutorid) && $items[$blocktree[$i]-1]['avail']<2 && $items[$blocktree[$i]-1]['SH'][0]!='S' &&($now<$items[$blocktree[$i]-1]['startdate'] || $now>$items[$blocktree[$i]-1]['enddate'] || $items[$blocktree[$i]-1]['avail']=='0')) {
			$_GET['folder'] = 0;
			$items = unserialize($line['itemorder']);
			unset($backtrack);
			unset($blocktree);
			break;
		}
		if (isset($items[$blocktree[$i]-1]['grouplimit']) && count($items[$blocktree[$i]-1]['grouplimit'])>0 && !isset($teacherid) && !isset($tutorid)) {
			if (!in_array('s-'.$studentinfo['section'],$items[$blocktree[$i]-1]['grouplimit'])) {
				echo 'Not authorized';
				exit;
			}
		}  
		$items = $items[$blocktree[$i]-1]['items']; //-1 to adjust for 1-indexing
	}
}
$curBreadcrumb = $breadcrumbbase;
if (isset($backtrack) && count($backtrack)>0) {
	$curBreadcrumb .= "<a href=\"course.php?cid=$cid&folder=0\">$coursename</a> ";
	for ($i=0;$i<count($backtrack);$i++) {
		$curBreadcrumb .= "&gt; ";
		if ($i!=count($backtrack)-1) {
			$curBreadcrumb .= "<a href=\"course.php?cid=$cid&folder={$backtrack[$i][1]}\">";
		}
		$curBreadcrumb .= stripslashes($backtrack[$i][0]);
		if ($i!=count($backtrack)-1) {
			$curBreadcrumb .= "</a>";
		}
	}
	$curname = $backtrack[count($backtrack)-1][0];
	if (count($backtrack)==1) {
		$backlink =  "<span class=right><a href=\"course.php?cid=$cid&folder=0\">Back</a></span><br class=\"form\" />";
	} else {
		$backlink = "<span class=right><a href=\"course.php?cid=$cid&folder=".$backtrack[count($backtrack)-2][1]."\">Back</a></span><br class=\"form\" />";
	}
} else {
	$curBreadcrumb .= $coursename;
	$curname = $coursename;
}


//Start Output
$pagetitle = "Content Browser";
$placeinhead = '<script type="text/javascript">function toggle(id) {
	node = document.getElementById(id);
	button = document.getElementById("b"+id);
	if (node.className.match("show")) {
		node.className = node.className.replace(/show/,"hide");
		button.innerHTML = "+";
	} else {
		node.className = node.className.replace(/hide/,"show");
		button.innerHTML = "-";
	}
}
function resizeiframe() {
	var windowheight = document.documentElement.clientHeight;
	var theframe = document.getElementById("readerframe");
	var framepos = findPos(theframe);
	var height =  (windowheight - framepos[1] - 15);
	theframe.style.height =height + "px";
}

function recordlasttreeview(id) {
	var url = "'.$urlmode . $_SERVER['HTTP_HOST'] . $imasroot . '/course/treereader.php?cid='.$cid.'&folder='.$_GET['folder'].'&recordbookmark=" + id;
	basicahah(url, "bmrecout");
}
var treereadernavstate = 1;
function toggletreereadernav() {
	if (treereadernavstate==1) {
		document.getElementById("leftcontent").style.width = "20px";
		document.getElementById("leftcontenttext").style.display = "none";
		document.getElementById("centercontent").style.marginLeft = "30px";
		document.getElementById("navtoggle").src= document.getElementById("navtoggle").src.replace(/collapse/,"expand");
	} else {
		document.getElementById("leftcontent").style.width = "250px";
		document.getElementById("leftcontenttext").style.display = "";
		document.getElementById("centercontent").style.marginLeft = "260px";
		document.getElementById("navtoggle").src= document.getElementById("navtoggle").src.replace(/expand/,"collapse");
	}
	resizeiframe();
	treereadernavstate = (treereadernavstate+1)%2;
}
function updateTRunans(aid, status) {
	var urlbase = "'.$urlmode.$_SERVER['HTTP_HOST'] . $imasroot.'";
	if (status==0) {
		document.getElementById("aimg"+aid).src = urlbase+"/img/q_fullbox.gif";
	} else if (status==1) {
		document.getElementById("aimg"+aid).src = urlbase+"/img/q_halfbox.gif";
	} else {
		document.getElementById("aimg"+aid).src = urlbase+"/img/q_emptybox.gif";
	}
}
addLoadEvent(resizeiframe);
</script>
<style type="text/css">
img {
vertical-align: middle;
}
html, body {
height: auto;
}
#leftcontent {
	margin-top: 0px;
}
</style>';
$placeinhead .= "<style type=\"text/css\">\n<!--\n@import url(\"$imasroot/course/libtree.css\");\n-->\n</style>\n";
require("../header.php");

$query = "SELECT value FROM imas_bookmarks WHERE userid='$userid' AND courseid='$cid' AND name='TR{$_GET['folder']}'";
$result = mysqli_query($GLOBALS['link'],$query) or die("Query failed : " . mysqli_error($GLOBALS['link']));
if (mysqli_num_rows($result)==0) {
	$openitem = '';
} else {
	$openitem = mysqli_fetch_first($result);
}

$foundfirstitem = '';
$foundopenitem = '';

$astatus = array();
$query = "SELECT ia.id,ias.bestscores FROM imas_assessments AS ia JOIN imas_assessment_sessions AS ias ON ia.id=ias.assessmentid ";
$query .= "WHERE ia.courseid='$cid' AND ias.userid='$userid'";
$result = mysqli_query($GLOBALS['link'],$query) or die("Query failed : " . mysqli_error($GLOBALS['link']));
while ($row = mysqli_fetch_row($result)) {
	if (strpos($row[1],'-1')===false) {
		$astatus[$row[0]] = 2; //completed
	} else { //at least some undone
		$p = explode(',',$row[1]);
		foreach ($p as $v) {
			if (strpos($v,'-1')===false) {
				$astatus[$row[0]] = 1; //at least some is done	
				continue 2;
			}
		}
		$astatus[$row[0]] = 0; //unstarted
	}
}
		

function printlist($items) {
	global $cid,$imasroot,$foundfirstitem, $foundopenitem, $openitem, $astatus;
	$out = '';
	$isopen = false;
	foreach ($items as $item) {
		if (is_array($item)) { //is block
			//TODO check that it's available
			list($subcontent,$bisopen) = printlist($item['items']);
			if ($bisopen) {
				$isopen = true;
			}
			if ($bisopen) {
				$out .=  "<li class=lihdr><span class=hdr onClick=\"toggle({$item['id']})\"><span class=btn id=\"b{$item['id']}\">-</span> <img src=\"$imasroot/img/folder_tiny.png\"> ";
				$out .=  "{$item['name']}</span>\n";
				$out .=  '<ul class="show nomark" id="'.$item['id'].'">';
			} else {
				$out .=  "<li class=lihdr><span class=hdr onClick=\"toggle({$item['id']})\"><span class=btn id=\"b{$item['id']}\">+</span> <img src=\"$imasroot/img/folder_tiny.png\"> ";
				$out .=  "{$item['name']}</span>\n";
				$out .=  '<ul class="hide nomark" id="'.$item['id'].'">';
			}
			$out .= $subcontent;
			$out .=  '</ul></li>';
		} else {
			$query = "SELECT itemtype,typeid FROM imas_items WHERE id='$item'";
			$result = mysqli_query($GLOBALS['link'],$query) or die("Query failed : $query " . mysqli_error($GLOBALS['link']));
			$line = mysqli_fetch_assoc($result);
			$typeid = $line['typeid'];
			$itemtype = $line['itemtype'];
			/*if ($line['itemtype']=="Calendar") {
				$out .=  '<li><img src="'.$imasroot.'/img/calendar_tiny.png"> <a href="showcalendar.php?cid='.$cid.'" target="readerframe">Calendar</a></li>';
				if ($openitem=='' && $foundfirstitem=='') {
				 	 $foundfirstitem = '/course/showcalendar.php?cid='.$cid;
				 	 $isopen = true;
				}
			} else*/
			if ($line['itemtype']=='Assessment') {
				//TODO check availability, timelimit, etc.
				 $query = "SELECT name,summary,startdate,enddate,reviewdate,deffeedback,reqscore,reqscoreaid,avail,allowlate,timelimit,displaymethod FROM imas_assessments WHERE id='$typeid'";
				 $result = mysqli_query($GLOBALS['link'],$query) or die("Query failed : " . mysqli_error($GLOBALS['link']));
				 $line = mysqli_fetch_assoc($result);
				 if ($openitem=='' && $foundfirstitem=='') {
				 	 $foundfirstitem = '/assessment/showtest.php?cid='.$cid.'&amp;id='.$typeid; $isopen = true;
				 }
				 if ($itemtype.$typeid===$openitem) {
				 	 $foundopenitem = '/assessment/showtest.php?cid='.$cid.'&amp;id='.$typeid; $isopen = true;
				 }
				 $out .= '<li>';
				 if ($line['displaymethod']!='Embed') {
				 	 $out .=  '<img src="'.$imasroot.'/img/assess_tiny.png"> ';
				 } else {
					 if (!isset($astatus[$typeid]) || $astatus[$typeid]==0) {
						 $out .= '<img id="aimg'.$typeid.'" src="'.$imasroot.'/img/q_fullbox.gif" /> ';
					 } else if ($astatus[$typeid]==1) {
						 $out .= '<img id="aimg'.$typeid.'" src="'.$imasroot.'/img/q_halfbox.gif" /> ';
					 } else {
						 $out .= '<img id="aimg'.$typeid.'" src="'.$imasroot.'/img/q_emptybox.gif" /> ';
					 }
				 }
				 $out .= '<a href="'.$imasroot.'/assessment/showtest.php?cid='.$cid.'&amp;id='.$typeid.'"  onclick="recordlasttreeview(\''.$itemtype.$typeid.'\')" target="readerframe">'.$line['name'].'</a></li>';
			} else if ($line['itemtype']=='LinkedText') {
				//TODO check availability, etc.
				 $query = "SELECT title,summary,text,startdate,enddate,avail,target FROM imas_linkedtext WHERE id='$typeid'";
				 $result = mysqli_query($GLOBALS['link'],$query) or die("Query failed : " . mysqli_error($GLOBALS['link']));
				 $line = mysqli_fetch_assoc($result);
				 if ($openitem=='' && $foundfirstitem=='') {
				 	 $foundfirstitem = '/course/showlinkedtext.php?cid='.$cid.'&amp;id='.$typeid; $isopen = true;
				 }
				 if ($itemtype.$typeid===$openitem) {
				 	 $foundopenitem = '/assessment/showtest.php?cid='.$cid.'&amp;id='.$typeid; $isopen = true;
				 }
				 $out .=  '<li><img src="'.$imasroot.'/img/html_tiny.png"> <a href="showlinkedtext.php?cid='.$cid.'&amp;id='.$typeid.'"  onclick="recordlasttreeview(\''.$itemtype.$typeid.'\')"  target="readerframe">'.$line['title'].'</a></li>';
			} /*else if ($line['itemtype']=='Forum') {
				//TODO check availability, etc.
				 $query = "SELECT id,name,description,startdate,enddate,groupsetid,avail,postby,replyby FROM imas_forums WHERE id='$typeid'";
				 $result = mysqli_query($GLOBALS['link'],$query) or die("Query failed : " . mysqli_error($GLOBALS['link']));
				 $line = mysqli_fetch_assoc($result);
				 if ($openitem=='' && $foundfirstitem=='') {
				 	 $foundfirstitem = '/forums/thread.php?cid='.$cid.'&amp;forum='.$typeid; $isopen = true;
				 }
				 if ($itemtype.$typeid===$openitem) {
				 	 $foundopenitem = '/forums/thread.php?cid='.$cid.'&amp;forum='.$typeid; $isopen = true;
				 }
				 $out .=  '<li><img src="'.$imasroot.'/img/forum_tiny.png"> <a href="'.$imasroot.'/forums/thread.php?cid='.$cid.'&amp;forum='.$typeid.'" onclick="recordlasttreeview(\''.$itemtype.$typeid.'\')" target="readerframe">'.$line['name'].'</a></li>';
			} else if ($line['itemtype']=='Wiki') {
				//TODO check availability, etc.
				 $query = "SELECT id,name,description,startdate,enddate,editbydate,avail,settings,groupsetid FROM imas_wikis WHERE id='$typeid'";
				 $result = mysqli_query($GLOBALS['link'],$query) or die("Query failed : " . mysqli_error($GLOBALS['link']));
				 $line = mysqli_fetch_assoc($result);
				 if ($openitem=='' && $foundfirstitem=='') {
				 	 $foundfirstitem = '/wikis/viewwiki.php?cid='.$cid.'&amp;id='.$typeid; $isopen = true;
				 }
				 if ($itemtype.$typeid===$openitem) {
				 	 $foundopenitem = '/wikis/viewwiki.php?cid='.$cid.'&amp;id='.$typeid; $isopen = true;
				 }
				 $out .=  '<li><img src="'.$imasroot.'/img/wiki_tiny.png"> <a href="'.$imasroot.'/wikis/viewwiki.php?cid='.$cid.'&amp;id='.$typeid.'"  onclick="recordlasttreeview(\''.$itemtype.$typeid.'\')" target="readerframe">'.$line['name'].'</a></li>';
			} */
			
		}
	}
	return array($out,$isopen);
}
?>
<div class="breadcrumb">
	<span class="padright">
	<?php if (isset($guestid)) {
		echo '<span class="red">Instructor Preview</span> ';
	}?>
	<?php echo $userfullname ?>
	</span>
	<?php echo $curBreadcrumb ?>
	<div class="clear"></div>
</div>

<div id="leftcontent" style="width: 250px;">
<img id="navtoggle" src="<?php echo $imasroot;?>/img/collapse.gif"  onclick="toggletreereadernav()"/>
<ul id="leftcontenttext" class="nomark" style="margin-left:5px; font-size: 90%;">
<?php
$ul = printlist($items);
echo $ul[0];


?>
</ul>
<div id="bmrecout" style="display:none;"></div>
</div>
<div id="centercontent" style="margin-left: 260px;">

<iframe id="readerframe" name="readerframe" style="width:100%; border:1px solid #ccc;" src="<?php echo $imasroot . (($openitem=='')?$foundfirstitem:$foundopenitem); ?>"></iframe>
</div>
<?php
require("../footer.php");
?>
