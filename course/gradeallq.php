<?php
//IMathAS:  Grade all of one question for an assessment
//(c) 2007 David Lippman
	require("../validate.php");
	
	if (!(isset($teacherid))) {
		require("../header.php");
		echo "You need to log in as a teacher to access this page";
		require("../footer.php");
		exit;
	}


	$cid = $_GET['cid'];
	$stu = $_GET['stu'];
	$gbmode = $_GET['gbmode'];
	$aid = $_GET['aid'];
	$qid = $_GET['qid'];
	if (isset($_GET['ver'])) {
		$ver = $_GET['ver'];
	} else {
		$ver = 'graded';
	}
	if (isset($_GET['page'])) {
		$page = intval($_GET['page']);
	} else {
		$page = -1;
	}
	
	if (isset($_GET['update'])) {
		$allscores = array();
		$grpscores = array();
		$grpfeedback = array();
		$locs = array();
		foreach ($_POST as $k=>$v) {
			if (strpos($k,'-')!==false) {
				$kp = explode('-',$k);
				if ($kp[0]=='ud') {
					//$locs[$kp[1]] = $kp[2];
					if (count($kp)==3) {
						if ($v=='N/A') {
							$allscores[$kp[1]][$kp[2]] = -1;
						} else {
							$allscores[$kp[1]][$kp[2]] = $v;
						}
					} else {
						if ($v=='N/A') {
							$allscores[$kp[1]][$kp[2]][$kp[3]] = -1;
						} else {
							$allscores[$kp[1]][$kp[2]][$kp[3]] = $v;
						}
					}
				}
			}
		}
		if (isset($_POST['onepergroup']) && $_POST['onepergroup']==1) {
			foreach ($_POST['groupasid'] as $grp=>$asid) {
				$grpscores[$grp] = $allscores[$asid];
				$grpfeedback[$grp] = $_POST['feedback-'.$asid];
			}
			$onepergroup = true;
		} else {
			$onepergroup = false;
		}
		
		$query = "SELECT imas_users.LastName,imas_users.FirstName,imas_assessment_sessions.* FROM imas_users,imas_assessment_sessions ";
		$query .= "WHERE imas_assessment_sessions.userid=imas_users.id AND imas_assessment_sessions.assessmentid='$aid' ";
		$query .= "ORDER BY imas_users.LastName,imas_users.FirstName";
		if ($page != -1 && isset($_GET['userid'])) {
			$query .= " AND userid='{$_POST['userid']}'";
		}
		$result = mysql_query($query) or die("Query failed : $query: " . mysql_error());
		$cnt = 0;
		while($line=mysql_fetch_assoc($result)) {
			if ((!$onepergroup && isset($allscores[$line['id']])) || ($onepergroup && isset($grpscores[$line['agroupid']]))) {//if (isset($locs[$line['id']])) {
				$scores = explode(",",$line['bestscores']);
				if ($onepergroup) {
					if ($line['agroupid']==0) { continue;}
					foreach ($grpscores[$line['agroupid']] as $loc=>$sv) {
						if (is_array($sv)) {
							$scores[$loc] = implode('~',$sv);
						} else {
							$scores[$loc] = $sv;
						}
					}
					$feedback = $grpfeedback[$line['agroupid']];
				} else {
					foreach ($allscores[$line['id']] as $loc=>$sv) {
						if (is_array($sv)) {
							$scores[$loc] = implode('~',$sv);
						} else {
							$scores[$loc] = $sv;
						}
					}
					$feedback = $_POST['feedback-'.$line['id']];
				}
				$scorelist = implode(",",$scores);
				
				$query = "UPDATE imas_assessment_sessions SET bestscores='$scorelist',feedback='$feedback' WHERE id='{$line['id']}'";
				mysql_query($query) or die("Query failed : $query " . mysql_error());
				
				if (strlen($line['lti_sourcedid'])>1) {
					//update LTI score
					require_once("../includes/ltioutcomes.php");
					calcandupdateLTIgrade($line['lti_sourcedid'],$aid,$scores);
				}
			}
		}
		if ($page == -1) {
			header('Location: ' . $urlmode  . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/gb-itemanalysis.php?stu=$stu&cid=$cid&aid=$aid&asid=average");
		} else {
			$page++;
			header('Location: ' . $urlmode  . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/gradeallq.php?stu=$stu&cid=$cid&aid=$aid&qid=$qid&page=$page");
			
		}
		exit;
	}
	
	
	require("../assessment/displayq2.php");
	list ($qsetid,$cat) = getqsetid($qid);
	
	$query = "SELECT name,defpoints,isgroup,groupsetid,deffeedbacktext FROM imas_assessments WHERE id='$aid'";
	$result = mysql_query($query) or die("Query failed : $query: " . mysql_error());
	list($aname,$defpoints,$isgroup,$groupsetid,$deffbtext) = mysql_fetch_row($result);
	
	if ($isgroup>0) {
		$groupnames = array();
		$query = "SELECT id,name FROM imas_stugroups WHERE groupsetid=$groupsetid";
		$result = mysql_query($query) or die("Query failed : $query: " . mysql_error());
		while ($row = mysql_fetch_row($result)) {
			$groupnames[$row[0]] = $row[1];
		}
		$grplist = implode(',',array_keys($groupnames));
		$groupmembers = array();
		$query = "SELECT isg.stugroupid,iu.LastName,iu.FirstName FROM imas_stugroupmembers AS isg JOIN imas_users as iu ON isg.userid=iu.id WHERE isg.stugroupid IN ($grplist) ORDER BY iu.LastName,iu.FirstName";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		while ($row = mysql_fetch_row($result)) {
			if (!isset($groupmembers[$row[0]])) {  $groupmembers[$row[0]] = array();}
			$groupmembers[$row[0]][] = $row[2].' '.$row[1];
		}
			
	}
	
	$query = "SELECT imas_questions.points,imas_questionset.control,imas_questions.rubric,imas_questionset.qtype FROM imas_questions,imas_questionset ";
	$query .= "WHERE imas_questions.questionsetid=imas_questionset.id AND imas_questions.id='$qid'";
	$result = mysql_query($query) or die("Query failed : $query: " . mysql_error());
	$points = mysql_result($result,0,0);
	$qcontrol = mysql_result($result,0,1);
	$rubric = mysql_result($result,0,2);
	$qtype = mysql_result($result,0,3);
	if ($points==9999) {
		$points = $defpoints;
	}
	
	$useeditor='review';
	$placeinhead = '<script type="text/javascript" src="'.$imasroot.'/javascript/rubric.js?v=120311"></script>';
	$placeinhead .= "<script type=\"text/javascript\">";
	$placeinhead .= 'function jumptostu() { ';
	$placeinhead .= '       var stun = document.getElementById("stusel").value; ';
	$address = $urlmode . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/gradeallq.php?stu=$stu&cid=$cid&gbmode=$gbmode&aid=$aid&qid=$qid&ver=$ver";
	$placeinhead .= "       var toopen = '$address&page=' + stun;\n";
	$placeinhead .= "  	window.location = toopen; \n";
	$placeinhead .= "}\n";
	$placeinhead .= '</script>';
	require("../includes/rubric.php");
	$sessiondata['coursetheme'] = $coursetheme;
	require("../assessment/header.php");
	echo "<style type=\"text/css\">p.tips {	display: none;}\n .hideongradeall { display: none;}</style>\n";
	echo "<div class=breadcrumb>$breadcrumbbase <a href=\"course.php?cid={$_GET['cid']}\">$coursename</a> ";
	echo "&gt; <a href=\"gradebook.php?stu=0&cid=$cid\">Gradebook</a> ";
	echo "&gt; <a href=\"gb-itemanalysis.php?stu=$stu&cid=$cid&aid=$aid\">Item Analysis</a> ";
	echo "&gt; Grading a Question</div>";
	echo "<div id=\"headergradeallq\" class=\"pagetitle\"><h2>Grading a Question in $aname</h2></div>";
	echo "<p><b>Warning</b>: This page may not work correctly if the question selected is part of a group of questions</p>";
	if ($page==-1) {
		echo "<p><a href=\"gradeallq.php?stu=$stu&gbmode=$gbmode&cid=$cid&aid=$aid&qid=$qid&page=0\">Grade one student at a time</a> (Do not use for group assignments)</p>";
	} else {
		echo "<p><a href=\"gradeallq.php?stu=$stu&gbmode=$gbmode&cid=$cid&aid=$aid&qid=$qid&page=-1\">Grade all students at once</a></p>";
	}
	echo "<p>Note: Feedback is for whole assessment, not the individual question.</p>";
?>
	<script type="text/javascript">
	function hidecorrect() {
	   var butn = document.getElementById("hctoggle");
	   if (butn.value=="Hide Perfect Score Questions") {
	      butn.value = "Show Perfect Score Questions";
	      var setdispto = "block";
	   } else { 
	      butn.value = "Hide Perfect Score Questions";
	      var setdispto = "none";
	   }
	   var divs = document.getElementsByTagName("div");
	   for (var i=0;i<divs.length;i++) {
	     if (divs[i].className=="iscorrect") { 
	         if (divs[i].style.display=="none") {
	               divs[i].style.display = "block";
	         } else { divs[i].style.display = "none"; }
	     }
	    }
	}
	function hidenonzero() {
	   var butn = document.getElementById("nztoggle");
	   if (butn.value=="Hide Nonzero Score Questions") {
	      butn.value = "Show Nonzero Score Questions";
	      var setdispto = "block";
	   } else { 
	      butn.value = "Hide Nonzero Score Questions";
	      var setdispto = "none";
	   }
	   var divs = document.getElementsByTagName("div");
	   for (var i=0;i<divs.length;i++) {
	     if (divs[i].className=="isnonzero") { 
	         if (divs[i].style.display=="none") {
	               divs[i].style.display = "block";
	         } else { divs[i].style.display = "none"; }
	     }
	    }
	}
	function hideNA() {
	   var butn = document.getElementById("hnatoggle");
	   if (butn.value=="Hide Not Answered Questions") {
	      butn.value = "Show Not Answered Questions";
	      var setdispto = "block";
	   } else { 
	      butn.value = "Hide Not Answered Questions";
	      var setdispto = "none";
	   }
	   var divs = document.getElementsByTagName("div");
	   for (var i=0;i<divs.length;i++) {
	     if (divs[i].className=="notanswered") { 
	         if (divs[i].style.display=="none") {
	               divs[i].style.display = "block";
	         } else { divs[i].style.display = "none"; }
	     }
	    }
	}
	function preprint() {
		var els = document.getElementsByTagName("input");
		for (var i=0; i<els.length; i++) {
			if (els[i].type=='button' && els[i].value=='Preview') {
				els[i].click();
			} else if (els[i].type=='button' && els[i].value=='Show Answer') {
				els[i].click();
				els[i].parentNode.insertBefore(document.createTextNode('Answer: '),els[i]);
				els[i].style.display = 'none';
			}
		}
		document.getElementById("preprint").style.display = "none";
	}
	function hidegroupdup(el) {  //el.checked = one per group
	   var divs = document.getElementsByTagName("div");
	   for (var i=0;i<divs.length;i++) {
	     if (divs[i].className=="groupdup") { 
	         if (el.checked) {
	               divs[i].style.display = "none";
	         } else { divs[i].style.display = "block"; }
	     }
	    }	
	    var hfours = document.getElementsByTagName("h4");
	   for (var i=0;i<hfours.length;i++) {
	     if (hfours[i].className=="person") { 
	     	hfours[i].style.display = el.checked?"none":"";
	     } else if (hfours[i].className=="group") { 
	     	hfours[i].style.display = el.checked?"":"none";
	     }
	    }
	    var spans = document.getElementsByTagName("span");
	   for (var i=0;i<spans.length;i++) {
	     if (spans[i].className=="person") { 
	     	spans[i].style.display = el.checked?"none":"";
	     } else if (spans[i].className=="group") { 
	     	spans[i].style.display = el.checked?"":"none";
	     }
	    }
	}
	function clearfeedback() {
		var els=document.getElementsByTagName("textarea");
		for (var i=0;i<els.length;i++) {
			if (els[i].id.match(/feedback/)) {
				els[i].value = '';
			}
		}
	}
	function cleardeffeedback() {
		var els=document.getElementsByTagName("textarea");
		for (var i=0;i<els.length;i++) {
			if (els[i].value=='<?php echo str_replace("'","\\'",$deffbtext); ?>') {
				els[i].value = '';
			}
		}
	}
	</script>
<?php
	$query = "SELECT imas_rubrics.id,imas_rubrics.rubrictype,imas_rubrics.rubric FROM imas_rubrics JOIN imas_questions ";
	$query .= "ON imas_rubrics.id=imas_questions.rubric WHERE imas_questions.id='$qid'";
	$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
	if (mysql_num_rows($result)>0) {
		echo printrubrics(array(mysql_fetch_row($result)));
	}
	if ($page==-1) {
		echo '<input type=button id="hctoggle" value="Hide Perfect Score Questions" onclick="hidecorrect()" />';
		echo '<input type=button id="nztoggle" value="Hide Nonzero Score Questions" onclick="hidenonzero()" />';
		echo ' <input type=button id="hnatoggle" value="Hide Not Answered Questions" onclick="hideNA()" />';
		echo ' <input type="button" id="preprint" value="Prepare for Printing (Slow)" onclick="preprint()" />';
	}
	echo ' <input type="button" id="clrfeedback" value="Clear all feedback" onclick="clearfeedback()" />';
	if ($deffbtext != '') {
		echo ' <input type="button" id="clrfeedback" value="Clear default feedback" onclick="cleardeffeedback()" />';
	}
	echo "<form id=\"mainform\" method=post action=\"gradeallq.php?stu=$stu&gbmode=$gbmode&cid=$cid&aid=$aid&qid=$qid&page=$page&update=true\">\n";
	if ($isgroup>0) {
		echo '<p><input type="checkbox" name="onepergroup" value="1" onclick="hidegroupdup(this)" /> Grade one per group</p>';
	}
	
	echo "<p>";
	if ($ver=='graded') {
		echo "Showing Graded Attempts.  ";
		echo "<a href=\"gradeallq.php?stu=$stu&gbmode=$gbmode&cid=$cid&aid=$aid&qid=$qid&ver=last\">Show Last Attempts</a>";
	} else if ($ver=='last') {
		echo "<a href=\"gradeallq.php?stu=$stu&gbmode=$gbmode&cid=$cid&aid=$aid&qid=$qid&ver=graded\">Show Graded Attempts</a>.  ";
		echo "Showing Last Attempts.  ";
		echo "<br/><b>Note:</b> Grades and number of attempt used are for the Graded Attempt.  Part points might be inaccurate.";
	}
	echo "</p>";
	
	if ($page!=-1) {
		$stulist = array();
		$query = "SELECT imas_users.LastName,imas_users.FirstName,imas_assessment_sessions.* FROM imas_users,imas_assessment_sessions,imas_students ";
		$query .= "WHERE imas_assessment_sessions.userid=imas_users.id AND imas_students.userid=imas_users.id AND imas_students.courseid='$cid' AND imas_assessment_sessions.assessmentid='$aid' ";
		$query .= "ORDER BY imas_users.LastName,imas_users.FirstName";
		$result = mysql_query($query) or die("Query failed : $query: " . mysql_error());
		while ($row = mysql_fetch_row($result)) {
			$stulist[] = $row[0].', '.$row[1];
		}
	}
	
	$query = "SELECT imas_users.LastName,imas_users.FirstName,imas_assessment_sessions.* FROM imas_users,imas_assessment_sessions,imas_students ";
	$query .= "WHERE imas_assessment_sessions.userid=imas_users.id AND imas_students.userid=imas_users.id AND imas_students.courseid='$cid' AND imas_assessment_sessions.assessmentid='$aid' ";
	$query .= "ORDER BY imas_users.LastName,imas_users.FirstName";
	if ($page != -1) {
		$query .= " LIMIT $page,1";
	}
	$result = mysql_query($query) or die("Query failed : $query: " . mysql_error());
	$cnt = 0;
	$onepergroup = array();
	require_once("../includes/filehandler.php");
	if (mysql_num_rows($result)>0) {
		
	while($line=mysql_fetch_assoc($result)) {
		if ($page != -1) {
			echo '<input type="hidden" name="userid" value="'.$line['userid'].'"/>';
		}
		$asid = $line['id'];
		$groupdup = false;
		if ($line['agroupid']>0) {
			$s3asid = 'grp'.$line['agroupid'].'/'.$aid;
			if (isset($onepergroup[$line['agroupid']])) {
				$groupdup = true;
			} else {
				echo "<input type=\"hidden\" name=\"groupasid[{$line['agroupid']}]\" value=\"{$line['id']}\" />";
				$onepergroup[$line['agroupid']] = $line['id'];
			}
		} else {
			if ($isgroup) {
				$groupdup = true;
			}
			$s3asid = $asid;
		}
		$questions = explode(',',$line['questions']);
		$scores = explode(",",$line['bestscores']);
		$attempts = explode(",",$line['bestattempts']);
		if ($ver=='graded') {
			$seeds = explode(",",$line['bestseeds']);
			$la = explode("~",$line['bestlastanswers']);
		} else if ($ver=='last') {
			$seeds = explode(",",$line['seeds']);
			$la = explode("~",$line['lastanswers']);
		}
		//$loc = array_search($qid,$questions);
		$lockeys = array_keys($questions,$qid);
		foreach ($lockeys as $loc) {
			if ($groupdup) {
				echo '<div class="groupdup">';
			}
			echo "<p><span class=\"person\"><b>".$line['LastName'].', '.$line['FirstName'].'</b></span>';
			if ($page != -1) {
				echo '.  Jump to <select id="stusel" onchange="jumptostu()">';
				foreach ($stulist as $i=>$st) {
					echo '<option value="'.$i.'" ';
					if ($i==$page) {echo 'selected="selected"';}
					echo '>'.$st.'</option>';
				}
				echo '</select>';
			}
			echo '</p>';
			if (!$groupdup) {
				echo '<h4 class="group" style="display:none">'.$groupnames[$line['agroupid']];
				if (isset($groupmembers[$line['agroupid']]) && count($groupmembers[$line['agroupid']])>0) {
					echo ' ('.implode(', ',$groupmembers[$line['agroupid']]).')</h4>';
				} else {
					echo ' (empty)</h4>';
				}
			}
			echo "<div ";
			if (getpts($scores[$loc])==$points) {
				echo 'class="iscorrect"';	
			} else if ($scores[$loc]>0) {
				echo 'class="isnonzero"';
			} else if ($scores[$loc]==-1) {
				echo 'class="notanswered"';
			} else {
				echo 'class="iswrong"';
			}
			echo '>';
			$lastanswers[$cnt] = $la[$loc];
			$teacherreview = $line['userid'];
			
			if ($qtype=='multipart') {
				if (($p = strpos($qcontrol,'answeights'))!==false) {
					$p = strpos($qcontrol,"\n",$p);
					$answeights = getansweights($loc,substr($qcontrol,0,$p));
				} else {
					preg_match('/anstypes(.*)/',$qcontrol,$match);
					$n = substr_count($match[1],',')+1;
					if ($n>1) {
						$answeights = array_fill(0,$n-1,round(1/$n,3));
						$answeights[] = 1-array_sum($answeights);
					} else {
						$answeights = array(1);
					}
				}
				for ($i=0; $i<count($answeights)-1; $i++) {
					$answeights[$i] = round($answeights[$i]*$points,2);
				}
				//adjust for rounding
				$diff = $points - array_sum($answeights);
				$answeights[count($answeights)-1] += $diff;
			}
			
			if ($qtype=='multipart') {
				$GLOBALS['questionscoreref'] = array("ud-{$line['id']}-$loc",$answeights);
			} else {
				$GLOBALS['questionscoreref'] = array("ud-{$line['id']}-$loc",$points);
			}
			$qtypes = displayq($cnt,$qsetid,$seeds[$loc],true,false,$attempts[$loc]);
			echo '</div>';
			
			echo "<div class=review>";
			echo '<span class="person">'.$line['LastName'].', '.$line['FirstName'].': </span>';
			if (!$groupdup) {
				echo '<span class="group" style="display:none">'.$groupnames[$line['agroupid']].': </span>';
			}
			if ($isgroup) {
				
			}
			list($pt,$parts) = printscore($scores[$loc]);
			
			if ($parts=='') { 
				if ($pt==-1) {
					$pt = 'N/A';
				}
				echo "<input type=text size=4 id=\"ud-{$line['id']}-$loc\" name=\"ud-{$line['id']}-$loc\" value=\"$pt\">";
				if ($rubric != 0) {
					echo printrubriclink($rubric,$points,"ud-{$line['id']}-$loc","feedback-{$line['id']}",($loc+1));
				}
			} 
			if ($parts!='') {
				echo " Parts: ";
				$prts = explode(', ',$parts);
				for ($j=0;$j<count($prts);$j++) {
					if ($prts[$j]==-1) {
						$prts[$j] = 'N/A';
					}
					echo "<input type=text size=2 id=\"ud-{$line['id']}-$loc-$j\" name=\"ud-{$line['id']}-$loc-$j\" value=\"{$prts[$j]}\">";
					if ($rubric != 0) {
						echo printrubriclink($rubric,$answeights[$j],"ud-{$line['id']}-$loc-$j","feedback-{$line['id']}",($loc+1).' pt '.($j+1));
					}
					echo ' ';
				}
				
			}
			echo " out of $points ";
			
			if ($parts!='') {
				$answeights = implode(', ',$answeights);
				echo "(parts: $answeights) ";
			}
			echo "in {$attempts[$loc]} attempt(s)\n";
			if ($parts!='') {
				$togr = array();
				foreach ($qtypes as $k=>$t) {
					if ($t=='essay' || $t=='file') {
						$togr[] = $k;
					}
				}
				echo '<br/>Quick grade: <a href="#" onclick="quickgrade('.$loc.',0,\'ud-'.$line['id'].'-\','.count($prts).',['.$answeights.']);return false;">Full credit all parts</a>';
				if (count($togr)>0) {
					$togr = implode(',',$togr);
					echo ' | <a href="#" onclick="quickgrade('.$loc.',1,\'ud-'.$line['id'].'-\',['.$togr.'],['.$answeights.']);return false;">Full credit all manually-graded parts</a>';
				}
			} else {
				echo '<br/>Quick grade: <a href="#" onclick="quicksetscore(\'ud-'.$line['id'].'-'.$loc.'\','.$points.');return false;">Full credit</a>';
			}
			$laarr = explode('##',$la[$loc]);
			if (count($laarr)>1) {
				echo "<br/>Previous Attempts:";
				$cntb =1;
				for ($k=0;$k<count($laarr)-1;$k++) {
					if ($laarr[$k]=="ReGen") {
						echo ' ReGen ';
					} else {
						echo "  <b>$cntb:</b> " ;
						if (preg_match('/@FILE:(.+?)@/',$laarr[$k],$match)) {
							$url = getasidfileurl($match[1]);
							echo "<a href=\"$url\" target=\"_new\">".basename($match[1])."</a>";
						} else {
							if (strpos($laarr[$k],'$!$')) {
								if (strpos($laarr[$k],'&')) { //is multipart q
									$laparr = explode('&',$laarr[$k]);
									foreach ($laparr as $lk=>$v) {
										if (strpos($v,'$!$')) {
											$tmp = explode('$!$',$v);
											$laparr[$lk] = $tmp[0];
										}
									}
									$laarr[$k] = implode('&',$laparr);
								} else {
									$tmp = explode('$!$',$laarr[$k]);
									$laarr[$k] = $tmp[0];
								}
							}
							if (strpos($laarr[$k],'$#$')) {
								if (strpos($laarr[$k],'&')) { //is multipart q
									$laparr = explode('&',$laarr[$k]);
									foreach ($laparr as $lk=>$v) {
										if (strpos($v,'$#$')) {
											$tmp = explode('$#$',$v);
											$laparr[$lk] = $tmp[0];
										}
									}
									$laarr[$k] = implode('&',$laparr);
								} else {
									$tmp = explode('$#$',$laarr[$k]);
									$laarr[$k] = $tmp[0];
								}
							}
							echo str_replace(array('&','%nbsp;'),array('; ','&nbsp;'),strip_tags($laarr[$k]));
						}
						$cntb++;
					}
				}
			}
			
			//echo " <a target=\"_blank\" href=\"$imasroot/msgs/msglist.php?cid=$cid&add=new&quoteq=$i-$qsetid-{$seeds[$i]}&to={$_GET['uid']}\">Use in Msg</a>";
			//echo " &nbsp; <a href=\"gradebook.php?stu=$stu&gbmode=$gbmode&cid=$cid&asid={$line['id']}&clearq=$i\">Clear Score</a>";
			echo "<br/>Feedback: <textarea cols=50 rows=1 id=\"feedback-{$line['id']}\" name=\"feedback-{$line['id']}\">{$line['feedback']}</textarea>";
			echo ' Question #'.($loc+1);
			echo "</div>\n";
			if ($groupdup) {
				echo '</div>';
			}
			$cnt++;
		}
	}
	echo "<input type=submit value=\"Save Changes\"/>";
	}
	echo "</form>";

	

	echo "<p><a href=\"gb-itemanalysis.php?stu=$stu&cid=$cid&aid=$aid&asid=average\">Back to Gradebook Item Analysis</a></p>";

	require("../footer.php");
	function getpts($sc) {
		if (strpos($sc,'~')===false) {
			if ($sc>0) { 
				return $sc;
			} else {
				return 0;
			}
		} else {
			$sc = explode('~',$sc);
			$tot = 0;
			foreach ($sc as $s) {
				if ($s>0) { 
					$tot+=$s;
				}
			}
			return round($tot,1);
		}
	}
	function printscore($sc) {
		if (strpos($sc,'~')===false) {

			return array($sc,'');
		} else {
			$pts = getpts($sc);
			$sc = str_replace('-1','N/A',$sc);
			$sc = str_replace('~',', ',$sc);
			return array($pts,$sc);
		}		
	}
function getansweights($qi,$code) {
	global $seeds,$questions;	
	$i = array_search($qi,$questions);
	return sandboxgetweights($code,$seeds[$i]);
}

function sandboxgetweights($code,$seed) {
	srand($seed);
	eval(interpret('control','multipart',$code));
	if (!isset($answeights)) {
		if (!is_array($anstypes)) {
			$anstypes = explode(",",$anstypes);
		}
		$n = count($anstypes);
		if ($n>1) {
			$answeights = array_fill(0,$n-1,round(1/$n,3));
			$answeights[] = 1-array_sum($weights);
		} else {
			$answeights = array(1);
		}
	} else if (!is_array($answeights)) {
		$answeights =  explode(',',$answeights);
	}
	$sum = array_sum($answeights);
	if ($sum==0) {$sum = 1;}
	foreach ($answeights as $k=>$v) {
		$answeights[$k] = $v/$sum;
	}
	return $answeights;
}
?>


