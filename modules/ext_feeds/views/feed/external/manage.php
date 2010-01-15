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

<script type="text/javascript">window.addEvent('domready', Feeds.init);</script>
<h2>Current Feeds</h2>
<?php
if (count($feeds) == 0)
	echo '<p>You do not currently have any feeds.</p>';
?>
<ul class="feeds">
	<?php
	foreach ($feeds as $feed)
	{
	echo '
	<li class="feed"><a href="' . url::site('feed/external/view/' . $feed->id) . '">' . $feed->name . '</a>
	<ul class="feed_options">
		<li>' . $feed->url . '</li>
		<li><a href="' . url::site('profile/feeds/external/edit/' . $feed->id) . '"><img src="res/icons16/feed_edit.png" alt="Edit Feed" title="Edit Feed"/></a></li>
		<li><a href="' . url::site('profile/feeds/external/delete/' . $feed->id) . '"><img src="res/icons16/bin_closed.png" alt="Delete Feed" title="Delete Feed"/></a></li>
	</ul>
	</li>';
	}
	?>
</ul>

<h2>Add A New Feed</h2>

<?php echo form::open('profile/feeds/external/add'); ?>
	<p><label for="name">Feed Name:</label> <?php echo form::input('name'); ?></p>
	<p><label for="url">Feed URL:</label> <?php echo form::input('url'); ?></p>
	<p><label for="auto_start">Automatically Start Torrents By Default:</label><?php echo form::checkbox('auto_start', 'true', TRUE); ?></p>
	<p><?php echo form::submit('submit','Add Feed'); ?></p>
</form>