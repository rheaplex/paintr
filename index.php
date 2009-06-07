<?php

// Copyright 2005, 2009 Rob Myers <rob@robmyers.org>
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

$id_string = (int)file_get_contents ("./current-id");
$current_id = 0 + $id_string;

// Get the current image id
// Make damn sure it's an int and is in range
$id = (int)$_GET['image'];
if ($id)
  {
    $id = 0 + $id;
    if ($id < 1)
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
<title>paintr image <?php echo $id ?></title>
<link href="rss.xml" type="application/rss+xml" rel="alternate" title="Paintr RSS Feed" />
<style type="text/css">
body {font-family:Helvetica,Verdana,Arial,sans-serif}
p {font-size:8pt}
</style>
<head>
<body>
<h1>paintr</h1><hr />
<p><embed src="<?php echo "./$id.svgz"?>" width="500" height="500" /></p>
<?php
// Would be bad if we were using a string or not constrained.
echo file_get_contents ("./" . $id . ".html");
?>
<hr />
<p>To find out more about me and to get my source code click 
<?php echo "<a href='./about.php?id=$id'>"?>here</a>.
<br />
<a href="./rss.xml"><img src="feed-icon-14x14.png" height="14" width="14" border="0" /> RSS 2.0 feed of images.</a>
</p>
<hr />

<p align ='center'>
<?php
// If we're not the oldest image, add oldest and previous
if (($id != 1) &&
    ($id > ($current_id - 20)))
  {
    $previous_id = $id - 1;
    $navigation = "<a href ='./index.php?image=1'>oldest</a>&nbsp;&nbsp;" .
    "<a href = './index.php?image=$previous_id'>previous</a>&nbsp;&nbsp;";
  }
 else
   {
     $navigation = "oldest&nbsp;&nbsp;previous&nbsp;&nbsp;";
   }
// If we're not the newest image, add next
if ($id != $current_id)
  {
    $next_id = $id + 1;
    $navigation = $navigation . 
      "<a href = './index.php?image=$next_id'>next</a>&nbsp;&nbsp;" .
      "<a href ='./index.php?image=$current_id'>newest</a>\n";
  }
 else
   {
     $navigation = $navigation . "next&nbsp;&nbsp;newest";
   }
echo $navigation;
?></p>
</body>
</html>
