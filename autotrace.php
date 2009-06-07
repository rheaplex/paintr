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

// Autotrace image to SVG (autotrace).
// Posterises image as well (don't need to use Imagemagick).

function autotrace ($colour_count, $filename_jpeg, $filename_svg) 
{
  $response = autotrace_upload ($colour_count, $filename_jpeg);
  autotrace_download ($response, $filename_svg);
}

// Send the file to the autotrace server

function autotrace_upload ($colour_count, $filename_jpeg)
{
  $ch = curl_init ();
  $url="http://roitsystems.com/cgi-bin/r2v/tracer.pl"; 

  $post_data = array ();
  $post_data['--output-format'] = "svg";
  $post_data['--color-count'] = $colour_count;
  $post_data['--despeckle-level'] = 10;
  $post_data['submit'] = "Upload File"; 
  $post_data['filename'] = "@$filename_jpeg";
 
  curl_setopt ($ch, CURLOPT_URL, $url);    
  curl_setopt ($ch, CURLOPT_TIMEOUT, 120);    
  curl_setopt ($ch, CURLOPT_POST, 1 );
  curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt ($ch, CURLOPT_POSTFIELDS, $post_data );

  $response = curl_exec ($ch);   // Response is the data
  curl_close ($ch); 

  if (! $response)
    {
      die ("Error accessing autotrace service.");
    }

  return $response;
}

// Get the traced file from the server

function autotrace_download ($response, $filename_svg)
{
  $result = false;
  $match_result = preg_match('/<a href="(.+?.svgz)">Retrieve the/',
			     $response,
			     $matches);
  if (! $match_result)
    {
      die ("autotrace service didn't return a file URL.");
    }
  $traced_file_url = "http://roitsystems.com" . $matches[1];

  // % -> %25 because of the cc-by-sa-2.0 compliance name hack
  // Take this out if the upload name hasn't been urlencoded
  $pathinfo = pathinfo ($traced_file_url);
  $traced_file_url = $pathinfo['dirname'] . "/" . 
    preg_replace ('/%/', '%25', $pathinfo['basename']);
  
  $traced_file_data = file_get_contents ($traced_file_url);
  if (! $traced_file_data)
    {
      die ("Couldn't get .svgz file from autotrace service.");
    }

  $gzipped_svg_filename = $filename_svg . ".gz";
  file_put_contents ($gzipped_svg_filename, $traced_file_data);
  exec ("gunzip " . $gzipped_svg_filename);
}

?>
