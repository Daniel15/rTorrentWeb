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
<!-- TODO: This should be in the head tag -->
<script type="text/javascript">window.addEvent('domready', Add.init);</script>
<?php echo form::open_multipart('torrents/add') ?>
	<p>
		<input type="radio" name="type" id="type_file" value="file" checked="checked" />
		<label for="type_file">Upload torrent file</label>
		
		<input type="radio" name="type" id="type_url" value="url" />
		<label for="type_url">Specify torrent URL</label>
	</p>
	<p id="file">
		<label for="torrent_file">Torrent file:</label> <input type="file" name="torrent_file" id="torrent_file" /><br />
		<!--label for="dir">Download directory:</label> <img src="res/icons16/house.png" alt="<?php echo $homedir; ?>" title="Your home directory - <?php echo $homedir; ?>" /> / <input type="text" name="dir" id="dir" /-->
	</p>
	
	<p id="url">
		<label for="torrent_url">Torrent URL:</label> <input type="text" name="torrent_url" id="torrent_url" /><br />
	</p>
	
	<p>
		<input type="checkbox" name="private" id="private" /> <label for="private">Private?</label> <small>(tick this if you don't want other users seeing this torrent)</small><br />
		<input type="checkbox" name="choose_files" id="choose_files" /> <label for="choose_files">Choose files to download?</label> <small>(tick this if you want to choose exactly which files to download from this torrent)</small><br />
	</p>
	
	<p><input name="submit" type="submit" value="Add Torrent" /></p>
</form>