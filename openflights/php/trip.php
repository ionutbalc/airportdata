<?php
session_start();

$type = $HTTP_POST_VARS["type"];
$name = $HTTP_POST_VARS["name"];
$url = $HTTP_POST_VARS["url"];
$trid = $HTTP_POST_VARS["trid"];
$privacy = $HTTP_POST_VARS["privacy"];

$uid = $_SESSION["uid"];
if(!$uid or empty($uid)) {
  printf("0;Your session has timed out, please log in again.");
  exit;
}

$db = mysql_connect("localhost", "openflights");
mysql_select_db("flightdb",$db);

// Load data for existing trip
if($type == "LOAD") {
  $sql = "SELECT * FROM trips WHERE trid=" . mysql_real_escape_string($trid) . " AND uid=" . mysql_real_escape_string($uid);
  $result = mysql_query($sql, $db);
  if ($row = mysql_fetch_array($result)) {
    printf("1;%s;%s;%s;%s", $row["trid"], $row["name"], $row["url"], $row["public"]);
  } else {
    printf("0;Could not load trip data.");
  }
  exit;
}

// Create new trip or edit existing one
if($type == "NEW") {
  $sql = sprintf("INSERT INTO trips(name,url,public,uid) VALUES('%s','%s','%s', %s)",
		 mysql_real_escape_string($name),
		 mysql_real_escape_string($url),
		 mysql_real_escape_string($privacy),
		 $uid);
} else {
  $sql = sprintf("UPDATE trips SET name='%s', url='%s', public='%s' WHERE uid=%s AND trid=%s",
		 mysql_real_escape_string($name),
		 mysql_real_escape_string($url),
		 mysql_real_escape_string($privacy),
		 $uid,
		 mysql_real_escape_string($trid));
}
mysql_query($sql, $db) or die ('0;Operation on trip ' . $name . ' failed: ' . $sql . ', error ' . mysql_error());

if($type == "NEW") {
  $trid = mysql_insert_id();
  printf("1;%s;Trip successfully created", $trid);
} else {
  printf("2;%s;Trip successfully edited.", $trid);
}
?>
