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
defined('SYSPATH') OR die('No direct access allowed.');

function output_dir($data, $level = 0, $current_dir = 0)
{
	static $dir_num = 0;
	
	echo '
<ul class="files">';
	// Go through all the files first
	foreach ($data['files'] as $file)
	{
		echo '
	<li class="file"><label><input type="checkbox" name="files[', $file['num'], ']" /> <img src="res/icons16/file.png" alt="File" title="', $file['name'], ' [File]" /> ', $file['name'], '</label></li>';
	}
	
	// Now go through the directories
	foreach ($data['dirs'] as $dir => $contents)
	{
		echo '
	<li class="dir">
		<label><input type="checkbox" /> <img src="res/icons16/folder.png" alt="Directory" title="', $dir, ' [Directory]" /> ', $dir, '</label>';
		
		output_dir($contents, $level + 1, $dir_num);
		
		echo '
	</li>';
	}
echo '
</ul>';
}
?>
<script type="text/javascript">window.addEvent('domready', AddFiles.init);</script>
<?php echo form::open() ?>

	<p>Select the files you want to download from this torrent:</p>
	<p>
		<input id="all" type="button" value="Select All" />
		<input id="invert" type="button" value="Invert Selection" />
		<input name="submit" type="submit" value="Start Download" />
	</p>
	
<?php output_dir(array('dirs' => $dirs, 'files' => $files)); ?>

	<p><input name="submit" type="submit" value="Start Download" /></p>
</form>