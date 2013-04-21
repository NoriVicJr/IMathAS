<?php
//IMathAS:  Main gradebook views (instructor & student)
//(c) 2007 David Lippman
// DONE:
//   Instructor Main view
//   Student Main view
//   Export/Email GB (gb-export.php)
//   Item Analysis (gb-itemanalysis.php)
//   New asid
//   View/Edit Assessment
//   Question/Category breakdown
//   email, message, unenroll, etc
//   stu view links
//   exceptions

// TODO:




require("../validate.php");
$cid = $_GET['cid'];
if (isset($teacherid)) {
	$isteacher = true;
} 
if (isset($tutorid)) {
	$istutor = true;
}
if ($isteacher || $istutor) {
	$canviewall = true;
} else {
	$canviewall = false;
}

if ($canviewall) {
	if (isset($_GET['gbmode']) && $_GET['gbmode']!='') {
		$gbmode = $_GET['gbmode'];
		$sessiondata[$cid.'gbmode'] = $gbmode;
		writesessiondata();
	} else if (isset($sessiondata[$cid.'gbmode'])) {
		$gbmode =  $sessiondata[$cid.'gbmode'];
	} else {
		$query = "SELECT defgbmode FROM imas_gbscheme WHERE courseid='$cid'";
		$result = mysqli_query($GLOBALS['link'],$query) or die("Query failed : " . mysqli_error($GLOBALS['link']));
		$gbmode = mysql_fetch_first($result);
		
		
	}
	if (isset($_COOKIE["colorize-$cid"])) {
		$colorize = $_COOKIE["colorize-$cid"];
	} else {
		$query = "SELECT colorize FROM imas_gbscheme WHERE courseid='$cid'";
		$result = mysqli_query($GLOBALS['link'],$query) or die("Query failed : " . mysqli_error($GLOBALS['link']));
		$colorize = mysql_fetch_first($result);
		setcookie("colorize-$cid",$colorize);
	}
	if (isset($_GET['catfilter'])) {
		$catfilter = $_GET['catfilter'];
		$sessiondata[$cid.'catfilter'] = $catfilter;
		writesessiondata();
	} else if (isset($sessiondata[$cid.'catfilter'])) {
		$catfilter = $sessiondata[$cid.'catfilter'];
	} else {
		$catfilter = -1;
	}
	if (isset($tutorsection) && $tutorsection!='') {
		$secfilter = $tutorsection;
	} else {
		if (isset($_GET['secfilter'])) {
			$secfilter = $_GET['secfilter'];
			$sessiondata[$cid.'secfilter'] = $secfilter;
			writesessiondata();
		} else if (isset($sessiondata[$cid.'secfilter'])) {
			$secfilter = $sessiondata[$cid.'secfilter'];
		} else {
			$secfilter = -1;
		}
	}
	//Gbmode : Links NC Dates
	$showpics = floor($gbmode/10000)%10 ; //0 none, 1 small, 2 big
	$totonleft = ((floor($gbmode/1000)%10)&1) ; //0 right, 1 left
	$avgontop = ((floor($gbmode/1000)%10)&2) ; //0 bottom, 2 top
	$links = ((floor($gbmode/100)%10)&1); //0: view/edit, 1 q breakdown
	$hidelocked = ((floor($gbmode/100)%10&2)); //0: show locked, 1: hide locked
	$hidenc = floor($gbmode/10)%10; //0: show all, 1 stu visisble (cntingb not 0), 2 hide all (cntingb 1 or 2)
	$availshow = $gbmode%10; //0: past, 1 past&cur, 2 all, 3 past and attempted, 4=current only

	
} else {
	$secfilter = -1;
	$catfilter = -1;
	$links = 0;
	$hidenc = 1;
	$availshow = 1;
	$showpics = 0;
	$totonleft = 0;
	$avgontop = 0;
	$hidelocked = 0;
}

if ($canviewall && isset($_GET['stu'])) {
	$stu = $_GET['stu'];
} else {
	$stu = 0;
}

