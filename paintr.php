<?php

// Copyright 2005 Rob Myers <rob@robmyers.org>
//     
// This program is free software: you can redistribute it and/or modify
// it under the terms of the GNU Affero General Public License as
// published by the Free Software Foundation, either version 3 of the
// License, or (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU Affero General Public License for more details.
//
// You should have received a copy of the GNU Affero General Public License
// along with this program.  If not, see <http://www.gnu.org/licenses/>.

// To run from the command line:
// php paintr.php generate


/////////////////////////////////////////////////////////////////////////////// 
// Setup
///////////////////////////////////////////////////////////////////////////////

// php4 compatibility

if(!function_exists('file_put_contents')) 
  { 
    function file_put_contents($filename, $data, $file_append = false) 
    { 
      $fp = fopen($filename, (!$file_append ? 'w+' : 'a+')); 
      if(!$fp) 
	{ 
	  trigger_error('file_put_contents cannot write in file.', 
			E_USER_ERROR);
	  return -1; 
      } 
      $result = fputs($fp, $data); 
      fclose($fp); 
      return $result;
    } 
  }

require_once 'colr.php';
require_once 'flickr.php';
//require_once 'wordnet.php';
require_once 'autotrace.php';
require_once 'current_id.php';

define("WORK_DIR", "./");
define("STORE_DIR", "./");

///////////////////////////////////////////////////////////////////////////////
// Functions
///////////////////////////////////////////////////////////////////////////////

// Sort palette by brightness.

function brightness ($palette_entry)
{
  return $palette_entry["red"] + 
    $palette_entry["green"] + 
    $palette_entry["blue"];
}

function compare_brightness ($a, $b)
{	 
  $a_brightness = brightness ($a);
  $b_brightness = brightness ($b);
  if ($a_brightness == $b_brightness)
    {
      return 0;
    }
  return ($a_brightness < $b_brightness) ? -1 : 1; 
}

function sort_palette_by_brightness ($palette)
{
  usort ($palette, "compare_brightness");
  return $palette;
}

// Choose light-to-dark or dark-to-light.

function light_to_dark_or_dark_to_light ($palette)
{
  if (rand (0, 1) == 1)
    {
      $palette = array_reverse ($palette);
    }

  return $palette;
}

// Load the svg

function load_svg ($filename)
{
  $tree = new XML_Tree ($filename);
  $root = $tree->getTreeFromFile ();
  if (! $root)
    {
      die ("Couldn't parse svg data.");
    }
  return $root;
}

// Apply the palette
// Random, iterate in palette order, divide in palette order, or map old to new?

function apply_palette_to_svg ($palette, $svg)
{
  $palette_index = 0;
  for ($i = 0; $i < count ($svg->children); $i++) 
    {
      if ($svg->children[$i]->name == 'path')
	{
	  $palette_entry = $palette[$palette_index];
	  $fill = sprintf ("%02x%02x%02x",
			   $palette_entry["red"],
			   $palette_entry["green"], 
			   $palette_entry["blue"]);
	  $svg->children[$i]->setAttribute ('style', 
			       "fill:#" . $fill . "; stroke:none;");
	  $palette_index++;
	  if ($palette_index == count($palette))
	    {
	      $palette_index = 0;
	    }
	}
    }
  return $svg;
}

// Save as final SVG

function save_svg ($svg, $filepath)
{
  global $current_id;
  $svg_string = $svg->get ();
  if (! $svg_string)
    {
      die ("Couldn't convert SVG tree to string.");
    }
  $result = file_put_contents ($filepath, $svg_string);
  if (! $result)
    {
      die ("Couldn't save SVG data to file.");
    }
}

// Get rid of older files so we don't fill up our directory
// Ignores the fact that a user may be loading/working with them

function trim_older ()
{
  if ($current_id > 20)
    {
      @unlink (STORE_DIR . $current_id - 20 . ".html");
      @unlink (STORE_DIR . $current_id - 20 . ".svg");
    }
}

// Create log of concept expansion and matching as text 
// (palette, tags, wordnet expansion, flickr search string, matches, choice)

function tag_or_tags ($tag_list)
{
  if (count ($tag_list) > 1)
    return "tags";
  else
    return "tag";
}

function format_tags ($tags)
{
  return "<i>'" .
    implode ("', '", $tags) .
    "'</i>";
}

