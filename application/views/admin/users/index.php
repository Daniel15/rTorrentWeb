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

<script type="text/javascript">window.addEvent('domready', Users.init);</script>

<h2>Current Users</h2>
<ul>
<?php
foreach ($users as $user)
{
	echo '
	<li>
		<a class="delete" href="', url::site('admin/users/delete/' . $user->id), '"><img src="res/icons16/bin_closed.png" alt="Delete" title="Delete ', $user->username, '" /></a>
		<a href="', url::site('admin/users/change_password/' . $user->id), '"><img src="res/icons16/key.png" alt="Change Password" title="Change ', $user->username, '\'s password" /></a>
		', $user->username, '
	</li>';
}
?>
</ul>
<h2>Add a new user</h2>
<?php echo form::open('admin/users/add', array('id' => 'add_user')) ?>
<?php echo $form; ?>
</form>