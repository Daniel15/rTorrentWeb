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

<script type="text/javascript">window.addEvent('domready', Labels.init);</script>

<h2>Current Labels</h2>
<?php
if (count($labels) == 0)
	echo '<p>You do not currently have any labels.</p>';
?>
<ul>
<?php
foreach ($labels as $label)
{
	echo '
	<li><img alt="Label icon" src="res/label_icons/', htmlspecialchars($label->icon), '.png" width="16" height="16" /> ', htmlspecialchars($label->name), '</li>';
}
?>

</ul>

<h2>Add a New Label</h2>
<?php echo form::open('profile/add_label'); ?>
<?php
// Any errors adding a label?
if (!empty($add_errors))
{
	echo '
	Could not add label, the following errors were encountered:
	<ul>';
	
	foreach ($add_errors as $key => $error)
		echo '
		<li><strong>', $key, '</strong>: ', $error, '</li>';
	echo '
	</ul>';
}
?>
	<p>
		<label for="name">Name:</label> <input type="text" name="name" id="name" /><br />
		<label for="icon">Icon:</label>
		<select name="icon" id="icon">
			<option value="blank">No icon</option>
<?php
foreach ($icons as $icon)
{
	echo '
			<option value="', $icon, '">', $icon, '</option>';
}
?>
		</select>
		<img alt="Label icon" src="res/label_icons/blank.png" width="16" height="16" id="label_icon" />
	</p>
	
	<p><input type="submit" name="submit" value="Add Label" /></p>
</form>

</ul>