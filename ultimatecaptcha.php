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
if (!defined('IN_MYBB'))
  die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");

// Shorthand name for PluginLibrary
if (!defined('PLUGINLIBRARY'))
  define('PLUGINLIBRARY', MYBB_ROOT . 'inc/plugins/pluginlibrary.php');


// Tell MyBB when to run the hooks
// $plugins->add_hook("hook name", "function name");
$plugins->add_hook('member_register_start', 'ultimatecaptcha_display');
$plugins->add_hook('datahandler_user_validate', 'ultimatecaptcha_validate');

/**
 * Basic information about the plugin.
 */
function ultimatecaptcha_info() {
  global $plugins_cache;

  $info = array(
    "name"          => "Ultimate CAPTCHA",
    "description"   => "MyBB port of ETD's Ultimate CAPTCHA.",
    "website"       => "http://www.endtimediscussions.org/ultimatecaptcha",
    "author"        => "Michael Campbell",
    "authorsite"    => "http://www.endtimediscussions.org",
    "version"       => "0.3",
    "guid"          => "",
    "compatibility" => "16"
  );

  // Display some extra information when installed and active.
  if(ultimatecaptcha_is_installed()
      && $plugins_cache['active']['ultimatecaptcha']) {
    $url = ultimatecaptcha_get_settings_url('ultimatecaptcha');
    if ($url)
      $info["description"] .= " | <a href=\"{$url}\">Edit settings</a>";
  }

  return $info;
}

/**
 * Install function for the plugin.
 *
 * Creates <code>captcha_ultimatecaptcha</code> table in the database. Add
 * cleanup task.
 */
function ultimatecaptcha_install() {
  global $db, $PL, $cache;

  // PluginLibrary dependency check
  if (!file_exists(PLUGINLIBRARY)) {
    flash_message("The selected plugin could not be installed because <a href=\"http://mods.mybb.com/view/pluginlibrary\">PluginLibrary</a> is missing.", "error");
    admin_redirect("index.php?module=config-plugins");
  }

  // Load PluginLibrary
  $PL or require_once PLUGINLIBRARY;

  // PluginLibrary version check
  if ($PL->version < 11) {
    flash_message("PluginLibrary is too old.", "error");
    admin_redirect("index.php?module=config-plugins");
  }

  // Create table
  $tbl_namea = TABLE_PREFIX . 'captcha_ultimatecaptcha';
  $db->write_query("create table `$tbl_namea` (
    `hash`      binary(20)    not null,
    `ip`        int unsigned  not null  default '0',
    `dateline`  bigint(30)    not null  default '0',
    `spareused` tinyint(1)    not null  default '0',
    `answer`    char(255)     not null  default '',
    primary key (`hash`, `ip`)
  );");

  $tbl_nameb = TABLE_PREFIX . 'captcha_ultimatecaptcha_spares';
  $db->write_query("create table `$tbl_nameb` (
    `hash`      binary(20)    not null,
    `filename`  char(20)      not null,
    `used`      tinyint(1)    not null  default '0',
    `answer`    char(255)     not null  default '',
    primary key (`hash`)
  );");

  // Add index to dateline to accelerate CAPTCHA cleanup
  $db->write_query("alter table `$tbl_namea` add index (`dateline`);");
  $db->write_query("alter table `$tbl_nameb` add index (`hash`);");

  // Insert task
  ultimatecaptcha_task_add(array(
    "title"       => 'Ultimate CAPTCHA Cleanup',
    "description" => 'Clean up old CAPTCHA entries.',
    "file"        => 'ultimatecaptcha',
    "minute"      => '0',
  ));

  ultimatecaptcha_task_add(array(
    "title"       => 'Ultimate CAPTCHA Spares Cleanup',
    "description" => 'Clean up old CAPTCHA spare image entries and check the rendering process.',
    "file"        => 'ultimatecaptchaspares',
    "minute"      => '0,10,20,30,40,50',
  ));
}

