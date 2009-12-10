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
	<title><?php echo $title ?> &mdash; rTorrentWeb</title>
	<meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
	<base href="<?php echo url::base(false, 'http'); ?>" />
	<link rel="stylesheet" href="res/main.css" type="text/css" />
	<script type="text/javascript" src="res/mootools-1.2.3-core-yc.js"></script>
	<script type="text/javascript">
		var base_url = '<?php echo url::base(true); ?>';
	</script>
	<script type="text/javascript" src="res/main.js"></script>
</head>

<body>
	<div id="container">
		<div id="header">
			<h1><?php echo $title; ?></h1>
			<ul id="nav"><?php
// Here comes the menu
$menu_items = array(
	'' => 'Go Back Home',
	'profile/labels' => 'Labels',
	'profile/feeds/external' => 'Feeds'
);

// Admins are SPECIAL
if ($this->auth->logged_in('admin'))
{
	$menu_items += array(
		'admin' => 'General Settings',
		'admin/users' => 'Users',
		'admin/about' => 'About',
	);
}

$menu_items += array(
	'user/logout' => 'Logout',
);
foreach ($menu_items as $uri => $title)
{
	echo '
				<li', url::current() == $uri ? ' class="selected"': '', '><a href="', url::site($uri), '">', $title, '</a></li>';
}
?>

			</ul>
		</div>
		<div id="content">
<?php
if (!empty($top_message))
	echo '
			<p id="top_message">', $top_message, '</p>
';
?>
<?php echo $content; ?>
	
		</div>
		
		<div id="footer">
			<p>Powered by <a href="http://rtorrentweb.com/">rTorrentWeb</a> <?php echo VERSION; ?></p>
		</div>
	</div>
</body>
</html>