<?php

// Copyright 2005 Rob Myers <rob@robmyers.org>    
//     
// This file is part of paintr.
// 
// paintr is free software; you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation; either version 3 of the License, or
// (at your option) any later version.
// 
// paintr is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// 
// You should have received a copy of the GNU General Public License
// along with this program.  If not, see <http://www.gnu.org/licenses/>.


///////////////////////////////////////////////////////////////////////////////
// Functions
///////////////////////////////////////////////////////////////////////////////

// Expand tag word using wordnet. 

function wordnet_tag_synonims ($tag)
{
  $wn_command = "/usr/local/WordNet-2.0/bin/wn " . $tag . " -hypen";
  $raw_synonims = shell_exec ($wn_command);
  if (! $raw_synonims)
    {
      die ("Problem running wordnet.");
    }
  $matches = array ();
  preg_match_all ("/=>\s+(.+)\s+/",
		  $raw_synonims, $matches, PREG_PATTERN_ORDER);
  $synonims = array ();		
  // Often more than one, comma-delimited word per line
  foreach ($matches[1] as $line)
    {
      $synonims = array_merge ($synonims, split (", ", $line));
    }
  return $synonims;
}

// Expand multiple tags placing the result into a flat list using wordnet

function wordnet_tags_synonims ($tags)
{
  $synonims = array ();
  foreach ($tags as $tag)
    {
      $tag_synonims = wordnet_tag_synonims ($tag);
      $synonims = array_merge ($synonims, $tag_synonims);
    }
  return $synonims;
}

?>
