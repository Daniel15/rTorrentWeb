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

// Any errors adding a user?
if (!empty($add_errors))
{
	echo '
	The following errors were encountered:
	<ul>';
	
	foreach ($add_errors as $key => $error)
		echo '
		<li><strong>', $key, '</strong>: ', $error, '</li>';
	echo '
	</ul>';
}
?>
	<p>
		<label for="username">Username:</label> <input type="text" name="username" id="username" value="<?php echo htmlspecialchars($data['username']) ?>" /><br />
		<label for="password">Password:</label> <input type="password" name="password" id="password" /><br />
		<label for="password_confirm">Confirm Password:</label> <input type="password" name="password_confirm" id="password_confirm" /><br />
		<label for="email">E-mail Address:</label> <input type="text" name="email" id="email" value="<?php echo htmlspecialchars($data['email']) ?>" /><br />
	</p>
	
	<h3>Roles</h3>
<?php
foreach ($roles as $role)
{
	echo '
	<p>
		<input type="checkbox" name="roles[]" value="', $role->id, '" id="role_', $role->id, '" ', in_array($role->id, $data['roles']) ? 'checked="checked" ' : '', '/>
		<label class="checkbox" for="role_', $role->id, '">', ucfirst($role->name), '</label>
		<small>', $role->description, '</small>
	</p>';
}
?>
	
	<p><input type="submit" name="submit" value="Submit" /></p>