//HANDLE ANY POSTS
if ($isteacher) {
	if (isset($_GET['togglenewflag'])) {
		//recording a toggle.  Called via AHAH
		$query = "SELECT newflag FROM imas_courses WHERE id='$cid'";
		$result = mysqli_query($GLOBALS['link'],$query) or die("Query failed : " . mysqli_error($GLOBALS['link']));
		$newflag = mysql_fetch_first($result);
		$newflag = $newflag ^ 1;  //XOR
		$query = "UPDATE imas_courses SET newflag = $newflag WHERE id='$cid'";
		mysqli_query($GLOBALS['link'],$query) or die("Query failed : " . mysqli_error($GLOBALS['link']));
		if (($newflag&1)==1) {
			echo 'New';
		} 
		exit;
	}
	if ((isset($_POST['posted']) && ($_POST['posted']=="E-mail" || $_POST['posted']=="Message"))|| isset($_GET['masssend']))  {
		$calledfrom='gb';
		include("masssend.php");
	}
	if ((isset($_POST['posted']) && $_POST['posted']=="Make Exception") || isset($_GET['massexception'])) {
		$calledfrom='gb';
		include("massexception.php");
	}
	if ((isset($_POST['posted']) && $_POST['posted']=="Unenroll") || (isset($_GET['action']) && $_GET['action']=="unenroll" )) {
		$calledfrom='gb';
		$curBreadcrumb = "$breadcrumbbase <a href=\"course.php?cid={$_GET['cid']}\">$coursename</a> ";
		$curBreadcrumb .= "&gt; <a href=\"gradebook.php?cid=$cid\">Gradebook</a> &gt; Confirm Change";
		$pagetitle = "Unenroll Students";
		include("unenroll.php");
		include("../footer.php");
		exit;
	}
	if (isset($_POST['posted']) && $_POST['posted']=='Print Report') {
		//based on a contribution by Cam Joyce
		require_once("gbtable2.php");
		
		$placeinhead = '<style type="text/css" >@media print { .noPrint  { display:none; } }</style>';
		$placeinhead .= '<script type="text/javascript">addLoadEvent(print);</script>';
		$flexwidth = true;
		require("../header.php");
		
		echo '<div class="noPrint"><a href="#" onclick="window.print(); return false;">Print Reports</a> ';
		echo '<a href="gradebook.php?'.$_SERVER['QUERY_STRING'].'">Back to Gradebook</a></div>';
		if( isset($_POST['checked']) ) {
			echo "<div id=\"tbl-container\">";
			echo '<div id="bigcontmyTable"><div id="tblcontmyTable">';
			$value = $_POST['checked'];
			$last = count($value)-1;
			for($i = 0; $i < $last; $i++){
				gbstudisp($value[$i]);
				echo "<div style=\"page-break-after:always\"></div>";
			}
			gbstudisp($value[$last]);//no page break after last report
	
			echo "</div></div></div>";
		}
		require("../footer.php");
		exit;
		
	}
	if (isset($_POST['usrcomments']) && $stu>0) {
			$query = "UPDATE imas_students SET gbcomment='{$_POST['usrcomments']}' WHERE userid='$stu'";
			mysqli_query($GLOBALS['link'],$query) or die("Query failed : " . mysqli_error($GLOBALS['link']));
			//echo "<p>Comment Updated</p>";
	}
	if (isset($_POST['score']) && $stu>0) {
		foreach ($_POST['score'] as $id=>$val) {
			if (trim($val)=='') {
				$query = "DELETE FROM imas_grades WHERE userid='$stu' AND gradetypeid='$id' AND gradetype='offline'";
			} else {
				$query = "UPDATE imas_grades SET score='$val',feedback='{$_POST['feedback'][$id]}' WHERE userid='$stu' AND gradetypeid='$id' AND gradetype='offline'";
			}
			mysqli_query($GLOBALS['link'],$query) or die("Query failed : " . mysqli_error($GLOBALS['link']));
		}
	}
	if (isset($_POST['newscore']) && $stu>0) {
		$toins = array();
		foreach ($_POST['newscore'] as $id=>$val) {
			if (trim($val)=="") {continue;}
			$toins[] = "('$id','offline','$stu','$val','{$_POST['feedback'][$id]}')";
			mysqli_query($GLOBALS['link'],$query) or die("Query failed : " . mysqli_error($GLOBALS['link']));
		}
		if (count($toins)>0) {
			$query = "INSERT INTO imas_grades (gradetypeid,gradetype,userid,score,feedback) VALUES ".implode(',',$toins);
			mysqli_query($GLOBALS['link'],$query) or die("Query failed : " . mysqli_error($GLOBALS['link']));
		}
	}
	if (isset($_POST['usrcomments']) || isset($_POST['score']) || isset($_POST['newscore'])) {
		header('Location: ' . $urlmode  . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/gradebook.php?{$_SERVER['QUERY_STRING']}");
		exit;
	}
}



//DISPLAY
require_once("gbtable2.php");
require("../includes/htmlutil.php");

$placeinhead = '';
if ($canviewall) {
	$placeinhead .= "<script type=\"text/javascript\">";
	$placeinhead .= 'function chgfilter() { ';
	$placeinhead .= '       var cat = document.getElementById("filtersel").value; ';
	$address = $urlmode . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/gradebook.php?stu=$stu&cid=$cid";
	
	$placeinhead .= "       var toopen = '$address&catfilter=' + cat;\n";
	$placeinhead .= "  	window.location = toopen; \n";
	$placeinhead .= "}\n";
	if ($isteacher) { 
		$placeinhead .= 'function chgsecfilter() { ';
		$placeinhead .= '       var sec = document.getElementById("secfiltersel").value; ';
		$address = $urlmode . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/gradebook.php?stu=$stu&cid=$cid";
		
		$placeinhead .= "       var toopen = '$address&secfilter=' + sec;\n";
		$placeinhead .= "  	window.location = toopen; \n";
		$placeinhead .= "}\n";
		$placeinhead .= 'function chgnewflag() { ';
		$address = $urlmode . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/gradebook.php?stu=$stu&cid=$cid&togglenewflag=true";
		
		$placeinhead .= "       basicahah('$address','newflag','Recording...');\n";
		$placeinhead .= "}\n";
	}
	$placeinhead .= 'function chgtoggle() { ';
	$placeinhead .= "	var altgbmode = 10000*document.getElementById(\"toggle4\").value + 1000*($totonleft+$avgontop) + 100*(document.getElementById(\"toggle1\").value*1+ document.getElementById(\"toggle5\").value*1) + 10*document.getElementById(\"toggle2\").value + 1*document.getElementById(\"toggle3\").value; ";
	$address = $urlmode . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/gradebook.php?stu=$stu&cid=$cid&gbmode=";
	$placeinhead .= "	var toopen = '$address' + altgbmode;\n";
	$placeinhead .= "  	window.location = toopen; \n";
	$placeinhead .= "}\n";
	if ($isteacher) {
		$placeinhead .= 'function chgexport() { ';
		$placeinhead .= "	var type = document.getElementById(\"exportsel\").value; ";
		$address = $urlmode . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/gb-export.php?stu=$stu&cid=$cid&";
		$placeinhead .= "	var toopen = '$address';";
		$placeinhead .= "	if (type==1) { toopen = toopen+'export=true';}\n";
		$placeinhead .= "	if (type==2) { toopen = toopen+'emailgb=me';}\n";
		$placeinhead .= "	if (type==3) { toopen = toopen+'emailgb=ask';}\n";
		$placeinhead .= "	if (type==0) { return false;}\n";
		$placeinhead .= "  	window.location = toopen; \n";
		$placeinhead .= "}\n";
		$placeinhead .= 'function makeofflineeditable(el) {
					var anchors = document.getElementsByTagName("a");
					for (var i=0;i<anchors.length;i++) {
						if (bits=anchors[i].href.match(/addgrades.*gbitem=(\d+)/)) {
							if (anchors[i].innerHTML.match("-")) {
							    type = "newscore";
							} else {
							    type = "score";
							}
							anchors[i].style.display = "none";
							var newinp = document.createElement("input");
							newinp.size = 4;
							if (type=="newscore") {
							    newinp.name = "newscore["+bits[1]+"]";
							} else {
							    newinp.name = "score["+bits[1]+"]";
							    newinp.value = anchors[i].innerHTML;
							}
							anchors[i].parentNode.appendChild(newinp);
							var newtxta = document.createElement("textarea");
							newtxta.name = "feedback["+bits[1]+"]";
							newtxta.cols = 50;
							var feedbtd = anchors[i].parentNode.nextSibling.nextSibling.nextSibling;
							newtxta.value = feedbtd.innerHTML;
							feedbtd.innerHTML = "";
							feedbtd.appendChild(newtxta);
						}					
					}
					document.getElementById("savechgbtn").style.display = "";
					el.onclick = null;
				}';
	}
	
	
	$placeinhead .= "</script>";
	$placeinhead .= '<script type="text/javascript">function conditionalColor(table,type,low,high) {
	var tbl = document.getElementById(table);
	if (type==0) {  //instr gb view
		var poss = [];
		var startat = 2;
		var ths = tbl.getElementsByTagName("thead")[0].getElementsByTagName("th");
		for (var i=0;i<ths.length;i++) {
			if (k = ths[i].innerHTML.match(/(\d+)(&nbsp;|\u00a0)pts/)) {
				poss[i] = k[1]*1;
				if (poss[i]==0) {poss[i]=.0000001;}
			} else {
				poss[i] = 100;
				if(ths[i].innerHTML.match(/Section/)) {
					startat++;
				}
				if(ths[i].innerHTML.match(/Code/)) {
					startat++;
				}
			}
		}
		var trs = tbl.getElementsByTagName("tbody")[0].getElementsByTagName("tr");
		for (var j=0;j<trs.length;j++) {
			var tds = trs[j].getElementsByTagName("td");
			for (var i=startat;i<tds.length;i++) {
				if (low==-1) {
					if (tds[i].className.match("isact")) {
						tds[i].style.backgroundColor = "#99ff99";
					} else {
						tds[i].style.backgroundColor = "#ffffff";
					}
				} else {
					if (tds[i].innerText) {
						var v = tds[i].innerText;
					} else {
						var v = tds[i].textContent;
					}
					v = v.replace(/\(.*?\)/g,"");
					if (k = v.match(/([\d\.]+)\/(\d+)/)) {
						if (k[2]==0) { var perc = 0;} else { var perc= k[1]/k[2];}
					} else {
						v = v.replace(/[^\d\.]/g,"");
						var perc = v/poss[i];
					}
					
					if (perc<low/100) {
						tds[i].style.backgroundColor = "#ff9999";
						
					} else if (perc>high/100) {
						tds[i].style.backgroundColor = "#99ff99";
					} else {
						tds[i].style.backgroundColor = "#ffffff";
					}
				}
			}
		}
	} else {
		var trs = tbl.getElementsByTagName("tbody")[0].getElementsByTagName("tr");
		for (var j=0;j<trs.length;j++) {
			var tds = trs[j].getElementsByTagName("td");
			if (tds[1].innerText) {
				var poss = tds[1].innerText.replace(/[^\d\.]/g,"");
				var v = tds[2].innerText.replace(/[^\d\.]/g,"");
			} else {
				var poss = tds[1].textContent.replace(/[^\d\.]/g,"");
				var v = tds[2].textContent.replace(/[^\d\.]/g,"");
			}
			if (v/poss<low/100) {
				tds[2].style.backgroundColor = "#ff6666";
				
			} else if (v/poss>high/100) {
				tds[2].style.backgroundColor = "#66ff66";
			} else {
				tds[2].style.backgroundColor = "#ffffff";
				
			}
			
		}
	}
}
function updateColors(el) {
	if (el.value==0) {
		var tds=document.getElementById("myTable").getElementsByTagName("td");
		for (var i=0;i<tds.length;i++) {
			tds[i].style.backgroundColor = "";
		}
	} else {
		var s = el.value.split(/:/);
		conditionalColor("myTable",0,s[0],s[1]);
	}
	document.cookie = "colorize-'.$cid.'="+el.value;
}
</script>';
}



		
			