/**
 * Uninstall function for the plugin.
 *
 * Drops <code>captcha_ultimatecaptcha</code> table in the database.
 */
function ultimatecaptcha_uninstall() {
  global $PL, $db, $mybb;
  $PL or require_once PLUGINLIBRARY;

  $renderpath = $mybb->settings['ultimatecaptcha_renderpath'];
  // Drop template and stylesheet
  $PL->templates_delete('ultimatecaptcha');
  $PL->stylesheet_delete('ultimatecaptcha');

  // Drop ultimatecaptcha table
  $tbl_name = TABLE_PREFIX . 'captcha_ultimatecaptcha';
  $db->write_query("drop table `$tbl_name`;");
  $tbl_name = TABLE_PREFIX . 'captcha_ultimatecaptcha_spares';
  $db->write_query("drop table `$tbl_name`;");

  // Delete settings
  $PL->settings_delete('ultimatecaptcha');

  // Drop task
  ultimatecaptcha_task_drop('ultimatecaptcha', 'Ultimate CAPTCHA Cleanup');
  ultimatecaptcha_task_drop('ultimatecaptchaspares', 'Ultimate CAPTCHA Spares Cleanup');
  chdir($renderpath);
  $mask = "????/*";
  array_map( "unlink", glob( $mask ) );
  $mask = "????";
  array_map( "rmdir", glob( $mask ) );
  unlink('gifrender.log');
  unlink('pidfile');
  unlink('filescount');
}

/**
 * Check whether the plugin is installed, by checking the presense of the
 * database table.
 */
function ultimatecaptcha_is_installed() {
  global $db;

  return (bool) ($db->table_exists("captcha_ultimatecaptcha"));
}

/**
 * Activate function of the plugin.
 */
