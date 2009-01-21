<?php
// Facebook app for OpenFlights

require_once 'php/facebook.php';
require_once 'keys.php';
require_once 'profile.php';

// appapikey,appsecret must be defined in keys.php
$facebook = new Facebook($appapikey, $appsecret);
$fbuid = $facebook->require_login();
$db = mysql_connect("localhost", "openflights");
mysql_select_db("flightdb",$db);

// Clear prefs, DB if user passes in reset=true, so they can reconfig
if($_REQUEST["reset"] == "true") {
  $facebook->api_client->data_setUserPreference(1, 0);
  $sql = "DELETE FROM facebook WHERE fbuid=$fbuid";
  $result = mysql_query($sql, $db);
  print "<p><b>Account reset.</b></p>";
}

// Has the user configured their OpenFlights name?
$ofname = $facebook->api_client->data_getUserPreference(1);
if(! $ofname || $ofname == "") {
  // Nope, did they just submit it?
  $ofname = $_REQUEST["ofname"];
  if($ofname) {
    // Yes, check it
    $sql = "SELECT public, uid FROM users WHERE name='" . $ofname . "'";
    $result = mysql_query($sql, $db);
    if($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
      if($row["public"] == "N") {
	die("Sorry, $ofname's profile is set to 'Private'.  Please go to <a href='http://openflights.org/html/settings.html' target='_blank'>Settings</a>, change <b>Privacy</b> to 'Public' or 'Open', and try again.");
      }
    } else {
      die("Sorry, couldn't find <b>$ofname</b> at OpenFlights.  Please check the spelling, hit 'Back' and try again.");
    }

    echo("<p><b>Account found!</b>  Setting up your profile...</p>");

    // Looking good, save to Facebook and our internal table
    $facebook->api_client->data_setUserPreference(1, $ofname);

    $uid = $row["uid"];
    $sql = sprintf("INSERT INTO facebook(uid,fbuid,updated) VALUES(%s,%s,DATE_SUB(NOW(), INTERVAL 1 DAY))", $uid, $fbuid);
    $result = mysql_query($sql, $db);
    if(! $result || mysql_affected_rows() != 1) {
      die('<b>Uh-oh, an error occurred</b>.  Please send the following message to <i>support@openflights.org</i>:<br>' . $sql);
    }
    echo("<p>Done!  Here's a preview:</p>");
    
  } else {
    // No, ask for it
    echo "<form requirelogin=\"1\">";
    echo "<h2>Configuration</h2>";
    echo "<p>Thanks for trying out the OpenFlights Facebook application!  Hooking it up to your OpenFlights account is an easy three-step process.</p>";
    echo "<p>To start, please enter your username on OpenFlights: <input type='text' name='ofname' value='$ofname' /></p>";
    echo "<input type='submit' value='Submit' />";

    echo "<p>This application requires an <a target='_blank' href='http://openflights.org'>OpenFlights</a> account.</b>  If you don't have one already, you can <a target='_blank' href='http://openflights.org/html/signup.html'>sign up</a> for one now.</p>";

    echo "</form>";
    return;
  }
}

?>

<fb:tabs>
	<fb:tab-item href="http://apps.facebook.com/openflights/index.php" title="Home" selected="true"/>;
	<fb:tab-item href="http://apps.facebook.com/openflights/invite.php" title="Invite Friends"/>;
</fb:tabs>

<?php
// Check if we already have an infinite session (offline access) key for this user
$sql = "SELECT uid, sessionkey FROM facebook WHERE fbuid=" . $fbuid;
$result = mysql_query($sql, $db);
if($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
  $uid = $row["uid"];
  $session = $row["sessionkey"];
}

$session_key = $_POST["fb_sig_session_key"];
$session_expiry = $_POST["fb_sig_expires"];
// print "Live session key [" . $session_key . "], expiry [" . $session_expiry . "], DB session [" . $session . "]<br>";
// Do we now have a new infinite key?
if(! $session && $session_expiry == "0") {
  $sql = "UPDATE facebook SET sessionkey='" . $session_key . "' WHERE fbuid=" . $fbuid;
  if($result = mysql_query($sql, $db)) {
    $session = $session_key;
    print "<p><b>Thank you!</b> OpenFlights will now send notifications to your Facebook feed and refresh your profile automatically when you add new flights.</p>";
  } else {
    die('<b>Uh-oh, an error occurred</b>.  Please send the following message to <i>support@openflights.org</i>:<br>' . $sql);
  }
}

// Update the user's profile box
$profile_box = get_profile($db, $uid, $fbuid, $ofname);
echo "<br><div style='background-color: #f7f7f7;border: 1px solid #cccccc;color: #333333;padding: 10px; width: 184px;'>$profile_box</div>";
$facebook->api_client->profile_setFBML(null, $fbuid, null, null, null, $profile_box);
?>
<?php
if(! $session) {
?>
  <p><b>Step 1</b>: Click the link below to allow OpenFlights to send notifications to your Facebook feed and refresh your stats automatically when you add new flights.  This is <i>optional but recommended</i>; otherwise, you'll have to manually refresh your stats.</p> 
  <fb:prompt-permission perms="offline_access"> Grant permission for offline updates </fb:prompt-permission>
  <p><b>Step 2</b>: <i>After</i> granting permission, click Refresh below to activate automatic updating.</p>
  <form requirelogin="1">
    <input type='submit' value='Refresh' /><br/>
  </form>
<?php
} else {
  print "<p><i>Automatic refreshing active: any new flights will be updated to your profile and feed hourly.</i></p>";
}
?>

<fb:if-section-not-added section="profile">
<p><b>Step 3</b>: Click the button below to add the OpenFlights box to your Facebook profile.</p>
<div class="section_button"><fb:add-section-button section="profile"/></div>
<br/>
</fb:if-section-not-added>

