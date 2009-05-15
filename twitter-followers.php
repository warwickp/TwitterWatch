<?
//settings
$mydate = date("m.d.y-H-i-s");
$login = "twitterusername:twitterpassword"; 		//Your Twitter account, yo. example "tim:secret"
$log_location = "/home/user/TwitterWatch/logs"; 	//create this beforehand natch
$current_log = "$log_location/current_log.txt";
$current_count = "$log_location/current_count.txt";
$prev_log = "$log_location/prev_log.txt";
$prev_count = "$log_location/prev_count.txt";
$running_log = "$log_location/running.txt"; 		//this file will have the running log of activity
$lockfile = "$log_location/locked";

//Twitter API URLs
$followers = "http://twitter.com/followers/ids.xml";
$follower_details = "http://twitter.com/statuses/followers.xml";

//Start
if (file_exists($lockfile)) {
	echo "**** LOCKED! Exiting";
	exit;
    }
else{
	touch($lockfile);
}

//get follower count first
$fol = curl_init();
curl_setopt($fol, CURLOPT_URL, $followers);
curl_setopt($fol, CURLOPT_USERPWD, $login);
curl_setopt($fol, CURLOPT_RETURNTRANSFER, TRUE);
$twi = curl_exec($fol);
$myfollowers = new SimpleXMLElement($twi);

$folcount = count($myfollowers);
curl_close($fol);
$folpages = floor($folcount / 100) + 1;

//store followercount
$followercount = "$folcount";

//get follower details
for ( $counter = 1; $counter <= $folpages; $counter += 1) {

	$tw_api_url = "$follower_details?page=$counter";
	#echo "doing $tw_api_url \n";

	$foldet = curl_init();
	curl_setopt($foldet, CURLOPT_URL, $tw_api_url);
	curl_setopt($foldet, CURLOPT_USERPWD, $login);
	curl_setopt($foldet, CURLOPT_RETURNTRANSFER, TRUE);
	$tw = curl_exec($foldet);
	$follower_info = new SimpleXMLElement($tw);

	foreach ($follower_info->user as $fol_user) {
		$fol_sn = $fol_user->screen_name;
		//$fol_fn = $fol_user->name;
		$followerlog .= "$fol_sn \n";
	}

	curl_close($foldet);
}

//rename current logs to previous logs
rename("$current_log", "$prev_log");
rename("$current_count", "$prev_count");

//now write new logs
$myFile = "$current_log";
$fh = fopen($myFile, 'w') or die("can't open file");
fwrite($fh, $followerlog);
fclose($fh);

$myFile = "$current_count";
$fh = fopen($myFile, 'w') or die("can't open file");
fwrite($fh, $followercount);
fclose($fh);

//lets compare
$last_count = file_get_contents("$prev_count");
$last_followers = file_get_contents("$prev_log");

if ($followercount == $last_count){
	$msg = "($mydate) $followercount - NO CHANGE \n";
}
elseif ($followercount > $last_count){
	$foll_diff = $followercount -  $last_count;

	//who's new?
	$last_followers_array = explode("\n", trim($last_followers));
	sort($last_followers_array);
	$current_followers_array = explode("\n", trim($followerlog));
	sort($current_followers_array);

	$follower_diff = array_diff($current_followers_array, $last_followers_array);
	$restring = implode("\n", $follower_diff);

	$msg = "($mydate) $followercount GAINED $foll_diff, previously: $last_count ($restring) \n";
}
elseif ($followercount < $last_count){
	$foll_diff = $last_count - $followercount;

	//who's gone?
	$last_followers_array = explode("\n", trim($last_followers));
	sort($last_followers_array);
	$current_followers_array = explode("\n", trim($followerlog));
	sort($current_followers_array);

	$follower_diff = array_diff($last_followers_array, $current_followers_array);
	$restring = implode("\n", $follower_diff);

	$msg = "($mydate) $followercount LOST $foll_diff, previously: $last_count ($restring) \n";
}

//write msg to running_log
$myFile = "$running_log";
$fh = fopen($myFile, 'a') or die("can't open file");
fwrite($fh, $msg);
fclose($fh);

//unlock
unlink($lockfile);

?>
