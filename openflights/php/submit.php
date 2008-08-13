<?php
session_start();
$uid = $_SESSION["uid"];
if(!$uid or empty($uid)) {
  printf("Not logged in, aborting");
  exit;
}

$src_date = $HTTP_POST_VARS["src_date"];
$duration = $HTTP_POST_VARS["duration"];
$distance = $HTTP_POST_VARS["distance"];
$src_apid = $HTTP_POST_VARS["src_apid"];
$dst_apid = $HTTP_POST_VARS["dst_apid"];
$number = $HTTP_POST_VARS["number"];
$seat = $HTTP_POST_VARS["seat"];
$seat_type = $HTTP_POST_VARS["type"];
$class = $HTTP_POST_VARS["class"];
$reason = $HTTP_POST_VARS["reason"];
$registration = $HTTP_POST_VARS["registration"];
$plid = $HTTP_POST_VARS["plid"];
$alid = $HTTP_POST_VARS["alid"];
$trid = $HTTP_POST_VARS["trid"];
$fid = $HTTP_POST_VARS["fid"];
$param = $HTTP_POST_VARS["param"];

$db = mysql_connect("localhost", "openflights");
mysql_select_db("flightdb",$db);

// If $plid is of form "NEW:xxx", we add a new plane type called xxx and read its auto-assigned plid
if(strstr($plid, "NEW:")) {
  $newplane = substr($plid, 4);
  
  while ($newplane != "OK") {
    $sql = "SELECT * FROM planes WHERE name='" . $newplane . "' limit 1";
    $result = mysql_query($sql, $db);
    if ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
      // Found it
      $plid = $row["plid"];
      $newplane = "OK";
    } else {
      $sql = "INSERT INTO planes(name) VALUES('" . $newplane . "')";
      mysql_query($sql, $db) or die('0;Adding new plane failed');
    }
  }
 }

switch($param) {
 case "ADD":
   $verb = "add";
   $sql = sprintf("INSERT INTO flights(uid, src_apid, src_time, dst_apid, duration, distance, registration, code, seat, seat_type, class, reason, plid, alid, trid) VALUES (%s, %s, '%s', %s, '%s', %s, '%s', '%s', '%s', '%s', '%s', '%s', %s, %s, %s)",
	       $uid, $src_apid, $src_date, $dst_apid, $duration, $distance, $registration, $number, $seat, $seat_type, $class, $reason, $plid, $alid, $trid);
   break;

 case "EDIT":
   $verb = "chang";
   $sql = sprintf("UPDATE flights SET src_apid=%s, src_time='%s', dst_apid=%s, duration='%s', distance=%s, registration='%s', code='%s', seat='%s', seat_type='%s', class='%s', reason='%s', plid=%s, alid=%s, trid=%s WHERE fid=%s",
		  $src_apid, $src_date, $dst_apid, $duration, $distance, $registration, $number, $seat, $seat_type, $class, $reason, $plid, $alid, $trid, $fid);
   break;

   // uid is strictly speaking unnecessary, but just to be sure...
 case "DELETE":
   $sql = sprintf("DELETE FROM flights WHERE uid=%s AND fid=%s", $uid, $fid);
   break;
 }

mysql_query($sql, $db) or die ('0;Altering plane to DB failed: ' . $sql);
if($param == "DELETE") {
  printf("3;Flight deleted.");
} else {

  if($newplane == "OK") {
    printf("2;Flight and plane %sed.", $verb);
  } else {
    printf("1;Flight %sed.", $verb);
  }
}

?>
