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

<table width="100%" cellpadding="0" cellspacing="0">
	<thead>
		<tr>
			<th>Feed Name</th>
			<th>Actions</th>
		<tr>
	</thead>
	<tbody>
		<?php
		foreach ($feeds as $feed)
		{
			echo '<tr>';
			echo '<td>' . $feed->name . '</td>';
			echo '<td><center><a href="' . url::site('feed/view/' . $feed->id) . '">View Feed</a> | <a href="' . url::site('feed/delete/' . $feed->id) . '">Delete Feed</a></center></td>'; // TODO : Yes dan, this is wrong, but i'm tired...
			echo '</tr>';
		}
		?>
	</tbody>
</table>

<p><a href="<?php echo url::site('feed/add'); ?>">Add new feed</a></p>