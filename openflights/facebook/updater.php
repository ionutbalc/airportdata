#!/usr/bin/php -q
<?php
require_once 'php/facebook.php';
require_once 'keys.php';
require_once 'profile.php';

// Defined in keys.php, see http://www.facebook.com/topic.php?uid=2205007948&topic=5350 to generate your own
$facebook = new Facebook($appapikey, $appsecret);
$facebook->api_client->session_key = $infinitesessionkey;

$db = mysql_connect("localhost", "openflights");
mysql_select_db("flightdb",$db);
$fbupdates = 0;
$fbfail = 0;

// Check which FB users have added flights
$sql = "SELECT fb.uid,fbuid,u.name,COUNT(*) AS count,SUM(distance) AS distance,fb.updated FROM flights AS f,facebook AS fb, users AS u WHERE f.uid=fb.uid AND u.uid=fb.uid AND f.upd_time > fb.updated GROUP BY f.uid";
$result = mysql_query($sql, $db);
while($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
  $count = $row["count"];

  // This guy has
  if($count > 0) {
    $updated = $row["updated"];
    $uid = $row["uid"];
    $fbuid = $row["fbuid"];
    $ofname = $row["name"];

    // Get details of last flight entered
    $sql = "SELECT s.city AS src, d.city AS dst FROM flights AS f,airports AS s,airports AS d WHERE f.uid=$uid AND f.src_apid=s.apid AND f.dst_apid=d.apid AND f.upd_time > '$updated' ORDER BY f.upd_time LIMIT 1";
    $detailresult = mysql_query($sql, $db);
    if($detail = mysql_fetch_array($detailresult, MYSQL_ASSOC)) {
      $tokens = array( 'src' => $detail["src"],
		       'dst' => $detail["dst"],
		       'count' => $detail["count"] - 1,
		       'ofname' => $ofname );
      $target_ids = array();
      $body_general = '';

      try{
	// Publish feed story
	$facebook->api_client->feed_publishUserAction( $template_bundle_id, json_encode($tokens) , implode(',', $target_ids), $body_general);

	// Update the user's profile box
	$profile_box = get_profile($db, $uid, $fbuid, $ofname);
	$facebook->api_client->profile_setFBML(null, $fbuid, null, null, null, $profile_box);

	// Mark user as updated
	$sql = "UPDATE facebook SET updated=NOW() WHERE uid=$uid";
	$result = mysql_query($sql, $db);
	$fbupdates++;
      }catch(FacebookRestClientException $e){
	echo "Exception: " . $e;
	$fbfail++;
      }
    }
  }
}
echo "Updating complete: $fbupdates successful, $fbfail failed\n";

?>