if (isset($studentid) || $stu!=0) { //show student view
	if (isset($studentid)) {
		$stu = $userid;
	}
	$pagetitle = "Gradebook";
	$placeinhead .= "<script type=\"text/javascript\" src=\"$imasroot/javascript/tablesorter.js\"></script>\n";
	$placeinhead .= '<script type="text/javascript">
		function showhidefb(el,n) {
			el.style.display="none";
			document.getElementById("feedbackholder"+n).style.display = "inline";
			return false;
			}
		function showhideallfb(s) {
			s.style.display="none";
			var els = document.getElementsByTagName("a");
			for (var i=0;i<els.length;i++) {
				if (els[i].className.match("feedbacksh")) {
					els[i].style.display="none";
				}
			}
			var els = document.getElementsByTagName("span");
			for (var i=0;i<els.length;i++) {
				if (els[i].id.match("feedbackholder")) {
					els[i].style.display="inline";
				}
			}
			return false;
		}</script>';
	
	require("../header.php");
	
	if (isset($_GET['from']) && $_GET['from']=="listusers") {
		echo "<div class=breadcrumb>$breadcrumbbase <a href=\"course.php?cid={$_GET['cid']}\">$coursename</a> ";
		echo "&gt; <a href=\"listusers.php?cid=$cid\">List Students</a> &gt Student Grade Detail</div>\n";
	} else if ($isteacher || $istutor) {
		echo "<div class=breadcrumb>$breadcrumbbase <a href=\"course.php?cid={$_GET['cid']}\">$coursename</a> ";
		echo "&gt; <a href=\"gradebook.php?stu=0&cid=$cid\">Gradebook</a> &gt; Student Detail</div>";
	} else {
		echo "<div class=breadcrumb>$breadcrumbbase <a href=\"course.php?cid={$_GET['cid']}\">$coursename</a> ";
		echo "&gt; Gradebook</div>";
	}
	if ($stu==-1) {
		echo '<div id="headergradebook" class="pagetitle"><h2>Grade Book Averages </h2></div>';
	} else {
		echo '<div id="headergradebook" class="pagetitle"><h2>Grade Book Student Detail</h2></div>';
	}
	if ($canviewall) {
		echo "<div class=cpmid>";
		echo 'Category: <select id="filtersel" onchange="chgfilter()">';
		echo '<option value="-1" ';
		if ($catfilter==-1) {echo "selected=1";}
		echo '>All</option>';
		echo '<option value="0" ';
		if ($catfilter==0) { echo "selected=1";}
		echo '>Default</option>';
		$query = "SELECT id,name FROM imas_gbcats WHERE courseid='$cid' ORDER BY name";
		$result = mysqli_query($GLOBALS['link'],$query) or die("Query failed : " . mysqli_error($GLOBALS['link']));
		while ($row = mysqli_fetch_row($result)) {
			echo '<option value="'.$row[0].'"';
			if ($catfilter==$row[0]) {echo "selected=1";}
			echo '>'.$row[1].'</option>';
		}
		echo '<option value="-2" ';
		if ($catfilter==-2) {echo "selected=1";}
		echo '>Category Totals</option>';
		echo '</select> | ';
		echo "Not Counted: <select id=\"toggle2\" onchange=\"chgtoggle()\">";
		echo "<option value=0 "; writeHtmlSelected($hidenc,0); echo ">Show all</option>";
		echo "<option value=1 "; writeHtmlSelected($hidenc,1); echo ">Show stu view</option>";
		echo "<option value=2 "; writeHtmlSelected($hidenc,2); echo ">Hide all</option>";
		echo "</select>";
		echo " | Show: <select id=\"toggle3\" onchange=\"chgtoggle()\">";
		echo "<option value=0 "; writeHtmlSelected($availshow,0); echo ">Past due</option>";
		echo "<option value=3 "; writeHtmlSelected($availshow,3); echo ">Past &amp; Attempted</option>";
		echo "<option value=4 "; writeHtmlSelected($availshow,4); echo ">Available Only</option>";
		echo "<option value=1 "; writeHtmlSelected($availshow,1); echo ">Past &amp; Available</option>";
		echo "<option value=2 "; writeHtmlSelected($availshow,2); echo ">All</option></select>";
		echo " | Links: <select id=\"toggle1\" onchange=\"chgtoggle()\">";
		echo "<option value=0 "; writeHtmlSelected($links,0); echo ">View/Edit</option>";
		echo "<option value=1 "; writeHtmlSelected($links,1); echo ">Scores</option></select>";
		echo '<input type="hidden" id="toggle4" value="'.$showpics.'" />';
		echo '<input type="hidden" id="toggle5" value="'.$hidelocked.'" />';
		echo "</div>";
	}
	gbstudisp($stu);
	echo "<p>Meanings: IP-In Progress (some unattempted questions), OT-overtime, PT-practice test, EC-extra credit, NC-no credit<br/><sub>d</sub> Dropped score.  <sup>e</sup> Has exception <sup>LP</sup> Used latepass  </p>\n";
	
	require("../footer.php");
	
} else { //show instructor view
	$placeinhead .= "<script type=\"text/javascript\" src=\"$imasroot/javascript/tablesorter.js?v=012811\"></script>\n";
	$placeinhead .= "<script type=\"text/javascript\" src=\"$imasroot/javascript/tablescroller2.js?v=013112\"></script>\n";
	$placeinhead .= "<script type=\"text/javascript\">\n";
	$placeinhead .= 'var ts = new tablescroller("myTable",';
	if (isset($_COOKIE["gblhdr-$cid"]) && $_COOKIE["gblhdr-$cid"]==1) {
		$placeinhead .= 'true);';
		$headerslocked = true;
	} else {
		if (!isset($_COOKIE["gblhdr-$cid"]) && isset($CFG['GBS']['lockheader']) && $CFG['GBS']['lockheader']==true) {
			$placeinhead .= 'true);';
			$headerslocked = true;
		} else {
			$placeinhead .= 'false);';
			$headerslocked = false;
			$usefullwidth = true;
		}
	}
	$placeinhead .= "\nfunction lockcol() { \n";
	$placeinhead .= "var tog = ts.toggle(); ";
	$placeinhead .= "if (tog==1) { "; //going to locked
	$placeinhead .= "document.cookie = 'gblhdr-$cid=1';\n document.getElementById(\"lockbtn\").value = \"Unlock headers\"; ";
	$placeinhead .= "} else {";
	$placeinhead .= "document.cookie = 'gblhdr-$cid=0';\n document.getElementById(\"lockbtn\").value = \"Lock headers\"; ";
	//$placeinhead .= " var cont = document.getElementById(\"tbl-container\");\n";
	//$placeinhead .= " if (cont.style.overflow == \"auto\") {\n";
	//$placeinhead .= "   cont.style.height = \"auto\"; cont.style.overflow = \"visible\"; cont.style.border = \"0px\";";
	//$placeinhead .= "document.getElementById(\"myTable\").className = \"gb\"; document.cookie = 'gblhdr-$cid=0';";
	//$placeinhead .= "  document.getElementById(\"lockbtn\").value = \"Lock headers\"; } else {";
	//$placeinhead .= " cont.style.height = \"75%\"; cont.style.overflow = \"auto\"; cont.style.border = \"1px solid #000\";\n";
	//$placeinhead .= "document.getElementById(\"myTable\").className = \"gbl\"; document.cookie = 'gblhdr-$cid=1'; ";
	//$placeinhead .= "  document.getElementById(\"lockbtn\").value = \"Unlock headers\"; }";
	$placeinhead .= "}}\n ";
	$placeinhead .= "function cancellockcol() {document.cookie = 'gblhdr-$cid=0';\n document.getElementById(\"lockbtn\").value = \"Lock headers\";}\n"; 
	$placeinhead .= 'function highlightrow(el) { el.setAttribute("lastclass",el.className); el.className = "highlight";}';
	$placeinhead .= 'function unhighlightrow(el) { el.className = el.getAttribute("lastclass");}';
	$placeinhead .= "</script>\n";
	$placeinhead .= "<style type=\"text/css\"> table.gb { margin: 0px; } table.gb tr.highlight { border-bottom:1px solid #333;} table.gb tr {border-bottom:1px solid #fff; } td.trld {display:table-cell;vertical-align:middle;} </style>";
	
	require("../header.php");
	echo "<div class=breadcrumb>$breadcrumbbase <a href=\"course.php?cid={$_GET['cid']}\">$coursename</a> ";
	echo "&gt; Gradebook</div>";
	echo "<form id=\"qform\" method=post action=\"gradebook.php?cid=$cid\">";
	
	echo '<div id="headergradebook" class="pagetitle"><h2>Gradebook <span class="red" id="newflag" style="font-size: 70%" >';
	if (($coursenewflag&1)==1) {
		echo 'New';
	}
	echo '</span></h2></div>';
	if ($isdiag) {
		echo "<a href=\"gb-testing.php?cid=$cid\">View diagnostic gradebook</a>";
	}
	echo "<div class=cpmid>";
	if ($isteacher) {
		echo "Offline Grades: <a href=\"addgrades.php?cid=$cid&gbitem=new&grades=all\">Add</a>, ";
		echo "<a href=\"chgoffline.php?cid=$cid\">Manage</a> | ";
		echo '<select id="exportsel" onchange="chgexport()">';
		echo '<option value="0">Export to...</option>';
		echo '<option value="1">... file</option>';
		echo '<option value="2">... my email</option>';
		echo '<option value="3">... other email</option></select> | ';
		//echo "Export to <a href=\"gb-export.php?stu=$stu&cid=$cid&export=true\">File</a>, ";
		//echo "<a href=\"gb-export.php?stu=$stu&cid=$cid&emailgb=me\">My Email</a>, or <a href=\"gb-export.php?stu=$stu&cid=$cid&emailgb=ask\">Other Email</a> | ";
		echo "<a href=\"gbsettings.php?cid=$cid\">GB Settings</a> | ";
		echo "<a href=\"gradebook.php?cid=$cid&stu=-1\">Averages</a> | ";
		echo "<a href=\"gbcomments.php?cid=$cid&stu=0\">Comments</a> | ";
		echo "<input type=\"button\" id=\"lockbtn\" onclick=\"lockcol()\" value=\"";
		if ($headerslocked) {
			echo "Unlock headers";
		} else {
			echo "Lock headers";
		}
		echo "\"/>";
		echo ' | Color: <select id="colorsel" onchange="updateColors(this)">';
		echo '<option value="0">None</option>';
		for ($j=50;$j<90;$j+=($j<70?10:5)) {
			for ($k=$j+($j<70?10:5);$k<100;$k+=($k<70?10:5)) {
				echo "<option value=\"$j:$k\" ";
				if ("$j:$k"==$colorize) {
					echo 'selected="selected" ';
				}
				echo ">$j/$k</option>";
			}
		}
		echo '<option value="-1:-1" ';
		if ($colorize == "-1:-1") { echo 'selected="selected" ';}
		echo '>Active</option>';
		echo '</select>';
		echo ' | <a href="#" onclick="chgnewflag(); return false;">NewFlag</a>';
		//echo '<input type="button" value="Pics" onclick="rotatepics()" />';
		
		echo "<br/>\n";
		
	}
	
	echo 'Category: <select id="filtersel" onchange="chgfilter()">';
	echo '<option value="-1" ';
	if ($catfilter==-1) {echo "selected=1";}
	echo '>All</option>';
	echo '<option value="0" ';
	if ($catfilter==0) { echo "selected=1";}
	echo '>Default</option>';
	$query = "SELECT id,name FROM imas_gbcats WHERE courseid='$cid' ORDER BY name";
	$result = mysqli_query($GLOBALS['link'],$query) or die("Query failed : " . mysqli_error($GLOBALS['link']));
	while ($row = mysqli_fetch_row($result)) {
		echo '<option value="'.$row[0].'"';
		if ($catfilter==$row[0]) {echo "selected=1";}
		echo '>'.$row[1].'</option>';
	}
	echo '<option value="-2" ';
	if ($catfilter==-2) {echo "selected=1";}
	echo '>Category Totals</option>';
	echo '</select> | ';
	echo "Not Counted: <select id=\"toggle2\" onchange=\"chgtoggle()\">";
	echo "<option value=0 "; writeHtmlSelected($hidenc,0); echo ">Show all</option>";
	echo "<option value=1 "; writeHtmlSelected($hidenc,1); echo ">Show stu view</option>";
	echo "<option value=2 "; writeHtmlSelected($hidenc,2); echo ">Hide all</option>";
	echo "</select>";
	echo " | Show: <select id=\"toggle3\" onchange=\"chgtoggle()\">";
	echo "<option value=0 "; writeHtmlSelected($availshow,0); echo ">Past due</option>";
	echo "<option value=3 "; writeHtmlSelected($availshow,3); echo ">Past &amp; Attempted</option>";
	echo "<option value=4 "; writeHtmlSelected($availshow,4); echo ">Available Only</option>";
	echo "<option value=1 "; writeHtmlSelected($availshow,1); echo ">Past &amp; Available</option>";
	echo "<option value=2 "; writeHtmlSelected($availshow,2); echo ">All</option></select>";
	echo " | Links: <select id=\"toggle1\" onchange=\"chgtoggle()\">";
	echo "<option value=0 "; writeHtmlSelected($links,0); echo ">View/Edit</option>";
	echo "<option value=1 "; writeHtmlSelected($links,1); echo ">Scores</option></select>";
	echo " | Pics: <select id=\"toggle4\" onchange=\"chgtoggle()\">";
	echo "<option value=0 "; writeHtmlSelected($showpics,0); echo ">None</option>";
	echo "<option value=1 "; writeHtmlSelected($showpics,1); echo ">Small</option>";
	echo "<option value=2 "; writeHtmlSelected($showpics,2); echo ">Big</option></select>";
	if (!$isteacher) {
	
		echo " | <input type=\"button\" id=\"lockbtn\" onclick=\"lockcol()\" value=\"";
		if ($headerslocked) {
			echo "Unlock headers";
		} else {
			echo "Lock headers";
		}
		echo "\"/>\n";	
	}
	
	echo "</div>";
	
	if ($isteacher) {
		echo 'Check: <a href="#" onclick="return chkAllNone(\'qform\',\'checked[]\',true)">All</a> <a href="#" onclick="return chkAllNone(\'qform\',\'checked[]\',false)">None</a> ';
		echo 'With Selected:  <input type="submit" name="posted" value="Print Report"/> <input type="submit" name="posted" value="E-mail"/> <input type="submit" name="posted" value="Message"/> ';
		if (!isset($CFG['GEN']['noInstrUnenroll'])) {
			echo '<input type=submit name=posted value="Unenroll">';
		}
		echo "<input type=submit name=posted value=\"Make Exception\"> ";
	}
	
	$gbt = gbinstrdisp();
	echo "</form>";
	echo "</div>";
	echo "Meanings:  IP-In Progress (some unattempted questions), OT-overtime, PT-practice test, EC-extra credit, NC-no credit<br/><sup>*</sup> Has feedback, <sub>d</sub> Dropped score,  <sup>e</sup> Has exception <sup>LP</sup> Used latepass\n";
	require("../footer.php");
	
	/*if ($isteacher) {
		echo "<div class=cp>";
		echo "<a href=\"addgrades.php?cid=$cid&gbitem=new&grades=all\">Add Offline Grade</a><br/>";
		echo "<a href=\"gradebook.php?stu=$stu&cid=$cid&export=true\">Export Gradebook</a><br/>";
		echo "Email gradebook to <a href=\"gradebook.php?stu=$stu&cid=$cid&emailgb=me\">Me</a> or <a href=\"gradebook.php?stu=$stu&cid=$cid&emailgb=ask\">to another address</a><br/>";
		echo "<a href=\"gbsettings.php?cid=$cid\">Gradebook Settings</a>";
		echo "<div class=clear></div></div>";
	}
	*/
}

