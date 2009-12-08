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

<?php
if(isset($feed_items))
{
echo form::open('torrents/add', array(), array('label_id' => $label_id)); ?>
<input type="hidden" name="type" value="url" />

<table class="feed_items" width="100%" cellpadding="0" cellspacing="0">
	<thead>
		<tr>
			<th>Torrent Name</th>
			<th>Add?</th>
		<tr>
	</thead>
	<tfoot>
		<tr>
			<td>&nbsp;</td>
			<td class="add_col"><input type="submit" name="submit" value="Add Torrents" /></td>
		</tr>
	</tfoot>
	<tbody>
	<?php 
	$feed_item_is_new = true;
	
	foreach ($feed_items as $feed_item)
	{
		if ($feed_item['guid'] == $last_seen_guid)
			$feed_item_is_new = false;
		
	?>
	<tr class="normal">
		<td><?php echo $feed_item['title']?>
		<?php
		if ($feed_item_is_new)
			echo ' - <strong>NEW</strong>';
		?>
		</td>
		<td class="add_col"><input type="checkbox" name="torrent_url[]" value="<?php echo $feed_item['torrent_url']; ?>" /></td> 
	</tr>
	<?php
	}
	?>
	</tbody>
</table>
</form>
<?php
}
?>

<ul class="action_buttons">
	<?php
	echo '
	<li><a href="' . url::site('feed/external/delete/' . $feed_id) . '">Delete feed</a></li>
	<li><a href="' . url::site('feed/external/edit/' . $feed_id) . '">Edit feed</a></li>';
	?>