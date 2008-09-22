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


$current_id = 0;


///////////////////////////////////////////////////////////////////////////////
// Functions
///////////////////////////////////////////////////////////////////////////////

// Set up the current id

function load_current_id ($id_dir)
{
  global $current_id;
  $id_filename = "$id_dir/current.id";
  $id_string = '0';
  if (file_exists ($id_filename))
    {
      $id_string = file_get_contents ($id_filename);
    }
      $current_id = 0 + $id_string;
}

// Save the current id for next time

function save_current_id ($id_dir)
{
  global $current_id;
  $id_filename = "$id_dir/current.id";
  file_put_contents ($id_filename, $current_id);
}

?>
