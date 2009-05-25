<?php

// Copyright 2005, 2009 Rob Myers <rob@robmyers.org>    
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

?>

<html>
<head>
<title>about paintr</title>
<style type="text/css">
body {font-family:Helvetica,Verdana,Arial,sans-serif}
p {font-size:8pt}
ul {font-size:8pt}
</style>
<head>
<body>
<h1>paintr</h1><hr />
<p>Paintr was created by <a href='http://www.robmyers.org/'>Rob Myers</a>. It was inspired by the writing of Harold Cohen and the projects of Pall Thayer.</p> 
<p>It is written in Lisp, with this web front end written in PHP, and uses web services to gather aesthetic materials in order to create an analogue to art or artistic activity:
<ul>
<li /><a href="http://www.colr.org/">colr</a>
<li /><a href="http://www.flickr.com/">flickr</a>
</ul>
</p>
<p>All the images from flickr used by paintr are licensed under the Creative Commons <a href='http://creativecommons.org/licenses/by-sa/2.0/'>Attribution-Sharealike</a> license, and are therefore free to be used in this manner.</p>
<hr />
<p align ='center'>
  <?php echo "<a href='./index.php?image=" . (int)$_GET["id"] . "'>"?>back</a>
</p>
</body>
</html>
