<?php

require("../validate.php");

if ($myrights<100) {
    exit;
}

$cid = intval($_GET['cid']);
if ($cid==0) {
    exit;
}

require("../header.php");
if (isset($_POST['assess'])) {
    $dc = 0;
    $source = array();
    foreach ($_POST['assess'] as $aid=>$mark) {
        if ($mark=='*') {
            $dc++;
            $dest = intval($aid);
        } else if ($mark=='-') {
            $source[] = intval($aid);
        }
    }
    $err = false;
    if ($dc==0) {
        echo 'no main course designated';
        $err = true;
    } else if ($dc>1) {
        echo 'too many courses marked with *; only mark one';
        $err = true;
    } else if (count($source)==0) {
        echo 'no courses to merge FROM were marked';
        $err = true;
    } else {
        $query = "SELECT itemorder FROM imas_assessments WHERE id='$dest'";
        $result = mysqli_query($GLOBALS['link'],$query) or die("Query failed : " . mysqli_error($GLOBALS['link']));
        $sourceitemord = mysqli_fetch_first($result);
        $query = "SELECT itemorder,name FROM imas_assessments WHERE id IN (".implode(',',$source).")";
        $result = mysqli_query($GLOBALS['link'],$query) or die("Query failed : " . mysqli_error($GLOBALS['link']));
        while ($row = mysqli_fetch_row($result)) {
            if (substr_count($row[0],',') != substr_count($sourceitemord,',')) {
                echo 'one of this things is not like the others.... '.$row[1].' does not match same number of questions.   assessments cannot be merged';
                $err = true;
                break;
            }
        }
    }
    if (!$err) {
        $query = "SELECT userid,bestseeds,bestscores,bestattempts,bestlastanswers FROM imas_assessment_sessions WHERE assessmentid='$dest'";
	$result = mysqli_query($GLOBALS['link'],$query) or die("Query failed : " . mysqli_error($GLOBALS['link']));
	$adata = array();
        while ($row = mysqli_fetch_row($result)) {
		$adata[$row[0]] = array();
		$adata[$row[0]]['seeds'] = explode(',',$row[1]);
		$adata[$row[0]]['scores'] = explode(',',$row[2]);
		$adata[$row[0]]['attempts'] = explode(',',$row[3]);
		$adata[$row[0]]['la'] = explode('~',$row[4]);
	}
	
	$query = "SELECT userid,bestseeds,bestscores,bestattempts,bestlastanswers FROM imas_assessment_sessions WHERE assessmentid IN (".implode(',',$source).")";
	$result = mysqli_query($GLOBALS['link'],$query) or die("Query failed : " . mysqli_error($GLOBALS['link']));
	while ($row = mysqli_fetch_row($result)) {
		$seeds = explode(',',$row[1]);
		$scores = explode(',',$row[2]);
		$att = explode(',',$row[3]);
		$la = explode(',',$row[4]);
		foreach ($scores as $k=>$v) {
			if (getpts($v)>getpts($adata[$row[0]]['scores'][$k])) {
				$adata[$row[0]]['scores'][$k] = $scores[$k];
				$adata[$row[0]]['seeds'][$k] = $seeds[$k];
				$adata[$row[0]]['attempts'][$k] = $attempts[$k];
				$adata[$row[0]]['la'][$k] = $la[$k];
			}
		}
	}
	foreach ($adata as $uid=>$val) {
		$bestscorelist = implode(',',$val['scores']);
		$bestattemptslist = implode(',',$val['attempts']);
		$bestseedslist = implode(',',$val['seeds']);
		$bestlalist = implode('~',$val['la']);
		$bestlalist = addslashes(stripslashes($bestlalist));
		$query = "UPDATE imas_assessment_sessions SET bestseeds='$bestseedslist',bestattempts='$bestattemptslist',bestscores='$bestscorelist',bestlastanswers='$bestlalist' ";
		$query .= "WHERE userid='$uid' AND assessmentid='$dest'";
		mysqli_query($GLOBALS['link'],$query) or die("Query failed : " . mysqli_error($GLOBALS['link']));
	}
	echo "Merge complete";
    } 
       
       
} else {
    echo '<p>This page will merge scores from multiple copies of the same assessment.';
    echo '  Place a * in the box next to the assessment you want to designate as the main ';
    echo 'assessment.  Place a - in the boxes next to the assessments whose scores you want ';
    echo 'to copy TO the main assessment.  These assessments will not be deleted in this process; ';
    echo 'their scores will simply be transfered to the main assessment.</p>';
   
    echo '<form method="post" action="mergescores.php?cid='.$cid.'">';
    $query = "SELECT id,name FROM imas_assessments WHERE courseid='$cid' ORDER BY name";
    $result = mysqli_query($GLOBALS['link'],$query) or die("Query failed : " . mysqli_error($GLOBALS['link']));
    echo '<p>';
    while ($row = mysqli_fetch_row($result)) {
        echo '<input type="input" size="1" name="assess['.$row[0].']" />'.$row[1].'<br/>';
    }
    echo '</p>';
    echo '<p><input type="submit" value="Submit"/></p>';
}
require("../footer.php");

function getpts($sc) {
	if (strpos($sc,'~')===false) {
		return $sc;
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
?>
