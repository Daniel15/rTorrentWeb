<?php
/*
 * rTorrentWeb version 1.1 Beta
 * $Id$
 * Copyright (C) 2009-2010, Daniel Lo Nigro (Daniel15) <daniel at d15.biz>
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

<p>This page will let you configure basic settings relating to your rTorrentWeb installation.</p>
<?php
// Any errors?
if (!empty($errors))
{
	echo '
	<div class="errors">
		The following errors were encountered:
		<ul>';
	
	foreach ($errors as $key => $error)
		echo '
			<li><strong>', $key, '</strong>: ', $error, '</li>';
	echo '
		</ul>
	</div>';
}
?>

<?php echo form::open('admin', array('id' => 'settings')) ?>
<h2>rTorrentWeb Settings</h2>
	<p>
		<label for="rpcurl">rTorrent RPC URL:</label>
		<input type="text" name="rpcurl" id="rpcurl" size="50" value="<?php echo $settings['rpcurl']; ?>" /><br />
		<small>This is the URL to rTorrent's XMLRPC. If you followed the XMLRPC setup instructions, this will usually be at http://localhost/RPC2/.</small>
	</p>
	<p>
		<label for="metadata_dir">Metadata Directory:</label>
		<input type="text" name="metadata_dir" id="metadata_dir" size="80" value="<?php echo $settings['metadata_dir']; ?>" /><br />
		<small>This is the directory .torrent files are stored in.</small>
	</p>
	<p>
		<label for="torrent_dir">Torrent Directory:</label>
		<input type="text" name="torrent_dir" id="torrent_dir" size="80" value="<?php echo $settings['torrent_dir']; ?>" /><br />
		<small>This is the directory the torrent data (ie. the files actually inside the torrent) is stored in. Each user will get their own directory underneath here.</small>
	</p>
	
<h2>rTorrent Settings</h2>
	<p>
		<label for="up_rate">Limit Upload Speed</label>
		<input type="text" name="up_rate" id="up_rate" size="2" value="<?php echo $settings['up_rate']; ?>" />
		<small>kb/s</small>
		<label for="down_rate">Limit Download Speed</label>
		<input type="text" name="down_rate" id="down_rate" size="2" value="<?php echo $settings['down_rate']; ?>" />
		<small>kb/s</small><br />
		<small>This sets the hard server wide download/upload speed limits on rTorrent. This will apply to all users in total, not individually.</small>
	</p>
	
	<p><input type="submit" name="submit" value="Save Changes" /></p>
</form>