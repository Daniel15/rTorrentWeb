<?php
/*
 * rTorrentWeb version 0.1 prerelease
 * $Id$
 * Copyright (C) 2009, Joseph Stubberfield (stubbers101) <stubbers at stubbers101.net>
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

<?php echo form::open_multipart('feed/external/add') ?>
	<p>
		<label for="feed_name">Feed Name:</label><?php echo form::input('feed_name',$feed_name); ?><br />
		<label for="feed_url">Feed URL:</label><?php echo form::input('feed_url',$feed_url); ?><br />
	</p>
	<p><?php echo form::submit('submit','Add Feed'); ?></p>
</form>