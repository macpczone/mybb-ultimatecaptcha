<?php
/**
 * MyBB Ultimate CAPTCHA (development version)
 *
 * Author:    Tony Campbell < http://www.endtimediscussions.org >
 * Homepage:  < http://www.endtimediscussions.org/ultimatecaptcha >
 * License:   GPL-2+
 * Compatible with (hopefully) MyBB 1.6.x
 */

// Disallow direct access to this file for security reasons
if(!defined("IN_MYBB"))
  die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");

// For ultimatecaptcha_gets_num()
require_once MYBB_ROOT . 'inc/plugins/ultimatecaptcha.php';

function task_ultimatecaptcha($task) {
  global $db;

  // Delete old captcha entries, those older than one hour
  $cut = TIME_NOW - 60 * 60 * ultimatecaptcha_gets_num('cleanupinterval', 1);
  $db->delete_query("captcha_ultimatecaptcha", "dateline < '{$cut}'");
}