function gbstudisp($stu) {
	global $hidenc,$cid,$gbmode,$availshow,$isteacher,$istutor,$catfilter,$imasroot,$canviewall,$urlmode;
	if ($availshow==4) {
		$availshow=1;
		$hidepast = true;
	}
	$curdir = rtrim(dirname(__FILE__), '/\\');
	$gbt = gbtable($stu);
	
	if ($stu>0) {
		$query = "SELECT showlatepass FROM imas_courses WHERE id='$cid'";
		$result = mysqli_query($GLOBALS['link'],$query) or die("Query failed : " . mysqli_error($GLOBALS['link']));
		$showlatepass = mysql_fetch_first($result);
		
		if ($isteacher) {
			if ($gbt[1][4][2]==1) {
				if(isset($GLOBALS['CFG']['GEN']['AWSforcoursefiles']) && $GLOBALS['CFG']['GEN']['AWSforcoursefiles'] == true) {
					echo "<img src=\"{$urlmode}s3.amazonaws.com/{$GLOBALS['AWSbucket']}/cfiles/userimg_sm{$gbt[1][4][0]}.jpg\"/> <input type=\"checkbox\" name=\"removepic\" value=\"1\" /> Remove ";
				} else {
					echo "<img src=\"$imasroot/course/files/userimg_sm{$gbt[1][4][0]}.jpg\" style=\"float: left; padding-right:5px;\" onclick=\"togglepic(this)\"/>";
				}
			} 
		}
		echo '<h3>' . strip_tags($gbt[1][0][0]) . ' <span class="small">('.$gbt[1][0][1].')</span></h3>';
		$query = "SELECT imas_students.gbcomment,imas_users.email,imas_students.latepass FROM imas_students,imas_users WHERE ";
		$query .= "imas_students.userid=imas_users.id AND imas_users.id='$stu' AND imas_students.courseid='{$_GET['cid']}'";
		$result = mysqli_query($GLOBALS['link'],$query) or die("Query failed : " . mysqli_error($GLOBALS['link']));
		echo '<div style="clear:both">';
		if (mysqli_num_rows($result)>0) {
			list($gbcomment,$email,$latepasses) = mysqli_fetch_row($result); 
			if ($isteacher) {
				echo '<a href="mailto:'.$email.'">Email</a> | ';
				echo "<a href=\"$imasroot/msgs/msglist.php?cid={$_GET['cid']}&add=new&to=$stu\">Message</a> | ";
				echo "<a href=\"gradebook.php?cid={$_GET['cid']}&uid=$stu&massexception=1\">Make Exception</a> | ";
				echo "<a href=\"listusers.php?cid={$_GET['cid']}&chgstuinfo=true&uid=$stu\">Change Info</a> | ";
				echo "<a href=\"viewloginlog.php?cid={$_GET['cid']}&uid=$stu&from=gb\">Login Log</a> | ";
				echo "<a href=\"#\" onclick=\"makeofflineeditable(this); return false;\">Edit Offline Scores</a>";
				
			}
			
			if ($showlatepass==1) {
				if ($latepasses==0) { $latepasses = 'No';}
				if ($isteacher) {echo '<br/>';}
				echo "$latepasses LatePass".($latepasses!=1?"es":"").' available';
			}
		} else {
			$gbcomment = '';
		}
		if (trim($gbcomment)!='' || $isteacher) {
			if ($isteacher) {
				echo "<form method=post action=\"gradebook.php?{$_SERVER['QUERY_STRING']}\">";
				echo "<textarea name=\"usrcomments\" rows=3 cols=60>$gbcomment</textarea><br/>";
				echo "<input type=submit value=\"Update Comment\">";
				echo '</form>';
			} else {
				echo "<div class=\"item\">$gbcomment</div>";
			}
		}
		echo '</div>';
	}
	echo "<form method=\"post\" id=\"qform\" action=\"gradebook.php?{$_SERVER['QUERY_STRING']}&uid=$stu\">";
	//echo "<input type='button' onclick='conditionalColor(\"myTable\",1,50,80);' value='Color'/>";
	echo '<table id="myTable" class="gb" style="position:relative;">';
	echo '<thead><tr>';
	if ($stu>0 && $isteacher) {
		echo '<th></th>';
	}
	echo '<th>Item</th><th>Possible</th><th>Grade</th><th>Percent</th>';
	if ($stu>0 && $isteacher) {
		echo '<th>Time Spent (In Questions)</th>';
	}
	if ($stu>0) {
		echo '<th>Feedback<br/><a href="#" class="small pointer" onclick="return showhideallfb(this);">[Show Feedback]</a></th>';
	} 
	echo '</tr></thead><tbody>';
	if ($catfilter>-2) {
		for ($i=0;$i<count($gbt[0][1]);$i++) { //assessment headers
			if (!$isteacher && !$istutor && $gbt[0][1][$i][4]==0) { //skip if hidden 
				continue;
			}
			if ($hidenc==1 && $gbt[0][1][$i][4]==0) { //skip NC
				continue;
			} else if ($hidenc==2 && ($gbt[0][1][$i][4]==0 || $gbt[0][1][$i][4]==3)) {//skip all NC
				continue;
			}
			if ($gbt[0][1][$i][3]>$availshow) {
				continue;
			}
			if ($hidepast && $gbt[0][1][$i][3]==0) {
				continue;
			}
			
			echo '<tr class="grid">';
			if ($stu>0 && $isteacher) { 
				if ($gbt[0][1][$i][6]==0) {
					echo '<td><input type="checkbox" name="assesschk[]" value="'.$gbt[0][1][$i][7] .'" /></td>';
				} else {
					echo '<td></td>';
				}
			}
			
			echo '<td class="cat'.$gbt[0][1][$i][1].'">'.$gbt[0][1][$i][0].'</td>';
			echo '<td>';
			
			if ($gbt[0][1][$i][4]==0 || $gbt[0][1][$i][4]==3) {
				echo $gbt[0][1][$i][2].' (Not Counted)';
			} else {
				echo $gbt[0][1][$i][2].'&nbsp;pts';
				if ($gbt[0][1][$i][4]==2) {
					echo ' (EC)';
				}
			}
			if ($gbt[0][1][$i][5]==1 && $gbt[0][1][$i][6]==0) {
				echo ' (PT)';
			}
			
			echo '</td><td>';
			
			$haslink = false;
			
			if ($isteacher || $istutor || $gbt[1][1][$i][2]==1) { //show link
				if ($gbt[0][1][$i][6]==0) {//online
					if ($stu==-1) { //in averages
						if (isset($gbt[1][1][$i][0])) { //has score
							echo "<a href=\"gb-itemanalysis.php?stu=$stu&cid=$cid&aid={$gbt[0][1][$i][7]}\">";
							$haslink = true;
						}
					} else {
						if (isset($gbt[1][1][$i][0])) { //has score
							echo "<a href=\"gb-viewasid.php?stu=$stu&cid=$cid&asid={$gbt[1][1][$i][4]}&uid={$gbt[1][4][0]}\">";
							$haslink = true;
						} else if ($isteacher) {
							echo "<a href=\"gb-viewasid.php?stu=$stu&cid=$cid&asid=new&aid={$gbt[0][1][$i][7]}&uid={$gbt[1][4][0]}\">";
							$haslink = true;
						}
					}
				} else if ($gbt[0][1][$i][6]==1) {//offline
					if ($isteacher || ($istutor && $gbt[0][1][$i][8]==1)) {
						if ($stu==-1) {
							if (isset($gbt[1][1][$i][0])) { //has score
								echo "<a href=\"addgrades.php?stu=$stu&cid=$cid&grades=all&gbitem={$gbt[0][1][$i][7]}\">";
								$haslink = true;
							}
						} else {
							echo "<a href=\"addgrades.php?stu=$stu&cid=$cid&grades={$gbt[1][4][0]}&gbitem={$gbt[0][1][$i][7]}\">";
							$haslink = true;
						}
					} 
				} else if ($gbt[0][1][$i][6]==2) {//discuss
					if ($stu != -1) {
						echo "<a href=\"viewforumgrade.php?cid=$cid&stu=$stu&uid={$gbt[1][4][0]}&fid={$gbt[0][1][$i][7]}\">";
						$haslink = true;
					}
				}
			}
			if (isset($gbt[1][1][$i][0])) {
				if ($gbt[1][1][$i][3]>9) {
					$gbt[1][1][$i][3] -= 10;
				}
				echo $gbt[1][1][$i][0];
				if ($gbt[1][1][$i][3]==1) {
					echo ' (NC)';
				} else if ($gbt[1][1][$i][3]==2) {
					echo ' (IP)';
				} else if ($gbt[1][1][$i][3]==3) {
					echo ' (OT)';
				} else if ($gbt[1][1][$i][3]==4) {
					echo ' (PT)';
				}
			} else {
				echo '-';
			}
			if ($haslink) { //show link
				echo '</a>';
			}
			if (isset($gbt[1][1][$i][6]) ) {  //($isteacher || $istutor) && 
				if ($gbt[1][1][$i][6]>1) {
					if ($gbt[1][1][$i][6]>2) {
						echo '<sup>LP ('.($gbt[1][1][$i][6]-1).')</sup>';
					} else {
						echo '<sup>LP</sup>';
					}
				} else {
					echo '<sup>e</sup>';
				}
			}
			if (isset($gbt[1][1][$i][5]) && ($gbt[1][1][$i][5]&(1<<$availshow)) && !$hidepast) {
				echo '<sub>d</sub>';
			}
			echo '</td><td>';
			if (isset($gbt[1][1][$i][0])) {
				if ($gbt[0][1][$i][2]>0) {
					echo round(100*$gbt[1][1][$i][0]/$gbt[0][1][$i][2],1).'%';
				}
			} else {
				echo '0%';
			}
			echo '</td>';
			if ($stu>0 && $isteacher) {
				if ($gbt[1][1][$i][7] > -1) {
					echo '<td>'.$gbt[1][1][$i][7].' min ('.$gbt[1][1][$i][8].' min)</td>';
				} else {
					echo '<td></td>';
				}
				
			}
			if ($stu>0) {
				if ($gbt[1][1][$i][1]=='') {
					echo '<td></td>';
				} else {
					echo '<td><a href="#" class="small feedbacksh pointer" onclick="return showhidefb(this,'.$i.')">[Show Feedback]</a><span style="display:none;" id="feedbackholder'.$i.'">'.$gbt[1][1][$i][1].'</span></td>';
				}
			}
			echo '</tr>';
		}
	}
	echo '</tbody></table>';	
	if (!$hidepast) {
		$query = "SELECT stugbmode FROM imas_gbscheme WHERE courseid='$cid'";
		$result = mysqli_query($GLOBALS['link'],$query) or die("Query failed : " . mysqli_error($GLOBALS['link']));
		$show = mysql_fetch_first($result);
		//echo '</tbody></table><br/>';
		if ($isteacher && $stu>0) {
			echo '<p><input type="submit" value="Save Changes" style="display:none"; id="savechgbtn" /> ';
			echo 'Check: <a href="#" onclick="return chkAllNone(\'qform\',\'assesschk[]\',true)">All</a> <a href="#" onclick="return chkAllNone(\'qform\',\'assesschk[]\',false)">None</a> ';
			echo 'With selected: <input type="submit" value="Make Exception" name="posted" /></p>';
		}
		echo '<table class="gb"><thead>';
		echo '<tr>';
		echo '<th >Totals</th>';
		if (($show&1)==1) {
			echo '<th>Past Due</th>';
		}
		if (($show&2)==2) {
			echo '<th>Past Due and Attempted</th>';
		} 
		if (($show&4)==4) {
			echo '<th>Past Due and Available</th>';
		}
		if (($show&8)==8) {
			echo '<th>All</th>';
		}
		echo '</tr>';
		echo '</thead><tbody>';
		if (count($gbt[0][2])>1 || $catfilter!=-1) { //want to show cat headers?
			//$donedbltop = false;
			for ($i=0;$i<count($gbt[0][2]);$i++) { //category headers	
				if ($availshow<2 && $gbt[0][2][$i][2]>1) {
					continue;
				} else if ($availshow==2 && $gbt[0][2][$i][2]==3) {
					continue;
				}
				//if (!$donedbltop) {
				//	echo '<tr class="grid dbltop">';
				//	$donedbltop = true;
				//} else {
					echo '<tr class="grid">';
				//}
				echo '<td class="cat'.$gbt[0][2][$i][1].'"><span class="cattothdr">'.$gbt[0][2][$i][0].'</span></td>';
				if (($show&1)==1) {
					echo '<td>'.$gbt[1][2][$i][0].'/'.$gbt[0][2][$i][3].' (';
					if ($gbt[0][2][$i][3]>0) {
						echo round(100*$gbt[1][2][$i][0]/$gbt[0][2][$i][3],1).'%)</td>';
					} else {
						echo '0%)</td>';
					}
				}
				if (($show&2)==2) {
					echo '<td>'.$gbt[1][2][$i][3].'/'.$gbt[1][2][$i][4].' (';
					if ($gbt[1][2][$i][4]>0) {
						echo round(100*$gbt[1][2][$i][3]/$gbt[1][2][$i][4],1).'%)</td>';
					} else {
						echo '0%)</td>';
					}
				}
				if (($show&4)==4) {
					echo '<td>'.$gbt[1][2][$i][1].'/'.$gbt[0][2][$i][4].' (';
					if ($gbt[0][2][$i][4]>0) {
						echo round(100*$gbt[1][2][$i][1]/$gbt[0][2][$i][4],1).'%)</td>';
					} else {
						echo '0%)</td>';
					}
				}
				if (($show&8)==8) {
					echo '<td>'.$gbt[1][2][$i][2].'/'.$gbt[0][2][$i][5].' (';
					if ($gbt[0][2][$i][5]>0) {
						echo round(100*$gbt[1][2][$i][2]/$gbt[0][2][$i][5],1).'%)</td>';
					} else {
						echo '0%)</td>';
					}
				}
				
				echo '</tr>';
			}
		}
		//Totals
		if ($catfilter<0) {
			echo '<tr class="grid">';
			if (isset($gbt[0][3][0])) { //using points based
				echo '<td>Total</td>';
				if (($show&1)==1) {
					echo '<td>'.$gbt[1][3][0].'/'.$gbt[0][3][0].' ('.$gbt[1][3][3].'%)</td>';
				}
				if (($show&2)==2) {
					echo '<td>'.$gbt[1][3][6].'/'.$gbt[1][3][7].' ('.$gbt[1][3][8].'%)</td>';
				}
				if (($show&4)==4) {
					echo '<td>'.$gbt[1][3][1].'/'.$gbt[0][3][1].' ('.$gbt[1][3][4].'%)</td>';
				}
				if (($show&8)==8) {
					echo '<td>'.$gbt[1][3][2].'/'.$gbt[0][3][2].' ('.$gbt[1][3][5].'%)</td>';
				}
				
			} else {
				echo '<td>Weighted Total</td>';
				if (($show&1)==1) { echo '<td>'.$gbt[1][3][0].'%</td>';}
				if (($show&2)==2) {echo '<td>'.$gbt[1][3][6].'%</td>';}
				if (($show&4)==4) {echo '<td>'.$gbt[1][3][1].'%</td>';}
				if (($show&8)==8) {echo '<td>'.$gbt[1][3][2].'%</td>';}
			}
			echo '</tr>';
			/*if ($availshow==2) {
				echo '<tr class="grid">';
				if (isset($gbt[0][3][0])) { //using points based
					echo '<td>Total All</td>';
					echo '<td>'.$gbt[0][3][2].'&nbsp;pts</td>';
					echo '<td>'.$gbt[1][3][2].'</td>';
					echo '<td>'.$gbt[1][3][5] .'%</td>';
				} else {
					echo '<td>Weighted Total All %</td>'; 
					echo '<td></td>';
					echo '<td>'.$gbt[1][3][2].'%</td>';
					echo '<td></td>';
				}
				if ($stu>0) {
					echo '<td></td>';
				}
				echo '</tr>';
			}
			echo '<tr class="grid">';
			if (isset($gbt[0][3][0])) { //using points based
				echo '<td>Total Past & Current</td>';
				echo '<td>'.$gbt[0][3][1].'&nbsp;pts</td>';
				echo '<td>'.$gbt[1][3][1].'</td>';
				echo '<td>'.$gbt[1][3][4] .'%</td>';
			} else {
				echo '<td>Weighted Total Past & Current %</td>'; 
				echo '<td></td>';
				echo '<td>'.$gbt[1][3][1].'%</td>';
				echo '<td></td>';
			}
			if ($stu>0) {
				echo '<td></td>';
				echo '<td></td>';
			}
			echo '</tr>';
			echo '<tr class="grid">';
			if (isset($gbt[0][3][0])) { //using points based
				echo '<td>Total Past Due</td>';
				echo '<td>'.$gbt[0][3][0].'&nbsp;pts</td>';
				echo '<td>'.$gbt[1][3][0].'</td>';
				echo '<td>'.$gbt[1][3][3] .'%</td>';
			} else {
				echo '<td>Weighted Total Past Due %</td>'; 
				echo '<td></td>';
				echo '<td>'.$gbt[1][3][0].'%</td>';
				echo '<td></td>';
			}
			if ($stu>0) {
				echo '<td></td>';
				echo '<td></td>';
			}
			echo '</tr>';
			
			echo '</tr>';
			echo '<tr class="grid">';
			if (isset($gbt[0][3][0])) { //using points based
				echo '<td>Total Past &amp; Attempted</td>';
				echo '<td>'.$gbt[1][3][7].'&nbsp;pts</td>';
				echo '<td>'.$gbt[1][3][6].'</td>';
				echo '<td>'.$gbt[1][3][8] .'%</td>';
			} else {
				echo '<td>Weighted Total ast &amp; Attempted</td>'; 
				echo '<td></td>';
				echo '<td>'.$gbt[1][3][6].'%</td>';
				echo '<td></td>';
			}
			if ($stu>0) {
				echo '<td></td>';
				echo '<td></td>';
			}
			echo '</tr>';
			*/
			
			
		}
		echo '</tbody></table><br/>';
		echo '<p>';
		if (($show&1)==1) {
			echo '<b>Past Due</b> total only includes items whose due date has past.  Current assignments are not counted in this total.<br/>';
		}
		if (($show&2)==2) {
			echo '<b>Past Due and Attempted</b> total includes items whose due date has past, as well as currently available items you have started working on.<br/>';
		} 
		if (($show&4)==4) {
			echo '<b>Past Due and Available</b> total includes items whose due date has past as well as currently available items, even if you haven\'t starting working on them yet.<br/>';
		}
		if (($show&8)==8) {
			echo '<b>All</b> total includes all items: past, current, and future to-be-done items.';
		}
		echo '</p>';
	}
	
	if ($hidepast && $isteacher && $stu>0) {
		echo '<p><input type="submit" value="Save Changes" style="display:none"; id="savechgbtn" />';
		echo '<input type="submit" value="Make Exception" name="massexception" /> for selected assessments</p>';
	}
	
	echo "</form>";
	$sarr = "'S','N','N','N'";
	if ($stu>0) {
		$sarr .= ",'N','S'";
	}
	echo "<script>initSortTable('myTable',Array($sarr),false);</script>\n";
	/*
	if ($hidepast) {
		echo "<script>initSortTable('myTable',Array($sarr),false);</script>\n";
	} else if ($availshow==2) {
		echo "<script>initSortTable('myTable',Array($sarr),false,-3);</script>\n";
	} else {
		echo "<script>initSortTable('myTable',Array($sarr),false,-2);</script>\n";
	}
	*/
}

