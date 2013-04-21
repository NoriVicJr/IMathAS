<?php
// code is adapted from IMS-DEV sample code
// on code.google.com/p/ims-dev
require_once  'OAuth.php';

/**
 * A Trivial store for testing - no support for tokens
 */
class IMathASLTIOAuthDataStore extends OAuthDataStore {
   
    function lookup_consumer($consumer_key) {
        	
	$keyparts = explode('_',$consumer_key);
	
	if ($keyparts[0]=='cid' || $keyparts[0]=='placein') {
		$keyparts[1] = intval($keyparts[1]);
		$query = "SELECT ltisecret FROM imas_courses WHERE id='{$keyparts[1]}'";
	} else if ($keyparts[0]=='aid') {
		$keyparts[1] = intval($keyparts[1]);
		$query = "SELECT ic.ltisecret FROM imas_courses AS ic JOIN imas_assessments AS ia ON ";
		$query .= "ic.id=ia.courseid WHERE ia.id='{$keyparts[1]}'";
	} else if ($keyparts[0]=='sso') {
		$query = "SELECT password,rights FROM imas_users WHERE SID='{$keyparts[1]}' AND (rights=11 OR rights=76)";
	} else {
		$query = "SELECT password,rights FROM imas_users WHERE SID='{$keyparts[0]}' AND (rights=11 OR rights=76)";
	}
	
	$result = mysqli_query($GLOBALS['link'],$query) or die("Query failed : " . mysqli_error($GLOBALS['link']));
	if (mysqli_num_rows($result)>0) {
		list($secret, $rights) = mysqli_fetch_row($result);
		if ($keyparts[0]=='cid' || $keyparts[0]=='aid' || $keyparts[0]=='placein') {
			$rights = 11;
		} 
		if ($secret=='') {
			//if secret isn't set, don't use blank as secret
			return NULL;
		}
		 $consumer = new OAuthConsumer($consumer_key,$secret, NULL,$rights);
		 return $consumer;
        }
        return NULL;
    }

    function lookup_token($consumer, $token_type, $token) {
        return new OAuthToken($consumer, "");
    }

    // Return NULL if the nonce has not been used
    // Return $nonce if the nonce was previously used
    function lookup_nonce($consumer, $token, $nonce, $timestamp) {
        $query = "SELECT id FROM imas_ltinonces WHERE nonce='$nonce'";
	$result = mysqli_query($GLOBALS['link'],$query) or die("Query failed : " . mysqli_error($GLOBALS['link']));
	if (mysqli_num_rows($result)>0) {
		return mysqli_fetch_first($result);
	} else {
		return NULL;
	}
    }
    
    //records nonce and deletes out old ones
    function record_nonce($nonce) {
	    $now = time();
	    $query = "INSERT INTO imas_ltinonces (nonce,time) VALUES ('$nonce','$now')";
	    mysqli_query($GLOBALS['link'],$query) or die("Query failed : " . mysqli_error($GLOBALS['link']));
	    
	    $old = $now - 5400; //old stuff - 90 minutes
	    $query = "DELETE FROM imas_ltinonces WHERE time<$old";
	    mysqli_query($GLOBALS['link'],$query) or die("Query failed : " . mysqli_error($GLOBALS['link']));
    }

    function mark_nonce_used($request) {
	    $nonce = @$request->get_parameter('oauth_nonce');    
	    $this->record_nonce($nonce);
    }
    
    function new_request_token($consumer) {
        return NULL;
    }

    function new_access_token($token, $consumer) {
        return NULL;
    }
}

?>
