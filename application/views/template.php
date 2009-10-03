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
	<title><?php echo $title ?></title>
	<meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
	<base href="<?php echo url::base(false, 'http'); ?>" />
	<link rel="stylesheet" href="res/style.css" type="text/css" />
	<script type="text/javascript" src="res/mootools-1.2.3-core-yc.js"></script>
	<script type="text/javascript" src="res/mootools-1.2.3.1-more.js"></script>
	<script type="text/javascript">
		var base_url = '<?php echo url::base(true); ?>';
	</script>
	<script type="text/javascript" src="res/script.js"></script>
</head>

<body>
	<h1><?php echo $title; ?></h1>
	<?php echo $content; ?>
</body>
</html>