function ultimatecaptcha_activate() {
  global $PL, $db, $lang;
  $PL or require_once PLUGINLIBRARY;

  // Create template
  $PL->templates('ultimatecaptcha',
    'Ultimate CAPTCHA Plugin',
    array('captcha' => <<<EOF
<br />
<fieldset class="trow2">
<legend><strong>{\$ultimatecaptcha_title}</strong></legend>
<table cellspacing="0" cellpadding="{\$theme['tablespace']}">
<tr>
<td><span class="smalltext">{\$ultimatecaptcha_desc}</span></td>
</tr>
<tr><td align="center"><div class="ultimatecaptcha-captcha{\$ultimatecaptcha_classes}"><img src="inc/plugins/ultimatecaptcha/ucaptcha.php?imagehash={\$ultimatecaptcha_hash}" /></div><br /><span style="color: red;" class="smalltext">{\$lang->verification_subnote}</span>
</td>
</tr>
<tr>
<td align="center"><input type="text" class="textbox" name="ultimatecaptcha-answer" value="" /><input type="hidden" name="ultimatecaptcha-hash" value="{\$ultimatecaptcha_hash}" /></td>
</tr>
</table>
</fieldset>
EOF
    )
  );

  // Add stylesheet
  $PL->stylesheet('ultimatecaptcha', <<<EOF
.ultimatecaptcha-captcha {
  font-family: monospace;
}

.ultimatecaptcha-captcha .ultimatecaptcha-css-wrap {
  display: inline-block;
  width: 15px;
}

.ultimatecaptcha-captcha .ultimatecaptcha-css-wrap .ultimatecaptcha-css-l, .ultimatecaptcha-captcha .ultimatecaptcha-css-wrap .ultimatecaptcha-css-r {
  display: inline-block;
  width: 7px;
}

.ultimatecaptcha-captcha .ultimatecaptcha-css-wrap .ultimatecaptcha-css-l {
  float: left;
}

.ultimatecaptcha-captcha .ultimatecaptcha-css-wrap .ultimatecaptcha-css-r {
  float: right;
}
EOF
  );

  // Add settings
  $PL->settings('ultimatecaptcha', 'Ultimate CAPTCHA',
    'Settings of the Ultimate CAPTCHA MyBB plugin.',
    array(
      'enabled' => array(
        'title' => 'Enable Ultimate CAPTCHA',
        'description' => '',
        'value' => 1,
      ),
      'background' => array(
        'title' => 'Enable Ultimate CAPTCHA background renderer',
        'description' => 'Enable the background renderer.',
        'value' => 0,
      ),
      'minresptime' => array(
        'title' => 'Minimum acceptable response time',
        'description' => 'The minimum interval after we send out a CAPTCHA before a response of it is considered acceptable, in seconds.',
        'optionscode' => 'text',
        'value' => 10,
      ),
      'num' => array(
        'title' => 'CAPTCHA length',
        'description' => 'The number of characters in the CAPTCHA.',
        'optionscode' => 'text',
        'value' => '5',
      ),
      'characters' => array(
        'title' => 'CAPTCHA characters used',
        'description' => 'The allowed characters for use in the CAPTCHA.',
        'optionscode' => 'text',
        'value' => '23456789abcdefghkmnpqrstuvwxyz',
      ),
      'width' => array(
        'title' => 'CAPTCHA width',
        'description' => 'The width in pixels of the CAPTCHA.',
        'optionscode' => 'text',
        'value' => '80',
      ),
      'height' => array(
        'title' => 'CAPTCHA height',
        'description' => 'The height in pixels of the CAPTCHA.',
        'optionscode' => 'text',
        'value' => '40',
      ),
      'movein' => array(
        'title' => 'CAPTCHA how far the characters move in from the edge',
        'description' => 'This number determines how far the characters move in from the edge of the image. Higher numbers result in larger file sizes, but will allow more time to see the characters at the end.',
        'optionscode' => 'text',
        'value' => '10',
      ),
      'mode' => array(
        'title' => 'CAPTCHA mode',
        'description' => 'Choose the mode of the CAPTCHA, rendering is a lot slower in ultimate mode.',
        'optionscode' => "radio\n0=Normal\n1=Ultimate",
      ),
      'stylenum' => array(
        'title' => 'CAPTCHA background style array size',
        'description' => 'This value determines the size of the array that generates the background pattern of the CAPTCHA. Try setting this to the width of the CAPTCHA + 1 for an interesting effect.',
        'optionscode' => 'text',
        'value' => '30',
      ),
      'cnoise' => array(
        'title' => 'CAPTCHA character noise level (0 - 255)',
        'description' => 'Ultimate mode only. This number determines how much noise is inside the characters. If you set it close to zero then the characters will disappear into the outer noise.',
        'optionscode' => 'text',
        'value' => '240',
      ),
      'contrast' => array(
        'title' => 'CAPTCHA character contrast (0 - 255)',
        'description' => 'Ultimate mode only. This number determines how bright the noise is inside the characters. The characters are at their brightest at 255.',
        'optionscode' => 'text',
        'value' => '225',
      ),
      'charcolours' => array(
        'title' => 'CAPTCHA charachter colour',
        'description' => 'The colour of the CAPTCHA characters, lines, elipses and squares. Only use Grey if your users have a high martial arts level',
        'optionscode' => "radio\n0=Black\n1=White\n2=Grey",
      ),
      'lines' => array(
        'title' => 'CAPTCHA number of lines',
        'description' => 'The number of lines in the CAPTCHA.',
        'optionscode' => 'text',
        'value' => '4',
      ),
      'elipses' => array(
        'title' => 'CAPTCHA number of elipses',
        'description' => 'The number of elipses in the CAPTCHA.',
        'optionscode' => 'text',
        'value' => '5',
      ),
      'squares' => array(
        'title' => 'CAPTCHA number of squares',
        'description' => 'The number of squares in the CAPTCHA.',
        'optionscode' => 'text',
        'value' => '3',
      ),
      'cleanupinterval' => array(
        'title' => 'CAPTCHA clean up interval',
        'description' => 'The time we wait before a CAPTCHA expires, in hours.',
        'optionscode' => 'text',
        'value' => 1,
      ),
      'renderpath' => array(
        'title' => 'Background renderer storage path',
        'description' => 'The path to the storage directory of the background renderer.',
        'optionscode' => 'text',
        'value' => '/var/customers/webs/michael/etdgifs/',
      ),
      'dirin' => array(
        'title' => 'Background renderer files in directory',
        'description' => 'The maximum number of files in each subdirectory.',
        'optionscode' => 'text',
        'value' => '1000',
      ),
      'sparesmax' => array(
        'title' => 'Background renderer max spare files',
        'description' => 'The maximum number of files rendered at any time.',
        'optionscode' => 'text',
        'value' => '7000',
      ),
      'sparesmin' => array(
        'title' => 'Background renderer min spare files',
        'description' => 'The number of files left where the renderer starts again.',
        'optionscode' => 'text',
        'value' => '4000',
      ),
    ));

  // Add CAPTCHA to register page template
  require_once MYBB_ROOT . 'inc/adminfunctions_templates.php';
  find_replace_templatesets("member_register", '#{\$regimage}#', '{\$ultimatecaptcha}{\$regimage}');

  // Enable task
  ultimatecaptcha_task_enable('ultimatecaptcha');
  ultimatecaptcha_task_enable('ultimatecaptchaspares');
}

