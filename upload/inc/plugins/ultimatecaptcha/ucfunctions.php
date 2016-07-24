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
include(MYBB_ROOT . 'inc/plugins/ultimatecaptcha/GIFEncoder.class.php');

function generateimage($xpos, $text, $font, $fontsize, $imgx, $imgy) 
{
    global $mybb;
	$mode = $mybb->settings['ultimatecaptcha_mode'];
	$cnoise = ultimatecaptcha_gets_num('cnoise', 240, 255);
	$contrast = ultimatecaptcha_gets_num('contrast', 225, 255);
	$lines = ultimatecaptcha_gets_num('lines', 4, NULL, 1);
	$elipses = ultimatecaptcha_gets_num('elipses', 5, NULL, 1);
	// Create the image
	$im = imagecreatetruecolor($imgx, $imgy);

	// Create some colors
	$white = imagecolorallocate($im, 255, 255, 255);
	$grey = imagecolorallocate($im, 127, 127, 127);
	$black = imagecolorallocate($im, 0, 0, 0);
//	$style = array($white, $black);
	switch (ultimatecaptcha_gets_num('charcolours', 0)) {
	    case 0:
	        $colour = $black;
	        break;
	    case 1:
	        $colour = $white;
	        break;
	    case 2:
	        $colour = $grey;
	        break;
	}
	$stylenum = ultimatecaptcha_gets_num('stylenum', 30);
	$style = array();
	for($i=1; $i<=$stylenum; $i++) {
		switch (rand(1,3)) {
		    case 1:
		        $style[] = $black;
		        break;
		    case 2:
		        $style[] = $white;
		        break;
		    case 3:
		        $style[] = $grey;
		        break;
		}
	}

//	$style = array($grey, $white, $grey, $white, $grey, $white, $grey, $white, $grey, $white, 
//               $grey, $black, $grey, $black, $grey, $black, $grey, $black, $grey, $black);
//	shuffle($style);
	ImageSetStyle($im, $style);
	imagefilledrectangle($im, 0, 0, $imgx - 1, $imgy - 1, IMG_COLOR_STYLED);

	for($i=1; $i<=$lines; $i++){
//	    $colour = $black;
    	imagesetthickness($im, round($imgy / 30) + 1);
	    imageline($im, rand(1,6), rand(5,$imgy - 5), rand($imgx - 9,$imgx - 4), rand(5,$imgy - 5), $colour);
	}

	for($i=1; $i<=$elipses; $i++){
        imagefilledellipse($im, mt_rand(0,$imgx - 5), mt_rand(0,$imgy - 5), mt_rand(round($imgy / 6), round($imgy / 3)), mt_rand(round($imgy / 6), round($imgy / 3)), $colour);
	}

	$square_count =  ultimatecaptcha_gets_num('squares', 3, NULL, 1);
	for($i = 1; $i <= $square_count; ++$i)
	{
		$pos_x = mt_rand(1, $imgx);
		$pos_y = mt_rand(1, $imgy);
		$sq_width = $sq_height = mt_rand(round($imgy / 6), round($imgy / 3));
		$pos_x2 = $pos_x + $sq_height;
		$pos_y2 = $pos_y + $sq_width;
		imagefilledrectangle($im, $pos_x, $pos_y, $pos_x2, $pos_y2, $colour); 
	}
	// The text to draw
//	$text = 'Scrollit...';
	// Replace path by your own font path

	// Add some shadow to the text
//	imagettftext($im, $fontsize, 0, $xpos + 1, $imgy - 5, $grey, $font, $text);

	// Add the text
	imagettftext($im, $fontsize, 0, $xpos, $imgy - round($fontsize / 3), $colour, $font, $text);
	if (!$mode)
		imagefilter($im, IMG_FILTER_EMBOSS);
	if ($mode) {
		for($i = 0; $i < $imgx; $i++) {
		    for($j = 0; $j < $imgy; $j++) {
				$rgb = imagecolorat($im, $i, $j);
				if ($rgb == $colour && mt_rand(0,255) < $cnoise)
					$pixel = imagecolorallocate($im, mt_rand($contrast,255), mt_rand($contrast,255), mt_rand($contrast,255));
				else
		        	$pixel = imagecolorallocate($im, mt_rand(0,255), mt_rand(0,255), mt_rand(0,255));
		        imagesetpixel($im, $i, $j, $pixel);
		    }
		}
	}
	return $im;
}

function renderagif($imagestring) {

	// Set the content-type
	header('Content-Type: image/gif');
	//error_reporting(E_ERROR | E_WARNING | E_PARSE);

    global $mybb;
	$imgx = ultimatecaptcha_gets_num('width', 100);
	$imgy = ultimatecaptcha_gets_num('height', 60);
	$fontsize = round(($imgy * 2) / 3);
	$movein = ultimatecaptcha_gets_num('movein', 10);

	$ttf_fonts = array();

	// We have support for true-type fonts (FreeType 2)
	if(function_exists("imagefttext"))
	{
		// Get a list of the files in the 'catpcha_fonts' directory
		$ttfdir  = @opendir(MYBB_ROOT."inc/captcha_fonts");
		if($ttfdir)
		{
			while($file = readdir($ttfdir))
			{
				// If this file is a ttf file, add it to the list
				if(is_file(MYBB_ROOT."inc/captcha_fonts/".$file) && get_extension($file) == "ttf")
				{
					$ttf_fonts[] = MYBB_ROOT."inc/captcha_fonts/".$file;
				}
			}
		}
	}
	else
	{
		die("No Freetype support.");
	}
	$font = array_rand($ttf_fonts);
	$font = $ttf_fonts[$font];

	$arSize = imagettfbbox($fontsize, 0, $font, $imagestring);

	$rend = $imgx - $movein - round($arSize[2] * 1.04);
	for ( $aa = $movein - 1; $aa >= $rend; $aa--) {
		ob_start();
		imagegif(generateimage($aa, $imagestring, $font, $fontsize, $imgx, $imgy));
		$frames[]=ob_get_contents();
		$framed[]=6;

		// Delay in the animation.
		ob_end_clean();
	}

	for ( $aa = $rend; $aa <= $movein; $aa++) {
		ob_start();
		imagegif(generateimage($aa, $imagestring, $font, $fontsize, $imgx, $imgy));
		$frames[]=ob_get_contents();
		$framed[]=6;

		ob_end_clean();
	}
		

	$gif = new GIFEncoder($frames,$framed,0,2,0,0,0,NULL,'bin');
	unset ($frames);
	unset ($framed);

	return $gif->GetAnimation();
	//exit;
}
?>
