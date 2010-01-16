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
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
	<title>rTorrentWeb - Logged in as <?php echo $this->user->username; ?></title>
	<meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
	<base href="<?php echo url::base(false, 'http'); ?>" />
	<link rel="stylesheet" href="res/listing.css" type="text/css" />
	<script type="text/javascript" src="res/mootools-1.2.3-core-yc.js"></script>
	<script type="text/javascript" src="res/mootools-1.2.4.2-more.js"></script>
	<script type="text/javascript" src="<?php echo url::site('profile/get_settings'); ?>"></script>
	<script type="text/javascript">
		var base_url = '<?php echo url::base(true); ?>';
		var current_user = <?php echo $this->user->id; ?>;
		/* This is not for security; it's just for convenience (only attempting
		 * certain things if we're an admin). Everything is checked server-side,
		 * so messing with it will just produce an error anyways. :P */
		var is_admin = <?php echo $this->auth->logged_in('admin') ? 'true' : 'false'; ?>;
	</script>
	<script type="text/javascript" src="res/listing.js"></script>
</head>

<body>
	<h1>rTorrentWeb</h1>
	<div id="top_pane">
		<div id="top_bar">
			<div id="toolbar">
				<ul>
					<li><img id="refresh" src="res/icons/refresh.png" alt="Refresh" title="Refresh" class="disabled" /></li>
					<li><a id="add" href="<?php echo url::site('torrents/add'); ?>"><img src="res/icons/add.png" alt="Add New Torrent" title="Add New Torrent" /></a></li>
					<li class="end-section"<?php echo !$has_rss ? ' style="display: none"' : '' ?>><a id="rss" href="<?php echo url::site('feed/external/'); ?>"><img src="res/icons/rss.png" alt="View RSS" title="View RSS" /></a></li>
					<li><img id="start" src="res/icons/play.png" alt="Start Torrent" title="Start Torrent" class="disabled" /></li>
					<li><img id="pause" src="res/icons/pause.png" alt="Pause Torrent" title="Pause Torrent" class="disabled" /></li>
					<li><img id="stop" src="res/icons/stop.png" alt="Stop Torrent" title="Stop Torrent" class="disabled" /></li>
					<li class="end-section"><img id="delete" src="res/icons/trash.png" alt="Delete Torrent" title="Delete Torrent" class="disabled" /></li>
					<li class="end-section"><a href="<?php echo url::site('profile'); ?>"><img src="res/icons/admin.png" alt="Administration" title="Administration" /></a></li>
					<li><a href="<?php echo url::site('user/logout'); ?>"><img src="res/icons/logout.png" alt="Log Out" title="Log Out" /></a></li>
				</ul>
			</div>
			<p id="toolbar_status">
				<a id="disable_auto" href="#">Disable automatic refreshing</a> <a id="enable_auto" href="#">Enable automatic refreshing</a> <img id="loading" src="res/loading.gif" alt="Loading..." title="Loading..." />
				
				<input id="search" type="text" title="Type here to search for torrents" />
			</p>
		</div>
		
		<div id="sidebar">
			<ul>
				<li class="end-section"><input type="checkbox" name="only_mine" id="only_mine" /> <label for="only_mine" title="If selected, only shows your torrents, instead of showing all public torrents">Only my torrents</label></li>
				<li id="sidebar_all" class="selected filter">All (<span>x</span>)</li>
				<li id="sidebar_seeding" class="filter">Seeding (<span>x</span>)</li>
				<li id="sidebar_downloading" class="filter">Downloading (<span>x</span>)</li>
				<li id="sidebar_finished" class="filter">Finished (<span>x</span>)</li>
				<li id="sidebar_stopped" class="filter">Stopped (<span>x</span>)</li>
				<li id="sidebar_paused" class="filter end-section">Paused (<span>x</span>)</li>
			</ul>
		</div>
		
		<div id="torrents" tabindex="1">
			<table width="100%" cellpadding="0" cellspacing="0">
				<thead>
					<tr>
						<th>Name</th>
						<th>Status</th>
						<th>Size</th>
						<th>Done</th>
						<th>DL rate</th>
						<th>UL rate</th>
						<th>Ratio</th>
					</tr>
				</thead>
				<tbody>
				</tbody>
			</table>
		</div>
	</div>
	
	<div id="bottom_pane">
		<div id="tabs">
			<ul>
				<li id="tab_general" class="selected"><img src="res/icons16/application_view_detail.png" title="General" alt="General" /> General</li>
				<li id="tab_files"><img src="res/icons16/file.png" title="Files" alt="Files" /> Files</li>
				<li id="tab_peers"><img src="res/icons16/user.png" title="Peers" alt="Peers" /> Peers</li>
				<li id="tab_log"><img src="res/icons16/cog.png" title="Log" alt="Log" /> Log</li>
			</ul>
		</div>
		<div id="details">
			<div id="general">
				<p id="no_torrent">No torrent is currently selected.</p>
				<div>
					<h3>General</h3>
					<p>						
						Hash: <span id="hash"></span><br />
						Owner: <span id="owner"></span><?php
// If they're an admin, they can change the owner
if ($this->auth->logged_in('admin')) :
?>

						<select id="owner_dropdown" name="owner_dropdown">
							<option>Loading...</option>
						</select>
						<input id="owner_change" type="button" value="Change" />
						<input id="owner_save" type="button" value="Save" />
<?php endif; ?><br />
						Private: <span id="private">no</span> <input id="private_change" type="button" value="Change" /><br />
					</p>
					<p>Labels:</p>
					<ul id="labels">
						<li>Loading...</li>
					</ul>
					<p id="no_labels">There are currently no other labels available to attach.</p>
					<p id="attach_label">
						Attach a label: 
						<select id="label_dropdown" name="label_dropdown"></select>
						<input id="label_add" type="button" value="Add" />
					</p>
				</div>
				<div>
					<h3>Transfer</h3>
					<p id="times">
						Started: <span id="started"></span><br />
						Estimated Completion: <span id="remaining"></span><br />
					</p>
					<p>
						Speed: <span id="download_speed"></span> down, <span id="upload_speed"></span> up<br />
						Totals: <span id="total_down"></span> downloaded, <span id="total_up"></span> uploaded<br />
					</p>
				</div>
			</div> 
			<div id="files">
				<span>Loading file listing for [x]...</span>
				<table cellpadding="0" cellspacing="0">
					<thead>
						<tr>
							<th>Name</th>
							<th>Size (chunks)</th>
							<th>Done (chunks)</th>
							<th>Priority</th>
						</tr>
					</thead>
					<tbody>
					</tbody>
				</table>
			</div>
			<div id="peers">
				<span>Loading peer listing for [x]...</span>
				<table cellpadding="0" cellspacing="0">
					<thead>
						<tr>
							<th>IP Address</th>
							<th>Client Version</th>
							<th>Down Rate</th>
							<th>Up Rate</th>
							<th>Down Total</th>
							<th>Up Total</th>
						</tr>
					</thead>
					<tbody>
					</tbody>
				</table>
			</div>
			<div id="log">
				<ul>
				</ul>
			</div>
		</div>
		<div id="statusbar">
			<p id="serverinfo">
				Server statistics:
				Loading.
			</p>
			<p id="statusinfo">
				<span id="toolbar_message">Status</span>
				<span id="refresh_count"></span>
			</p>
		</div>
	</div>
</body>
</html>