/**
 * Deactivate function of the plugin.
 */
function ultimatecaptcha_deactivate() {
  global $db;
  $PL or require_once PLUGINLIBRARY;

  // Deactivate stylesheet
  $PL->stylesheet_deactivate('ultimatecaptcha');

  // Remove CAPTCHA from register page template
  require_once MYBB_ROOT . 'inc/adminfunctions_templates.php';
  find_replace_templatesets("member_register", '#{\$ultimatecaptcha}{\$regimage}#', "{\$regimage}");

  // Disable task
  ultimatecaptcha_task_disable('ultimatecaptcha');
  ultimatecaptcha_task_disable('ultimatecaptchaspares');
	$db->update_query("settings", array('value' => 0), "name='ultimatecaptcha_background'");
}

/**
 * Get URL of a setting group with the specific name.
 */
function ultimatecaptcha_get_settings_url($name) {
  global $db;

  $name = $db->escape_string($name);
  $query = $db->simple_select('settinggroups', 'gid', "name = '{$name}'");
  $result = $db->fetch_array($query);
  if ($result)
    return "index.php?module=config-settings&action=change&gid={$result['gid']}";
  else
    return '';
}

/**
 * Add a task.
 *
 * Code stolen from MyBB itself.
 */
function ultimatecaptcha_task_add($task) {
  require_once MYBB_ROOT . 'inc/functions_task.php';

  global $db, $cache;

  // Merge default values
  $task_def = array(
    'title'       => '',
    'description' => '',
    'file'        => '',
    'minute'      => '*',
    'hour'        => '*',
    'day'         => '*',
    'month'       => '*',
    'weekday'     => '*',
    'enabled'     => 0,
    'logging'     => 1,
  );
  $task = array_merge($task_def, $task);

  if (!$task['file'] || !$task['title'])
    return false;

  // If there's a task with the same title or filename, drop it
  ultimatecaptcha_task_drop($task['file'], $task['title']);

  // Escape all the things in the task
  $task = array(
    'title'       => $db->escape_string($task['title']),
    'description' => $db->escape_string($task['description']),
    'file'        => $db->escape_string($task['file']),
    'minute'      => $db->escape_string($task['minute']),
    'hour'        => $db->escape_string($task['hour']),
    'day'         => $db->escape_string($task['day']),
    'month'       => $db->escape_string($task['month']),
    'weekday'     => $db->escape_string($task['weekday']),
    'enabled'     => (int) $task['enabled'],
    'logging'     => (int) $task['logging'],
  );

  // Fill nextrun
  $task['nextrun'] = fetch_next_run($task);

  // Insert, and update cache
  $db->insert_query("tasks", $task);
  $cache->update_tasks();

  return true;
}

