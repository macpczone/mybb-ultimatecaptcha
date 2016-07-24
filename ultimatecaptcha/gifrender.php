<?php
/*
This file is part of Ultimate CAPTCHA

Copyright (C) 2013  Michael Campbell.

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU Affero General Public License as
published by the Free Software Foundation, either version 3 of the
License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU Affero General Public License for more details.

You should have received a copy of the GNU Affero General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.

*/
$renderdir = $argv[1];
if(!chdir($renderdir))
    die("Could not change directory to imagedir\n");
$lock = fopen('pidfile', 'c+');
$pid = fgets($lock);
if (file_exists( "/proc/$pid" ) && $pid > 0){
    //process with a pid = $pid is running
    die("already running\n");
}
 
switch ($pid = pcntl_fork()) {
    case -1:
        die('unable to fork');
    case 0: // this is the child process
        break;
    default: // otherwise this is the parent process
        fseek($lock, 0);
        ftruncate($lock, 0);
    	fwrite($lock, $pid);
        fflush($lock);
        exit;
}
 
if (posix_setsid() === -1) {
     die('could not setsid');
}
 

fclose(STDIN);
fclose(STDOUT);
fclose(STDERR);

$stdIn = fopen('/dev/null', 'r');
$stdOut = fopen('gifrender.log', 'w') or die("can't open gifrender.log");
$stdErr = fopen('gifrender.log', 'a') or die("can't open gifrender.log");
 
pcntl_signal(SIGTSTP, SIG_IGN);
pcntl_signal(SIGTTOU, SIG_IGN);
pcntl_signal(SIGTTIN, SIG_IGN);
pcntl_signal(SIGHUP, SIG_IGN);
 
proc_nice(22);
// do some long running work
define("IN_MYBB", 1);
define("NO_ONLINE", 1);
define('THIS_SCRIPT', 'gifrender.php');
define("ALLOWABLE_PAGE", 1);
require_once $argv[2] . "global.php";
include(MYBB_ROOT . 'inc/plugins/ultimatecaptcha/ucfunctions.php');
if (!$mybb->settings['ultimatecaptcha_background'])
    die("Background renderer is disabled\n");
$num = ultimatecaptcha_gets_num('num', 5);
$characters = $mybb->settings['ultimatecaptcha_characters'];

$dirnum = 0;
$dirin = ultimatecaptcha_gets_num('dirin', 1000);;
$dircount = 0;
$sparesmax = ultimatecaptcha_gets_num('sparesmax', 7000);;
$sparesmin = ultimatecaptcha_gets_num('sparesmin', 4000);;
//$sparesmax = 7000;
//$sparesmin = 4000;
$renderem = 1;
$filescount = 0;
echo "Background renderer starting\n";
$countcheck = fopen('filescount', 'r');
    if ($countcheck) {
        $filescount = fgets($countcheck);
        if ($filescount > 0) {
            $dirnum = round($filescount, -3) / 1000;
            echo "Fourthdigit is " . $dirnum . "\n";
            $dircount = $filescount - $dirnum * 1000;
            echo "Resuming from file number " . $dircount . "\n";
        }
    }
while(true) {
    if($renderem) {
        $directory = sprintf("%04d", $dirnum);
        if(!file_exists($directory)) 
            mkdir($directory); 
        $imagestring = '';    

        for ($q = 0; $q < $num; $q++) {
            $imagestring .= $characters[mt_rand(0, strlen($characters) - 1)];
        }	
        $captchahash = sha1($imagestring, TRUE);

        $lengtha = 10;
        $charactersa = '0123456789abcdefghijklmnopqrstuvwxyz';
        $filename = '';    

        for ($q = 0; $q < $lengtha; $q++) {
            $filename .= $charactersa[mt_rand(0, strlen($charactersa) - 1)];
        }	
        $filename = $directory . "/" . $filename . ".gif";
        $outfile = fopen ( $filename, "wb" );
    //    echo "\nFilename " . $filename . " imagestring " . $imagestring . " loop " . $loop . "\n";
        $gif = renderagif($imagestring);
    //    echo "\n";
        if (fwrite ( $outfile, $gif) === FALSE) {
            echo "Cannot write to file ($filename)\n";
            exit;
        }
        fclose( $outfile );
        $db->replace_query('captcha_ultimatecaptcha_spares', array(
        	"hash"      => $db->escape_string($captchahash),
        	"filename"  => $filename,
        	"used"  	=> 0,
        	"answer"    => $imagestring,
        ));
        $filescount++;
        $countfile = fopen('filescount', 'c+') or die("Unable to open filescount\n");
        fseek($countfile, 0);
        ftruncate($countfile, 0);
    	fwrite($countfile, $filescount);
        fflush($countfile);
        fclose($countfile);
        $dircount++;
        if ($dircount >= $dirin) {
            $dircount = 0;
            $dirnum++;
            if ($dirnum > 9999) {
                $dirnum = 0;
                $filescount = 0;
            }
        }
        unset ($gif);
    }
    else {
        sleep(5);
    }
    $query = $db->simple_select('captcha_ultimatecaptcha_spares', 'filename', "used = 0");
    $filenum = $db->num_rows($query);
//    echo $filenum . "\n";
    if ($filenum >= $sparesmax && $renderem == 1) {
        $renderem = 0;
        sleep(10);
        echo "Stopped rendering\n";
    }
    elseif ($renderem == 0 && $filenum <= $sparesmin) {
        $renderem = 1;
        echo "Started rendering\n";
    }
//    echo "renderem is " . $renderem . "\n";
    $query = $db->simple_select("settings", "*", "name='ultimatecaptcha_background'", array("limit" => 1));
    $value = $db->fetch_array($query);
    if ($value['value'] == '0')
        die("Background renderer stopping\n");
}
?>
