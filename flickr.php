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
// Setup
///////////////////////////////////////////////////////////////////////////////

require_once 'Flickr/API.php';

define("FLICKR_API_KEY", ""); // For paintr

$flickr_api =& new Flickr_API (array ('api_key' => FLICKR_API_KEY));

///////////////////////////////////////////////////////////////////////////////
// Functions
///////////////////////////////////////////////////////////////////////////////

// Get an xml node child by name

function xml_node_child ($node, $name)
{
  $child = 0;
  $children = $node->children;
  if ($children)
  {
    foreach ($children as $candidate)
	{
	  	if (strcmp ($candidate->name, $name) == 0)
	  	{
	    	$child = $candidate;
			break;
		}
	}
  }
	
  return $child;
}


// Call a flickr method
// Response is an XML_Tree root object

function call_flickr ($method, $arguments)
{
  global $flickr_api;
  
  $response = $flickr_api->callMethod ($method, $arguments);
  
  if (! $response)
    {
      $code = $flickr_api->getErrorCode();
      $message = $flickr_api->getErrorMessage();
      die ("Error accessing flickr API: $code - $message");
    }

  return $response;
}

// Find matching image at flickr.

function flickr_tag_search ($tag_list, $result_count)
{
  // Flickr search hates more than 20 tags
  if (count ($tag_list) > 20)
    {
      $tag_list = array_slice ($tag_list, 0, 20);
    }
    
  // flickr doesn't like empty tag lists, always have at least one tag
  $tags = implode (", ", $tag_list);
  if ($tags == "")
    {
      $tags = "art";
    }
  $rsp = call_flickr ('flickr.photos.search',
		      array ('per_page' => $result_count,
			     'tags' => $tags,
			     'license' => '5' )); // CC-BY-SA-2.0
  echo $rsp->children[0]->name;
  return xml_node_child ($rsp, 'photos');
}

// Get the photo in the photolist at the given index

function flickr_photo ($photolist, $index)
{
  $photo_xml = $photolist->children[$index + 1];
  assert ($photo_xml);
  $photo_id = $photo_xml->getAttribute ('id');
  $photo_secret = $photo_xml->getAttribute ('secret');
  $photo =  call_flickr ('flickr.photos.getInfo',
			 array ('photo_id' => $photo_id,
				'photo_secret' => $photo_secret));
  assert ($photo);
  return xml_node_child ($photo, 'photo');
}

// Count the number of matches

function flickr_match_count ($photolist)
{
  $children = $photolist->children;
  return sizeof ($children);
}

// Get a photo matching a given tags list

function photo_from_tags ($tags)
{
  $photos = flickr_tag_search ($tags, 10);
  //echo $photos->get ();
  $photos_count = flickr_match_count ($photos);
  echo "Count: " . $photos_count . "\n";
  if ($photos_count == 0)
    {
      //TODO: It's here that we add the intelligense
      //TODO: Currently we get too many new tags too fast,
      //TODO: Then just fall back to "art" if that fails.
      //TODO: Move to paintr.php
      /*      $generalised_tags = wordnet_tag_synonims ($tags);
      $photos = flickr_tag_search ($generalised_tags, 10);
      $photos_count = $photos->getCount();
      if ($photos_count == 0)
	{*/
	  $photos = flickr_tag_search (array ("art"), 10);	  
	  $photos_count = flickr_match_count ($photos);
	  if ($photos_count == 0)
	    {	    
	      die ("Can't match colr tags at flickr.");
	    }
	  //  }
    }
  return flickr_photo ($photos, 0);
}

// Get the name of the user that uploaded the photo

function photo_user_name ($photo)
{
  $owner = xml_node_child ($photo, 'owner');
  return $owner->getAttribute ('realname');
}

// Get the name of the user that uploaded the photo

function flickr_photo_user_username ($photo)
{
  $owner = xml_node_child ($photo, 'owner');
  return $owner->getAttribute ('username');
}

// Get the name of the photo

function photo_name ($photo)
{
  $title = xml_node_child ($photo, 'title');
  return $title->content;
}

// Gedt the url for the flickr photo's page

function photo_page_url ($photo)
{
  //$user = flickr_photo_user_username ($photo);
  //$id = $photo->getAttribute ('id');
  //return "http://www.flickr.com/photos/{$user}/{$id}";
  $url = "";
  $urls = xml_node_child ($photo, 'urls');
  if ($urls)
  {
    foreach ($urls->children as $url_tag)
	{
	 	$type = $url_tag->getAttribute ('type');
	  	if (strcmp ($type, "photopage") == 0)
	  	{
	    	$url = $url_tag->content;
			break;
		}
	}
  }
  return $url;
}

// Get the url for the flickr data for the photo

function flickr_photo_data_url ($photo)
{
  $server_id = $photo->getAttribute ('server');
  $id = $photo->getAttribute ('id');
  $secret = $photo->getAttribute ('secret');
  return "http://photos{$server_id}.flickr.com/{$id}_{$secret}_m.jpg";
}

// Get the flickr tags for the photo

function photo_tags ($photo)
{
  $tags = array ();
  
  $taglist = xml_node_child ($photo, 'tags');
  if ($taglist)
    {
      foreach ($taglist->children as $tag)
	{
	  $raw_tag = $tag->getAttribute ('raw');
	  if (strcmp ($raw_tag, "") != 0)
	    array_push ($tags, $raw_tag);
	}
    }
    return $tags;
}

// Get the jpeg data blob of the photo

function flickr_photo_jpeg_data ($image_photo)
{
  $image_url = flickr_photo_data_url ($image_photo); // Defaults to 240px
  $result = file_get_contents ($image_url); 
  if (! $result)
    {
      die ("Couldn't get jpeg from flickr: " . $image_url);
    }
  return $result;
}

function photo_save_jpeg ($photo, $file_path)
{
  $jpeg = flickr_photo_jpeg_data ($photo);
  $result = file_put_contents ($file_path, $jpeg);
  //echo $result;
  if (! $result)
    {
      die ("Couldn't save flickr jpeg data to file.");
    }
}

?>