/**
 * Drop a task.
 *
 * Code stolen from MyBB itself.
 */
function ultimatecaptcha_task_drop($file, $title) {
  global $db, $cache;

  $file = $db->escape_string($file);
  $title = $db->escape_string($title);

  $db->delete_query('tasks', "file = '$file' or title = '$title'");

  $cache->update_tasks();
}

/**
 * Enable a task.
 *
 * Code stolen from MyBB itself.
 */
function ultimatecaptcha_task_enable($file) {
  global $db;

  $file = $db->escape_string($file);
  $db->update_query('tasks', array('enabled' => 1), "file = '$file'");
}

/**
 * Disable a task.
 *
 * Code stolen from MyBB itself.
 */
function ultimatecaptcha_task_disable($file) {
  global $db;

  $file = $db->escape_string($file);
  $db->update_query('tasks', array('enabled' => 0), "file = '$file'");
}

/**
 * Get a numeric value from settings.
 */
function ultimatecaptcha_gets_num($key, $default, $maximum = NULL, $allowzero = NULL) {
  global $mybb;

  $num = 0;
  if (isset($mybb->settings['ultimatecaptcha_' . $key]))
    $num = (int) $mybb->settings['ultimatecaptcha_' . $key];
  if (($num <=0 && $allowzero == NULL) || (isset($maximum) && $maximum > 0 && $num > $maximum))
    $num = $default;

  return $num;
}

/**
 * Insert a record of a CAPTCHA to database.
 */
function ultimatecaptcha_dbinsert($captcha) {
  global $db;

  if ('' === $captcha['answer'])
    return;

  $ip = ip2long(get_ip());
  // Use replace_query() here just in case we have a conflict.
  $db->replace_query('captcha_ultimatecaptcha', array(
    "hash"      => $db->escape_string($captcha['hash_raw']),
    "ip"        => $ip,
    "dateline"  => TIME_NOW,
    "spareused" => $captcha['spareused'],
    "answer"    => $db->escape_string(strtolower($captcha['answer'])),
  ));
}
/**
 * Drop the record of a CAPTCHA from database.
 */
function ultimatecaptcha_dbdrop($hash) {
  global $db;

  $hash = $db->escape_string($hash);
  $db->delete_query("captcha_ultimatecaptcha", "hash = X'{$hash}';");
}

/**
 * Validate the record of a CAPTCHA.
 *
 * @return the dateline of the matched CAPTCHA record, 0 if not found
 */
function ultimatecaptcha_dbvalidate($hash, $answer) {
  global $db;

  $hash = $db->escape_string($hash);
  $answer = $db->escape_string(strtolower($answer));
  $ip = ip2long(get_ip());
  $query = $db->simple_select("captcha_ultimatecaptcha", "*",
      "hash = X'{$hash}' and ip = '{$ip}' and answer = '{$answer}'");
  $result = $db->fetch_array($query);
  return (empty($result['dateline']) ? 0: $result['dateline']);
}

/**
 * Display callback function.
 */
