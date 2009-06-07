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
 
///////////////////////////////////////////////////////////////////////////////
// Functions
///////////////////////////////////////////////////////////////////////////////
 
// Get palette from colr.

function palette_entries ($palette_rss)
{
  $palette = array ();
  $colrs = array ();
  $result = preg_match_all ("/#(..)(..)(..)/s", // #FF00FF
			    $palette_rss, $colrs, PREG_SET_ORDER);
  foreach($colrs as $colr)
    {
      $details = array ("red" => base_convert ($colr[1], 16, 10),
			"green" => base_convert ($colr[2], 16, 10),
			"blue" => base_convert ($colr[3], 16, 10));
      array_push ($palette, $details);
    }
  return $palette;
}

// Get the tags from the palette XML

function palette_tags ($palette_rss)
{
  $matches = array ();
  $result = preg_match_all ('/<tags>(.+?)<\/tags>/s',
			    $palette_rss, $matches, PREG_SET_ORDER);
  if (! $result)
    {
      die ("Couldn't get tags from colr scheme.");
    }
  preg_match_all ('/([^ ]+)\s*/s',
		  $matches[0][1], $matches, PREG_SET_ORDER);
  $tags = array ();
  foreach ($matches as $tag)
    {
      if (strcmp ($tag, "") != 0)
	array_push ($tags, $tag[1]);
    }
  return $tags;
}

// Get the name from the palette XML

function palette_name ($colr_palette_rss)
{
  $result = preg_match ('/<title>(.+?)<\/title>/s', $colr_palette_rss, 
			$matches);
  if (! $result)
    {
      die ("Couldn't get colr scheme name from.");
    }
  return $matches[1];
}

// Get the first palette from the colr rss

function get_palette ()
{
  $colr_palette_rss = file_get_contents ("http://colr.org/rss/scheme/random");
  if (! $colr_palette_rss)
    {
      die ("Couldn't get latest color schemes rss.");
    }
  $result = preg_match ('/<item>.+?<\/item>/s', $colr_palette_rss, $matches);
  if (! $result)
    {
      die ("Couldn't get first colr scheme.");
    }
  return $matches[0];
}

?>
