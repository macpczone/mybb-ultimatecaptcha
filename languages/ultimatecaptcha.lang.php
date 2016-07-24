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
if (!defined('IN_MYBB'))
  die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");

// Error messages
$l['ultimatecaptcha_error_no_hash']       = 'The hash of this CAPTCHA was not found.';
$l['ultimatecaptcha_error_no_ans']        = 'You did not answer the CAPTCHA.';
$l['ultimatecaptcha_error_invalid_hash']  = 'The hash of this CAPTCHA is invalid.';
$l['ultimatecaptcha_error_invalid_fmt']   = 'The CAPTCHA code you entered looks incorrect.';
$l['ultimatecaptcha_error_invalid_ans']   = 'The CAPTCHA code you entered is incorrect.';
$l['ultimatecaptcha_error_too_fast']      = 'You filled in the form too quickly!';

// Ultimate CAPTCHA
$l['ultimatecaptcha_title']           = 'Ultimate Image Verification';
$l['ultimatecaptcha_desc']            = 'Please enter the text that you can see in the image into the text box below it. This process is used to prevent automated signups.';
