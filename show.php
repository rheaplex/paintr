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

require_once 'current_id.php';

load_current_id ("./");

// Get the current image id
// Make damn sure it's an int and is in range
$id = $_GET['image'];
if ($id)
  {
    $id = 0 + $id;
    if (($id < ($current_id - 20)) ||
	($id < 1))
      {
	$id = 1;
      }
    else if ($id > $current_id)
      {
	$id = $current_id;
      }
  }
 else
   {
     $id = $current_id;
   }
?>
<html>
<head>
<title>paintr</title>
<style type="text/css">
body {font-family:Helvetica,Verdana,Arial,sans-serif; overflow:hidden; margin: 0}
p {font-size:8pt}
</style>
</head>
<body>
<div align='center' valign='center'><applet code='paintr_show.class' codebase='.' width='1024' height='768'>
  <param name='url' value='http://paintr.robmyers.org/'>
  <?php echo "<param name='id' value='$id'>"?>
  You will need Java enabled to see this image.
</applet></p>
<?php
// Would be bad if we were using a string or not constrained.
echo file_get_contents ("./" . $id . ".html");
?>
</body>
</html>