function save_writeup ($filename, $palette_name, $palette, $palette_tags, 
		       //$wordnet_expansion, 
		       $flickr_photo)
{
  global $flickr_api;
  global $current_id;
  $flickr_photo_user = photo_user_name ($flickr_photo);
  $flickr_photo_name = photo_name ($flickr_photo);
  $flickr_photo_url = photo_page_url ($flickr_photo);
  $flickr_photo_tags = photo_tags ($flickr_photo);
  $writeup = 
    "<b>How I made this image.</b><br />" .
    "I found a palette at colr called " . $palette_name .
    //format_palette ($palette) .
    " with the following " . tag_or_tags ($palette_tags) . ": " .
    format_tags ($palette_tags) .
    /*"<p>I then expanded the palette tags to these terms using WordNet: " .
      implode (", ", $wordnet_expansion) . */
    " and searched for those tags on flickr.<br />" .
    "I found an image at flickr called " .
    "<a href='" . $flickr_photo_url . "'>" . $flickr_photo_name . "</a>" .
    " by " . $flickr_photo_user . 
    " which had the " . tag_or_tags ($flickr_photo_tags) .  " " . 
    format_tags ($flickr_photo_tags) .  
    " and I traced that using autotrace.<br />" .
    "I then applied the colr palette to the autotraced flickr picture " .
    "in my own unique way to make this finished image.<br />" .
    "This image is licensed under a " .
    "<a href='http://creativecommons.org/licenses/by-sa/2.5/'>" .
    "Creative Commons License</a><br />";
  $result = file_put_contents ($filename, $writeup);
  if (! $result)
    {
      die ("Couldn't save writeup html.");
    }
}


///////////////////////////////////////////////////////////////////////////////
// Main entry point.
///////////////////////////////////////////////////////////////////////////////

function paintr ()
{
  global $current_id;

  /*  try
      {*/
      load_current_id (STORE_DIR);
      $current_id ++;
  
      //$filename_jpeg = WORK_DIR . $current_id . ".jpg";
      $filename_svg = WORK_DIR . $current_id . ".svg";
      $filename_svg_final = STORE_DIR . $current_id . ".svg";
      $filename_html = STORE_DIR . $current_id . ".html";

      echo "Begin.<br />";
      echo "ID: " . $current_id . "<br />";
      
      echo "Get palette.<br />";
      $palette_source = get_palette ();
      $palette_name = palette_name ($palette_source);
      $palette = palette_entries ($palette_source);
      $palette = sort_palette_by_brightness ($palette);
      $palette = light_to_dark_or_dark_to_light ($palette);
      $palette_tags = palette_tags ($palette_source);
      echo "Tags: " . implode (", ", $palette_tags) . "<br />\n";

      //$wordnet_synonims = wordnet_tags_synonims ($palette_tags);
      //echo implode (", ", $wordnet_synonims) . "\n";
      // At the moment flickr API access fails if we don't echo these. Huh?
      echo "Get photo.<br />";
      $photo = photo_from_tags ($palette_tags); //($wordnet_synonims);
      echo "Get photo user name.<br />";
      $username = photo_user_name ($photo);
      echo "Get photo name.<br/>";
      $photo_name = photo_name ($photo);
      $file_description = $photoname . ' by ' . $username . 
	', licensed under the Creative Commons Attribution-ShareAlike License' .
	'.jpg';
      $filename_jpeg = WORK_DIR . urlencode ($file_description);

      echo "Save JPEG.<br />";
      photo_save_jpeg ($photo, $filename_jpeg);

      echo "Autotrace.<br />";
      autotrace (count($palette), $filename_jpeg, $filename_svg);
      echo "Delete old file.<br />";
      @unlink ($filename_jpeg);
      echo "Load svg.<br />";
      $svg = load_svg ($filename_svg);
      echo "Apply palette.<br />";
      $svg = apply_palette_to_svg ($palette, $svg);
      echo "Save SVG.<br />";
      save_svg ($svg, $filename_svg_final);

      echo "Save writeup.<br />";
      save_writeup ($filename_html, $palette_name, $palette, $palette_tags, 
		    $photo);

      echo "End.<br />";
      /*    } 
  catch (Exception $e)
    {
      $error = "<h2>" . date ("l dS of F Y at h:i:s A") . "</h2>" .
	"<h2>An error occurred: " . get_class($e) . "</h2>\n" .
	"<h3>{$e->getMessage()} ({$e->getCode()})</h3>\n\n" .
	"file: {$e->getFile()}<br/>\n" .
	"line: {$e->getLine()}<br/>\n" .
	"<PRE>" .
	$e->getTraceAsString() . 
	"</PRE><br />" ;
      file_put_contents ("./error.html", $error, FILE_APPEND);
      die ();
      }*/

  // When finished, save the id for next time. If we failed, re-use same id.

  save_current_id (STORE_DIR);
  trim_older ();
}

// Generate

if ($argc == 1 && 
    (strcmp ($_GET['command'], "generate8724581235981345619835619384561385sakdjghfSLJFGjsfgLJHSFLJfSJLHFJLSDFSJHF") == 0))
  {
    paintr ();
  }

?>
