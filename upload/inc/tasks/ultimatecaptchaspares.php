<?php
/**
 * MyBB Ultimate CAPTCHA
 *
 * Author:    Michael Campbell < http://www.endtimediscussions.org >
 * Homepage:  < http://www.endtimediscussions.org/ultimatecaptcha >
 * License:   GPL-2+
 * Compatible with (hopefully) MyBB 1.6.x
 */

// Disallow direct access to this file for security reasons
if(!defined("IN_MYBB"))
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");

// For ultimatecaptcha_gets_num()
require_once MYBB_ROOT . 'inc/plugins/ultimatecaptcha.php';

function task_ultimatecaptchaspares($task) {
  global $db, $mybb;

//  error_log("Spares are about to be deleted");
  // Delete used captcha image files, those older than one hour
	ultimatecaptcha_delete_used_spares();
//  error_log("Spares should have been deleted");
//  error_log($mybb->settings['ultimatecaptcha_background']);
	if ($mybb->settings['ultimatecaptcha_background']) {
    $renderpath = $mybb->settings['ultimatecaptcha_renderpath'];
    $lock = fopen($renderpath . 'pidfile', 'c+');
    if ($lock) {
      $pid = fgets($lock);
//      error_log("Read the lock file");
      if (file_exists( "/proc/$pid" ) && $pid > 0){
//        error_log("Gifrender already running and pid is $pid");
        return;
      }
      else {
        $args = array($renderpath, MYBB_ROOT);
        $cmd = MYBB_ROOT . 'inc/plugins/ultimatecaptcha/gifrender.php ';
//        $command = 'php ' . MYBB_ROOT . 'inc/plugins/ultimatecaptcha/gifrender.php ' . $renderpath . ' ' . MYBB_ROOT;
//        exec($command);
        $exec_cmd = ((php_uname('s') == 'FreeBSD') ? 'daemon' : 'nohup');
        $exec_cmd .= ' /usr/bin/env bash -c ';
        $exec_cmd .= escapeshellarg('exec 0<&-; exec 1> /dev/null; exec 2> /dev/null; eval exec {3..255}\>\&-; /usr/bin/env php ' . $cmd . ' ' . join(' ', $args));
        exec($exec_cmd);
      }
    }
    else
      error_log("Lock file not opened");
  }
}
