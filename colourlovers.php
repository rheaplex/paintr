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
 
// Get palette from colourlovers.

function get_palette ()
{
  // Get the latest page
  $links_html = file_get_contents ('http://www.colourlovers.com/index.cfm?section=palettes');
  // Get links
  $palette_links = get_palette_links ($links_html);
  // Choose randomly
  $num_palette_links = count ($palette_links);
  $palette_link_index = rand (0, $num_palette_links - 1);
  $palette_link = $palette_links[$palette_link_index];
  // Get html
  $palette_html = file_get_contents ('http://www.colourlovers.com/'. 
				     $palette_link);
  // Return
  return $palette_html;
}

// Get the links to palettes from the listing page
// Too hard to parse individual entries here :-(

function get_palette_links ($html)
{
  $result = preg_match_all ('/<strong><a href="(\/index.cfm\?section=palettes&view=display&palette=.+?)">.+?<\/a><\/strong>/s', 
			    $html, $links);  
  if (! $result)
    {
      die ("Couldn't get palette links.");
    }
  return $links[1];
}

// Get the palette name from the palette page html

function palette_name ($html)
{
  $result = preg_match ('/<span class="colourName">(.*?)<\/span>/', 
			$html, $matches);
  if (! $result)
    {
      die ("Couldn't get palette name.");
    }
  return $matches[1];
}

// Get the palette colours from the palette page html

function palette_entries ($html)
{
  $result = preg_match_all ('/<td bgcolor="#(..)(..)(..)"><a href="index\.cfm\?section=colours/s',
			    $html, $matches, PREG_SET_ORDER);
  $palette = array ();
  foreach($matches as $match)
    {
      $components = array ('red' => base_convert ($match[1], 16, 10),
			    'green' => base_convert ($match[2], 16, 10),
			    'blue' => base_convert ($match[3], 16, 10));
      array_push ($palette, $components);
    }
  return $palette;
}

// Get the palette 'tags' from the palette page html

function palette_tags ($html)
{
  return array ("art");
}

?>
