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
$l['ultimatecaptcha_error_no_hash']       = '您没有发送验证码的hash。';
$l['ultimatecaptcha_error_no_ans']        = '您没有输入验证码。';
$l['ultimatecaptcha_error_invalid_hash']  = '您发送的验证码hash不正确。';
$l['ultimatecaptcha_error_invalid_fmt']   = '您的验证码回答格式不正确。';
$l['ultimatecaptcha_error_invalid_ans']   = '您的验证码回答不正确。';
$l['ultimatecaptcha_error_too_fast']      = '您填表太快了！';

// Ultimate CAPTCHA
$l['ultimatecaptcha_title']           = 'Enter me';
$l['ultimatecaptcha_desc']            = 'and me';