function gbinstrdisp() {
	global $hidenc,$showpics,$isteacher,$istutor,$cid,$gbmode,$stu,$availshow,$catfilter,$secfilter,$totonleft,$imasroot,$isdiag,$tutorsection,$avgontop,$hidelocked,$colorize,$urlmode;
	$curdir = rtrim(dirname(__FILE__), '/\\');
	if ($availshow==4) {
		$availshow=1;
		$hidepast = true;
	}
	$gbt = gbtable();
	if ($avgontop) {
		$avgrow = array_pop($gbt);
		array_splice($gbt,1,0,array($avgrow));
	}
	//print_r($gbt);
	//echo "<script type=\"text/javascript\" src=\"$imasroot/javascript/tablesorter.js\"></script>\n"; in placeinhead
	echo "<div id=\"tbl-container\">";
	echo '<div id="bigcontmyTable"><div id="tblcontmyTable">';
	
	echo '<table class="gb" id="myTable"><thead><tr>';
	$n=0;
	for ($i=0;$i<count($gbt[0][0]);$i++) { //biographical headers
		if ($i==1) {echo '<th><div>&nbsp;</div></th>';} //for pics
		if ($i==1 && $gbt[0][0][1]!='ID') { continue;}
		echo '<th><div>'.$gbt[0][0][$i];
		if (($gbt[0][0][$i]=='Section' || ($isdiag && $i==4)) && (!$istutor || $tutorsection=='')) {
			echo "<br/><select id=\"secfiltersel\" onchange=\"chgsecfilter()\"><option value=\"-1\" ";
			if ($secfilter==-1) {echo  'selected=1';}
			echo  '>All</option>';
			$query = "SELECT DISTINCT section FROM imas_students WHERE courseid='$cid' ORDER BY section";
			$result = mysqli_query($GLOBALS['link'],$query) or die("Query failed : " . mysqli_error($GLOBALS['link']));
			while ($row = mysqli_fetch_row($result)) {
				if ($row[0]=='') { continue;}
				echo  "<option value=\"{$row[0]}\" ";
				if ($row[0]==$secfilter) {
					echo  'selected=1';
				}
				echo  ">{$row[0]}</option>";
			}
			echo  "</select>";	
			
		} else if ($gbt[0][0][$i]=='Name') {
			echo '<br/><span class="small">N='.(count($gbt)-2).'</span><br/>';
			echo "<select id=\"toggle5\" onchange=\"chgtoggle()\">";
			echo "<option value=0 "; writeHtmlSelected($hidelocked,0); echo ">Show Locked</option>";
			echo "<option value=2 "; writeHtmlSelected($hidelocked,2); echo ">Hide Locked</option>";
			echo "</select>";
		}
		echo '</div></th>';
		
		$n++;
	}
	if ($totonleft && !$hidepast) {
		//total totals
		if ($catfilter<0) {
			if (isset($gbt[0][3][0])) { //using points based
				echo '<th><div><span class="cattothdr">Total<br/>'.$gbt[0][3][$availshow].'&nbsp;pts</span></div></th>';
				echo '<th><div>%</div></th>';
				$n+=2;
			} else {
				echo '<th><div><span class="cattothdr">Weighted Total %</span></div></th>';
				$n++;
			}
		}
		if (count($gbt[0][2])>1 || $catfilter!=-1) { //want to show cat headers?
			for ($i=0;$i<count($gbt[0][2]);$i++) { //category headers	
				if (($availshow<2 || $availshow==3) && $gbt[0][2][$i][2]>1) {
					continue;
				} else if ($availshow==2 && $gbt[0][2][$i][2]==3) {
					continue;
				}
				echo '<th class="cat'.$gbt[0][2][$i][1].'"><div><span class="cattothdr">';
				if ($availshow<3) {
					echo $gbt[0][2][$i][0].'<br/>';
					echo $gbt[0][2][$i][3+$availshow].'&nbsp;pts';
				} else if ($availshow==3) { //past and attempted
					echo $gbt[0][2][$i][0];
				}
				echo '</span></div></th>';
				$n++;
			}
		}
		
	}
	if ($catfilter>-2) {
		for ($i=0;$i<count($gbt[0][1]);$i++) { //assessment headers
			if (!$isteacher && !$istutor && $gbt[0][1][$i][4]==0) { //skip if hidden 
				continue;
			}
			if ($hidenc==1 && $gbt[0][1][$i][4]==0) { //skip NC
				continue;
			} else if ($hidenc==2 && ($gbt[0][1][$i][4]==0 || $gbt[0][1][$i][4]==3)) {//skip all NC
				continue;
			}
			if ($gbt[0][1][$i][3]>$availshow) {
				continue;
			}
			if ($hidepast && $gbt[0][1][$i][3]==0) {
				continue;
			}
			//name and points
			echo '<th class="cat'.$gbt[0][1][$i][1].'"><div>'.$gbt[0][1][$i][0].'<br/>';
			if ($gbt[0][1][$i][4]==0 || $gbt[0][1][$i][4]==3) {
				echo $gbt[0][1][$i][2].' (Not Counted)';
			} else {
				echo $gbt[0][1][$i][2].'&nbsp;pts';
				if ($gbt[0][1][$i][4]==2) {
					echo ' (EC)';
				}
			}
			if ($gbt[0][1][$i][5]==1 && $gbt[0][1][$i][6]==0) {
				echo ' (PT)';
			}
			//links
			if ($gbt[0][1][$i][6]==0 ) { //online
				if ($isteacher) {
					echo "<br/><a class=small href=\"addassessment.php?id={$gbt[0][1][$i][7]}&amp;cid=$cid&amp;from=gb\">[Settings]</a>";
					echo "<br/><a class=small href=\"isolateassessgrade.php?cid=$cid&amp;aid={$gbt[0][1][$i][7]}\">[Isolate]</a>";
					if ($gbt[0][1][$i][10]==true) {
						echo "<br/><a class=small href=\"isolateassessbygroup.php?cid=$cid&amp;aid={$gbt[0][1][$i][7]}\">[By Group]</a>";
					}
				} else {
					echo "<br/><a class=small href=\"isolateassessgrade.php?cid=$cid&amp;aid={$gbt[0][1][$i][7]}\">[Isolate]</a>";
				}
			} else if ($gbt[0][1][$i][6]==1  && ($isteacher || ($istutor && $gbt[0][1][$i][8]==1))) { //offline
				if ($isteacher) {
					echo "<br/><a class=small href=\"addgrades.php?stu=$stu&amp;cid=$cid&amp;grades=all&amp;gbitem={$gbt[0][1][$i][7]}\">[Settings]</a>";
					echo "<br/><a class=small href=\"addgrades.php?stu=$stu&amp;cid=$cid&amp;grades=all&amp;gbitem={$gbt[0][1][$i][7]}&amp;isolate=true\">[Isolate]</a>";
				} else {
					echo "<br/><a class=small href=\"addgrades.php?stu=$stu&amp;cid=$cid&amp;grades=all&amp;gbitem={$gbt[0][1][$i][7]}&amp;isolate=true\">[Scores]</a>";
				}
			} else if ($gbt[0][1][$i][6]==2  && $isteacher) { //discussion
				echo "<br/><a class=small href=\"addforum.php?id={$gbt[0][1][$i][7]}&amp;cid=$cid&amp;from=gb\">[Settings]</a>";
			}
			
			echo '</div></th>';
			$n++;
		}
	}
	if (!$totonleft && !$hidepast) {
		if (count($gbt[0][2])>1 || $catfilter!=-1) { //want to show cat headers?
			for ($i=0;$i<count($gbt[0][2]);$i++) { //category headers	
				if (($availshow<2 || $availshow==3) && $gbt[0][2][$i][2]>1) {
					continue;
				} else if ($availshow==2 && $gbt[0][2][$i][2]==3) {
					continue;
				}
				echo '<th class="cat'.$gbt[0][2][$i][1].'"><div><span class="cattothdr">';
				if ($availshow<3) {
					echo $gbt[0][2][$i][0].'<br/>';
					echo $gbt[0][2][$i][3+$availshow].'&nbsp;pts';
				} else if ($availshow==3) { //past and attempted
					echo $gbt[0][2][$i][0];
				}
				echo '</span></div></th>';
				$n++;
			}
		}
		//total totals
		if ($catfilter<0) {
			if (isset($gbt[0][3][0])) { //using points based
				echo '<th><div><span class="cattothdr">Total<br/>'.$gbt[0][3][$availshow].'&nbsp;pts</span></div></th>';
				echo '<th><div>%</div></th>';
				$n+=2;
			} else {
				echo '<th><div><span class="cattothdr">Weighted Total %</span></div></th>';
				$n++;
			}
		}
	}
	echo '</tr></thead><tbody>';
	//create student rows
	if(isset($GLOBALS['CFG']['GEN']['AWSforcoursefiles']) && $GLOBALS['CFG']['GEN']['AWSforcoursefiles'] == true) {
		$userimgbase = $urlmode."s3.amazonaws.com/{$GLOBALS['AWSbucket']}/cfiles";
	} else {
		$userimgbase = "$imasroot/course/files";
	}
	for ($i=1;$i<count($gbt);$i++) {
		if ($i==1) {$insdiv = "<div>";  $enddiv = "</div>";} else {$insdiv = ''; $enddiv = '';}
		if ($i%2!=0) {
			echo "<tr class=even onMouseOver=\"highlightrow(this)\" onMouseOut=\"unhighlightrow(this)\">"; 
		} else {
			echo "<tr class=odd onMouseOver=\"highlightrow(this)\" onMouseOut=\"unhighlightrow(this)\">"; 
		}
		echo '<td class="locked" scope="row"><div class="trld">';
		if ($gbt[$i][0][0]!="Averages" && $isteacher) {
			echo "<input type=\"checkbox\" name='checked[]' value='{$gbt[$i][4][0]}' />&nbsp;";
		}
		echo "<a href=\"gradebook.php?cid=$cid&amp;stu={$gbt[$i][4][0]}\">";
		if ($gbt[$i][4][1]>0) {
			echo '<span style="text-decoration: line-through;">'.$gbt[$i][0][0].'</span>';
		} else {
			echo $gbt[$i][0][0];
		}
		echo '</a></div></td>';
		if ($showpics==1 && $gbt[$i][4][2]==1) { //file_exists("$curdir//files/userimg_sm{$gbt[$i][4][0]}.jpg")) {
			echo "<td>{$insdiv}<div class=\"trld\"><img src=\"$userimgbase/userimg_sm{$gbt[$i][4][0]}.jpg\"/></div></td>";
		} else if ($showpics==2 && $gbt[$i][4][2]==1) {
			echo "<td>{$insdiv}<div class=\"trld\"><img src=\"$userimgbase/userimg_{$gbt[$i][4][0]}.jpg\"/></div></td>";
		} else {
			echo '<td>'.$insdiv.'<div class="trld">&nbsp;</div></td>';
		}
		for ($j=($gbt[0][0][1]=='ID'?1:2);$j<count($gbt[0][0]);$j++) {
			echo '<td class="c">'.$insdiv.$gbt[$i][0][$j].$enddiv .'</td>';	
		}
		if ($totonleft && !$hidepast) {
			//total totals
			if ($catfilter<0) {
				if ($availshow==3) {
					if ($gbt[$i][0][0]=='Averages') { 
						if (isset($gbt[$i][3][8])) { //using points based
							echo '<td class="c">'.$insdiv.$gbt[$i][3][6].'%'.$enddiv .'</td>';
						}
						echo '<td class="c">'.$insdiv.$gbt[$i][3][6].'%'.$enddiv .'</td>';
					} else {
						if (isset($gbt[$i][3][8])) { //using points based
							echo '<td class="c">'.$insdiv.$gbt[$i][3][6].'/'.$gbt[$i][3][7].$enddiv.'</td>';
							echo '<td class="c">'.$insdiv.$gbt[$i][3][8] .'%'.$enddiv .'</td>';
							
						} else {
							echo '<td class="c">'.$insdiv.$gbt[$i][3][6].'%'.$enddiv .'</td>';
						}
					}
				} else {
					if (isset($gbt[0][3][0])) { //using points based
						echo '<td class="c">'.$insdiv.$gbt[$i][3][$availshow].$enddiv .'</td>';
						echo '<td class="c">'.$insdiv.$gbt[$i][3][$availshow+3] .'%'.$enddiv .'</td>';
					} else {
						echo '<td class="c">'.$insdiv.$gbt[$i][3][$availshow].'%'.$enddiv .'</td>';
					}
				}
			}
			//category totals
			if (count($gbt[0][2])>1 || $catfilter!=-1) { //want to show cat headers?
				for ($j=0;$j<count($gbt[0][2]);$j++) { //category headers	
					if (($availshow<2 || $availshow==3) && $gbt[0][2][$j][2]>1) {
						continue;
					} else if ($availshow==2 && $gbt[0][2][$j][2]==3) {
						continue;
					}
					if ($catfilter!=-1 && $availshow<3 && $gbt[0][2][$j][$availshow+3]>0) {
						//echo '<td class="c">'.$gbt[$i][2][$j][$availshow].' ('.round(100*$gbt[$i][2][$j][$availshow]/$gbt[0][2][$j][$availshow+3])  .'%)</td>';
						echo '<td class="c">'.$insdiv;
						if ($gbt[$i][0][0]=='Averages' && $availshow!=3) {
							echo "<span onmouseover=\"tipshow(this,'5-number summary: {$gbt[0][2][$j][6+$availshow]}')\" onmouseout=\"tipout()\" >";
						}
						echo $gbt[$i][2][$j][$availshow].' ('.round(100*$gbt[$i][2][$j][$availshow]/$gbt[0][2][$j][$availshow+3])  .'%)';
	
						if ($gbt[$i][0][0]=='Averages' && $availshow!=3) {
							echo '</span>';
						}
						echo $enddiv .'</td>';
					} else {
						//echo '<td class="c">'.$gbt[$i][2][$j][$availshow].'</td>';
						echo '<td class="c">'.$insdiv;
						if ($gbt[$i][0][0]=='Averages') {
							echo "<span onmouseover=\"tipshow(this,'5-number summary: {$gbt[0][2][$j][6+$availshow]}')\" onmouseout=\"tipout()\" >";
						}
						if ($availshow==3) {
							if ($gbt[$i][0][0]=='Averages') {
								echo $gbt[$i][2][$j][3].'%';//echo '-';
							} else {
								echo $gbt[$i][2][$j][3].'/'.$gbt[$i][2][$j][4];
							}
						} else {
							echo $gbt[$i][2][$j][$availshow];
						}
						if ($gbt[$i][0][0]=='Averages') {
							echo '</span>';
						}
						echo $enddiv .'</td>';
					} 
					
				}
			}
		}
		//assessment values
		if ($catfilter>-2) {
			for ($j=0;$j<count($gbt[0][1]);$j++) {
				if (!$isteacher && !$istutor && $gbt[0][1][$j][4]==0) { //skip if hidden 
					continue;
				}
				if ($hidenc==1 && $gbt[0][1][$j][4]==0) { //skip NC
					continue;
				} else if ($hidenc==2 && ($gbt[0][1][$j][4]==0 || $gbt[0][1][$j][4]==3)) {//skip all NC
					continue;
				}
				if ($gbt[0][1][$j][3]>$availshow) {
					continue;
				}
				if ($hidepast && $gbt[0][1][$j][3]==0) {
					continue;
				}
				//if online, not average, and either score exists and active, or score doesn't exist and assess is current,
				if ($gbt[0][1][$j][6]==0 && $gbt[$i][1][$j][4]!='average' && ((isset($gbt[$i][1][$j][3]) && $gbt[$i][1][$j][3]>9) || (!isset($gbt[$i][1][$j][3]) && $gbt[0][1][$j][3]==1))) {
					echo '<td class="c isact">'.$insdiv;
				} else {
					echo '<td class="c">'.$insdiv;
				}
				if (isset($gbt[$i][1][$j][5]) && ($gbt[$i][1][$j][5]&(1<<$availshow)) && !$hidepast) {
					echo '<span style="font-style:italic">';
				}
				if ($gbt[0][1][$j][6]==0) {//online
					if (isset($gbt[$i][1][$j][0])) {
						if ($istutor && $gbt[$i][1][$j][4]=='average') {
							
						} else if ($gbt[$i][1][$j][4]=='average') {
							echo "<a href=\"gb-itemanalysis.php?stu=$stu&amp;cid=$cid&amp;asid={$gbt[$i][1][$j][4]}&amp;aid={$gbt[0][1][$j][7]}\" "; 
							echo "onmouseover=\"tipshow(this,'5-number summary: {$gbt[0][1][$j][9]}')\" onmouseout=\"tipout()\" ";
							echo ">";
						} else {
							echo "<a href=\"gb-viewasid.php?stu=$stu&amp;cid=$cid&amp;asid={$gbt[$i][1][$j][4]}&amp;uid={$gbt[$i][4][0]}\">";
						}
						if ($gbt[$i][1][$j][3]>9) {
							$gbt[$i][1][$j][3] -= 10;
						} 
						echo $gbt[$i][1][$j][0];
						if ($gbt[$i][1][$j][3]==1) {
							echo ' (NC)';
						} else if ($gbt[$i][1][$j][3]==2) {
							echo ' (IP)';
						} else if ($gbt[$i][1][$j][3]==3) {
							echo ' (OT)';
						} else if ($gbt[$i][1][$j][3]==4) {
							echo ' (PT)';
						} 
						if ($istutor && $gbt[$i][1][$j][4]=='average') {
						} else {
							echo '</a>';
						}
						if ($gbt[$i][1][$j][1]==1) {
							echo '<sup>*</sup>';
						}
						
					} else { //no score
						if ($gbt[$i][0][0]=='Averages') {
							echo '-';
						} else if ($isteacher) {
							echo "<a href=\"gb-viewasid.php?stu=$stu&amp;cid=$cid&amp;asid=new&amp;aid={$gbt[0][1][$j][7]}&amp;uid={$gbt[$i][4][0]}\">-</a>";
						} else {
							echo '-';
						}
					}
					if (isset($gbt[$i][1][$j][6]) ) {
						if ($gbt[$i][1][$j][6]>1) {
							if ($gbt[$i][1][$j][6]>2) {
								echo '<sup>LP ('.($gbt[$i][1][$j][6]-1).')</sup>';
							} else {
								echo '<sup>LP</sup>';
							}
						} else {
							echo '<sup>e</sup>';
						}
					}
				} else if ($gbt[0][1][$j][6]==1) { //offline
					if ($isteacher) {
						if ($gbt[$i][0][0]=='Averages') {
							echo "<a href=\"addgrades.php?stu=$stu&amp;cid=$cid&amp;grades=all&amp;gbitem={$gbt[0][1][$j][7]}\" ";
							echo "onmouseover=\"tipshow(this,'5-number summary: {$gbt[0][1][$j][9]}')\" onmouseout=\"tipout()\" ";
							echo ">";
						} else {
							echo "<a href=\"addgrades.php?stu=$stu&amp;cid=$cid&amp;grades={$gbt[$i][4][0]}&amp;gbitem={$gbt[0][1][$j][7]}\">";
						}
					} else if ($istutor && $gbt[0][1][$j][8]==1) {
						if ($gbt[$i][0][0]=='Averages') {
							echo "<a href=\"addgrades.php?stu=$stu&amp;cid=$cid&amp;grades=all&amp;gbitem={$gbt[0][1][$j][7]}\">";
						} else {
							echo "<a href=\"addgrades.php?stu=$stu&amp;cid=$cid&amp;grades={$gbt[$i][4][0]}&amp;gbitem={$gbt[0][1][$j][7]}\">";
						}
					}
					if (isset($gbt[$i][1][$j][0])) {
						echo $gbt[$i][1][$j][0];
						if ($gbt[$i][1][$j][3]==1) {
							echo ' (NC)';
						}
					} else {
						echo '-';
					}
					if ($isteacher || ($istutor && $gbt[0][1][$j][8]==1)) {
						echo '</a>';
					}
					if ($gbt[$i][1][$j][1]==1) {
						echo '<sup>*</sup>';
					}
				} else if ($gbt[0][1][$j][6]==2) { //discuss
					if (isset($gbt[$i][1][$j][0])) {
						if ( $gbt[$i][0][0]!='Averages') {
							echo "<a href=\"viewforumgrade.php?cid=$cid&amp;stu=$stu&amp;uid={$gbt[$i][4][0]}&amp;fid={$gbt[0][1][$j][7]}\">";
							echo $gbt[$i][1][$j][0];
							echo '</a>';
						} else {
							echo "<span onmouseover=\"tipshow(this,'5-number summary: {$gbt[0][1][$j][9]}')\" onmouseout=\"tipout()\"> ";
							echo $gbt[$i][1][$j][0];
							echo '</span>';
						}
						if ($gbt[$i][1][$j][1]==1) {
							echo '<sup>*</sup>';
						}
					} else {
						if ($isteacher && $gbt[$i][0][0]!='Averages') {
							echo "<a href=\"viewforumgrade.php?cid=$cid&amp;stu=$stu&amp;uid={$gbt[$i][4][0]}&amp;fid={$gbt[0][1][$j][7]}\">-</a>";
						} else {
							echo '-';
						}
					}
					
				}
				if (isset($gbt[$i][1][$j][5]) && ($gbt[$i][1][$j][5]&(1<<$availshow)) && !$hidepast) {
					echo '<sub>d</sub></span>';
				}
				echo $enddiv .'</td>';
			}
		}
		if (!$totonleft && !$hidepast) {
			//category totals
			if (count($gbt[0][2])>1 || $catfilter!=-1) { //want to show cat headers?
				for ($j=0;$j<count($gbt[0][2]);$j++) { //category headers	
					if (($availshow<2 || $availshow==3) && $gbt[0][2][$j][2]>1) {
						continue;
					} else if ($availshow==2 && $gbt[0][2][$j][2]==3) {
						continue;
					}
					if ($catfilter!=-1 && $availshow<3 && $gbt[0][2][$j][$availshow+3]>0) {
						//echo '<td class="c">'.$gbt[$i][2][$j][$availshow].' ('.round(100*$gbt[$i][2][$j][$availshow]/$gbt[0][2][$j][$availshow+3])  .'%)</td>';
						echo '<td class="c">'.$insdiv;
						if ($gbt[$i][0][0]=='Averages' && $availshow!=3) {
							echo "<span onmouseover=\"tipshow(this,'5-number summary: {$gbt[0][2][$j][6+$availshow]}')\" onmouseout=\"tipout()\" >";
						}
						echo $gbt[$i][2][$j][$availshow].' ('.round(100*$gbt[$i][2][$j][$availshow]/$gbt[0][2][$j][$availshow+3])  .'%)';
	
						if ($gbt[$i][0][0]=='Averages' && $availshow!=3) {
							echo '</span>';
						}
						echo $enddiv .'</td>';
					} else {
						//echo '<td class="c">'.$gbt[$i][2][$j][$availshow].'</td>';
						echo '<td class="c">'.$insdiv;
						if ($gbt[$i][0][0]=='Averages' && $availshow<3) {
							echo "<span onmouseover=\"tipshow(this,'5-number summary: {$gbt[0][2][$j][6+$availshow]}')\" onmouseout=\"tipout()\" >";
						}
						if ($availshow==3) {
							if ($gbt[$i][0][0]=='Averages') {
								echo $gbt[$i][2][$j][3].'%';
							} else {
								echo $gbt[$i][2][$j][3].'/'.$gbt[$i][2][$j][4];
							}
						} else {
							echo $gbt[$i][2][$j][$availshow];
						}
						if ($gbt[$i][0][0]=='Averages' && $availshow<3) {
							echo '</span>';
						}
						echo $enddiv .'</td>';
					}
					
				}
			}
			
			//total totals
			if ($catfilter<0) {
				if ($availshow==3) {
					if ($gbt[$i][0][0]=='Averages') { 
						if (isset($gbt[$i][3][8])) { //using points based
							echo '<td class="c">'.$insdiv.$gbt[$i][3][6].'%'.$enddiv .'</td>';
						}
						echo '<td class="c">'.$insdiv.$gbt[$i][3][6].'%'.$enddiv .'</td>';
					} else {
						if (isset($gbt[$i][3][8])) { //using points based
							echo '<td class="c">'.$insdiv.$gbt[$i][3][6].'/'.$gbt[$i][3][7].$enddiv .'</td>';
							echo '<td class="c">'.$insdiv.$gbt[$i][3][8] .'%'.$enddiv .'</td>';
							
						} else {
							echo '<td class="c">'.$insdiv.$gbt[$i][3][6].'%'.$enddiv .'</td>';
						}
					}
				} else {
					if (isset($gbt[0][3][0])) { //using points based
						echo '<td class="c">'.$insdiv.$gbt[$i][3][$availshow].$enddiv .'</td>';
						echo '<td class="c">'.$insdiv.$gbt[$i][3][$availshow+3] .'%'.$enddiv .'</td>';
					} else {
						echo '<td class="c">'.$insdiv.$gbt[$i][3][$availshow].'%'.$enddiv .'</td>';
					}
				}
			}
		}
		echo '</tr>';
	}
	echo "</tbody></table></div></div>";
	if ($n>1) {
		$sarr = array_fill(0,$n-1,"'N'");
	} else {
		$sarr = array();
	}
	array_unshift($sarr,"false");
	array_unshift($sarr,"'S'");
	
	$sarr = implode(",",$sarr);
	if (count($gbt)<500) {
		if ($avgontop) {
			echo "<script>initSortTable('myTable',Array($sarr),true,true,false);</script>\n";
		} else {
			echo "<script>initSortTable('myTable',Array($sarr),true,false);</script>\n";
		}
	}
	if ($colorize != '0') {
		echo '<script type="text/javascript">addLoadEvent( function() {updateColors(document.getElementById("colorsel"));} );</script>';
	}
		
	
}

?>
