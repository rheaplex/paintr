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


function image_file_xml ($title, $creator, $creator_email, $subject, 
			 $license_url, $other_holder)
{
  assert (! empty ($title));
  assert (! empty ($creator));
  assert (! empty ($subject));
  assert (! empty ($license_url));
  return "<metadata>
        <title>$title</title>
        <collection>$creator</collection>
        <mediatype>Image</mediatype>
        <resource>Image</resource>
        <upload_application appid=\"$creator\" version=\"0.1.0\"/>
        <uploader>$creator_email</uploader>
<monochromatic>False</monochromatic>
<creator>$creator</creator>
<mature_content>False</mature_content>
<subject>$subject</subject>
<title>$title</title>
<image_type/>
<format>images</format>
<copyright_statement/>
<description>Abstract vector image.
Kinda Matissey. :-)</description>" .
    if ($derivative)
      {
	echo ( "<other_copyright_holders>True</other_copyright_holders>" .
	       "<other_holder_details>$other_holder</other_holder_details>");
      }
"<adder>$creator_email</adder>
<licenseurl>$license_url</licenseurl>
<publicdate>{date('Y-m-d G:i:s')}</publicdate>
</metadata>";
}

function filegroup_xml ($filename, $format, $date, $license)
{
  return "<?xml version=\"1.0\" encoding=\"UTF-8\"?>

<files>
  <file name=\"$title\" source=\"original\">
    <format>$format</format>
    <license>$date . $license</license>
  </file>
</files>";

}

function make_file_directory ($uniqueid, $username, $password)
{

}

function upload_file_xml ()
{
  
}

function upload_file ($user_email, $password, $mediatype,
		      $license_string, $license_url
		      $title, $creator, $creator_email, $subject,
		      $license_url, $other_holder)
{

}

function notify_contribution_engine ($user_email, $unique_id, $mediatype)
{
  assert (mediatype == 'audio' || 
	  mediatype == 'movies' || 
	  mediatype == 'items');
  return get_url ("http://www.archive.org/services/contrib-submit.php?" .
		  "user_email=$user_email&" .
		  "server=$mediatype-uploads.archive.org&" .
		  "dir=$unique_id";
}


?>
