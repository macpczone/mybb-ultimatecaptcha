<?php
/**
 * Ultimate Captcha
 * Copyright 2013 Michael Campbell
 *
 * Website: http://www.endtimediscussions.org
 * License: GPL-2+
 *
 */

define("IN_MYBB", 1);
define("NO_ONLINE", 1);
define('THIS_SCRIPT', 'ucaptcha.php');
define("ALLOWABLE_PAGE", 1);

require_once '../../../global.php';
include(MYBB_ROOT . 'inc/plugins/ultimatecaptcha/ucfunctions.php');

if(isset($_GET['delete'])){
     if($_GET['delete'] == "doit"){
     	ultimatecaptcha_delete_used_spares();
     	exit;
     }
}

$sparefile = '';
$spares = array();

if($mybb->input['imagehash'] == "test")
{
	$imagestring = "MyBB";
}
elseif($mybb->input['imagehash'])
{
	$query = $db->simple_select("captcha_ultimatecaptcha", "*", "HEX(hash) ='".$db->escape_string(strval($_GET['imagehash']))."'", array("limit" => 1));
	$regimage = $db->fetch_array($query);
	if ($regimage['spareused']) {
//	if (1) {
	  $query = $db->simple_select("captcha_ultimatecaptcha_spares", "*", "HEX(hash) ='".$db->escape_string(strval($_GET['imagehash']))."'", array("limit" => 1));
	  $spares = $db->fetch_array($query);
	  $sparefile = $spares['filename'];
	  $imagestring = $spares['answer'];
	  }
	 else {
	$imagestring = $regimage['answer'];
	$sparefile = '';
	}
}
else
{
	echo "imagestring = ". $imagestring;
	echo "<br>imagehash = ". $imagehash;
	return false;
}
//echo "scrolltext " . $_GET['imagehash'];
//echo "<br>imagehash =" . $mybb->input['imagehash'];
//echo "<br>regimage =<pre>- " . var_dump($spares) . " -</pre>";
//echo "<br>imagehash =" . $imagestring;
//exit;


if (!$sparefile) {
	echo renderagif($imagestring);
}
else {
    $renderpath = $mybb->settings['ultimatecaptcha_renderpath'];
	$output = $renderpath . $sparefile;
	readfile($output);
}
exit;

?>
