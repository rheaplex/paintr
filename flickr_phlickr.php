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

require_once 'Phlickr/PhotoList.php';
require_once 'Phlickr/Photo.php';
require_once 'Phlickr/User.php';

define("FLICKR_API_KEY", ""); // For paintr

$flickr_api = new Phlickr_Api(FLICKR_API_KEY);


///////////////////////////////////////////////////////////////////////////////
// Functions
///////////////////////////////////////////////////////////////////////////////

// Find matching image at flickr.

function flickr_tag_search ($tag_list, $result_count)
{
  global $flickr_api;
  // flickr doesn't like empty tag lists, always have at least one tag
  $tags = implode (", ", $tag_list);
  if ($tags == "")
    {
      $tags = "art";
    }
  $request = $flickr_api->createRequest('flickr.photos.search',
					array(
					      'tags' => $tags,
					      'licence' => '5' // CC-BY-SA-2.0
					      )
					);
  return new Phlickr_PhotoList($request, $result_count);
}

// Get the photo in the photolist at the given index

function flickr_photo ($photolist, $index_request)
{
  global $flickr_api;
  $ids = $photolist->getIds ();
  $photos_count = $photolist->getCount();
  $index = ($index_request < $photos_count) ? 
    $index_request : $photos_count;
  $id =  $ids[$index];
  return new Phlickr_Photo($flickr_api, $id);
}

// Get a photo matching a given tags list

function flickr_photo_from_tags ($tags)
{
  $photos = flickr_tag_search ($tags, 10);
  $photos_count = $photos->getCount();
  //echo "Count: " . $photos_count . "\n";
  if ($photos_count == 0)
    {
      //TODO: It's here that we add the intelligense
      //TODO: Currently we get too many new tags too fast,
      //TODO: Then just fall back to "art" if that fails.
      //TODO: Move to paintr.php
      $generalised_tags = wordnet_tag_synonims ($tags);
      $photos = flickr_tag_search ($generalised_tags, 10);
      $photos_count = $photos->getCount();
      if ($photos_count == 0)
	{
	  $photos = flickr_tag_search (array ("art"), 10);
	  $photos_count = $photos->getCount();
	  if ($photos_count == 0)
	    {	    
	      throw new Exception ("No photos at flickr match requested tags.");
	    }
	}
    }
  return flickr_photo ($photos, 1);
}

// Get the name of the user that uploaded the photo

function flickr_photo_username ($image_photo, &$user_name)
{
  global $flickr_api;
  $user_id = $image_photo->getOwnerId ();
  $user = new Phlickr_User($flickr_api, $user_id);
  $user_name = $user->getName ();
}

// Get the name of the photo

function flickr_photo_name ($image_photo, &$photo_name)
{
  $photo_name = $image_photo->getTitle ();
}

// Get the url for a flickr page for the photo

function flickr_photo_url ($image_photo, &$photo_url)
{
  $photo_url = $image_photo->buildUrl ();
}

// Get the flickr tags for the photo

function flickr_photo_tags ($image_photo, &$photo_url)
{
  $photo_url = $image_photo->getTags ();
}

// Get the jpeg data blob of the photo

function flickr_photo_jpeg_data ($image_photo)
{
  $image_url = $image_photo->buildImgUrl (); // Defaults to 240px
  $result = file_get_contents ($image_url); 
  if (! $result)
    {
      throw new Exception ("Couldn't get jpeg from flickr: " . $image_url);
    }
  return $result;
}

function flickr_photo_save_jpeg ($photo, $file_path)
{
  $jpeg = flickr_photo_jpeg_data ($photo);
  $result = file_put_contents ($file_path, $jpeg);
  if (! $result)
    {
      throw new Exception ("Couldn't save flickr jpeg data to file.");
    }
}

?>
