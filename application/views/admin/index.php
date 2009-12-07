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

<p>This page will let you configure basic settings relating to your rTorrentWeb installation.</p>

<?php echo form::open('admin', array('id' => 'settings')) ?>
	<p>
		<label for="rpcurl">rTorrent RPC URL:</label>
		<input type="text" name="rpcurl" id="rpcurl" size="50" value="<?php echo $this->config->get('rpcurl'); ?>" /><br />
		<small>This is the URL to rTorrent's XMLRPC. If you followed the XMLRPC setup instructions, this will usually be at http://localhost/RPC2/.</small>
	</p>
	<p>
		<label for="metadata_dir">Metadata Directory:</label>
		<input type="text" name="metadata_dir" id="metadata_dir" size="80" value="<?php echo $this->config->get('metadata_dir'); ?>" /><br />
		<small>This is the directory .torrent files are stored in.</small>
	</p>
	<p>
		<label for="torrent_dir">Torrent Directory:</label>
		<input type="text" name="torrent_dir" id="torrent_dir" size="80" value="<?php echo $this->config->get('torrent_dir'); ?>" /><br />
		<small>This is the directory the torrent data (ie. the files actually inside the torrent) is stored in. Each user will get their own directory underneath here.</small>
	</p>
	
	<p><input type="submit" name="submit" value="Save Changes" /></p>
</form>