function ultimatecaptcha_display() {
  global $templates, $ultimatecaptcha, $mybb, $lang, $db;

  // Quit if it's not enabled
  if (!$mybb->settings['ultimatecaptcha_enabled'])
    return;

  // Get the CAPTCHA
  $arr_captcha = array('challenge' => '', 'answer' => '');
    $opts = array(
      'num' => ultimatecaptcha_gets_num('num', 5));
    // Special options for particular CAPTCHA types

  $query = $db->simple_select("captcha_ultimatecaptcha_spares", "*",
      "used = '0'", array("limit" => 1));
	$spares = $db->fetch_array($query);
	if ($spares['hash']) {
	  $arr_captcha['hash'] = sha1($spares['answer']);
	  $arr_captcha['hash_raw'] = sha1($spares['answer'], true);
	  $arr_captcha['answer'] = $spares['answer'];
	  $arr_captcha['spareused'] = 1;
	  $db->update_query('captcha_ultimatecaptcha_spares', array(
 	   "used" => '1',
	  ), "hash = X'{$arr_captcha['hash']}'");
	 }
	 else {
  // Insert to database
    $characters = $mybb->settings['ultimatecaptcha_characters'];
    $randtext = '';    

    for ($q = 0; $q < $opts['num']; $q++) {
        $randtext .= $characters[mt_rand(0, strlen($characters) - 1)];
    }	
    $arr_captcha['hash'] = sha1($randtext);
    $arr_captcha['hash_raw'] = sha1($randtext, true);
    $arr_captcha['answer'] = $randtext;
    $arr_captcha['spareused'] = 0;
    }
  ultimatecaptcha_dbinsert($arr_captcha);

  // Display it
  $lang->load('ultimatecaptcha');
  $ultimatecaptcha_title = '';
  $ultimatecaptcha_desc = '';
  if (isset($lang->{"ultimatecaptcha_title"}))
    $ultimatecaptcha_title = $lang->{"ultimatecaptcha_title"};
  if (isset($lang->{"ultimatecaptcha_desc"}))
    $ultimatecaptcha_desc = $lang->{"ultimatecaptcha_desc"};

  $ultimatecaptcha_classes = ' ultimatecaptcha-captcha';
  $ultimatecaptcha_hash = $arr_captcha['hash'];

  eval("\$ultimatecaptcha = \"" . $templates->get("ultimatecaptcha_captcha")."\";");
}

/**
 * Validation callback function.
 */
function ultimatecaptcha_validate($reg) {
  global $mybb, $db, $lang;

  // Quit if it's not enabled
  if (!$mybb->settings['ultimatecaptcha_enabled'])
    return;

  // Load error messages
  $lang->load('ultimatecaptcha');

  // Quit if the hash field is gone
  if (empty($mybb->input['ultimatecaptcha-hash'])) {
    $reg->set_error($lang->ultimatecaptcha_error_no_hash);
    return;
  }

  // Quit if hash in invalid
  if (!preg_match('/^[0-9a-f]{40}$/', $mybb->input['ultimatecaptcha-hash'])) {
    $reg->set_error($lang->ultimatecaptcha_error_invalid_hash);
    return;
  }

  // Validate
  $hash = $mybb->input['ultimatecaptcha-hash'];

  if (isset($mybb->input['ultimatecaptcha-answer'])
      && '' !== $mybb->input['ultimatecaptcha-answer']) {
    $answer = trim($mybb->input['ultimatecaptcha-answer']);

      $result = ultimatecaptcha_dbvalidate($hash, $answer);
      if (!$result)
        $reg->set_error($lang->ultimatecaptcha_error_invalid_ans);
      elseif (ultimatecaptcha_gets_num('minresptime', 0)
          && $result > (TIME_NOW - ultimatecaptcha_gets_num('minresptime', 0)))
        $reg->set_error($lang->ultimatecaptcha_error_too_fast);
  }
  else {
    $reg->set_error($lang->ultimatecaptcha_error_no_ans);
  };

  // Drop all entries with the hash
  ultimatecaptcha_dbdrop($hash);
}
function ultimatecaptcha_delete_used_spares() {
  global $db;

  $query = $db->simple_select('captcha_ultimatecaptcha_spares', 'filename', "used = 1");
  while($result = $db->fetch_array($query)) {
//    error_log("Filename is " . $result['filename']);
    if (!unlink($mybb->settings['ultimatecaptcha_renderpath'] . $result['filename']))
    	error_log("Unlink of " . $result['filename'] . " failed");
    $filename = $result['filename'];
    $db->delete_query("captcha_ultimatecaptcha_spares", "filename = '$filename'");
  }
}

