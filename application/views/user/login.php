<?php
/*
 * rTorrentWeb version 0.1 prerelease
 * $Id$
 * Copyright (C) 2009, Daniel Lo Nigro (Daniel15) <daniel at d15.biz>
 * 
 * This file is part of rTorrentWeb.
 * 
 * rTorrentWeb is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * 
 * rTorrentWeb is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with rTorrentWeb.  If not, see <http://www.gnu.org/licenses/>.
 */
defined('SYSPATH') OR die('No direct access allowed.'); ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
	<title>Log in</title>
	<meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
	<base href="<?php echo url::base(false, 'http'); ?>" />
	<link rel="stylesheet" href="res/login.css" type="text/css" />
</head>

<body>
	<div id="container">
		<h1>Log in to rTorrentWeb</h1>
		<p id="error"><?php echo $error; ?></p>
		<form method="post" action="">
			<label for="username">Username:</label>
			<input type="text" name="username" id="username" /><br />
			
			<label for="password">Password:</label>
			<input type="password" name="password" id="password" /><br />
			
			<input type="submit" name="submit" value="Login" />
		</form>
	</div>
</body>
</html>