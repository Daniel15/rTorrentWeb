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
	<title>rTorrent</title>
	<meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
	<base href="<?php echo url::base(false, 'http'); ?>" />
	<link rel="stylesheet" href="res/listing.css" type="text/css" />
	<script type="text/javascript" src="res/mootools-1.2.3-core-yc.js"></script>
	<!--script type="text/javascript" src="res/mootools-1.2.3-core-nc.js"></script-->
	<script type="text/javascript" src="res/mootools-1.2.3.1-more.js"></script>
	<script type="text/javascript">
		var base_url = '<?php echo url::base(true); ?>';
	</script>
	<script type="text/javascript" src="res/listing.js"></script>
</head>

<body>
	<h1>rTorrentWeb</h1>
	<div id="top_pane">
		<p id="toolbar_status">
			<span id="toolbar_message">Status</span> <span id="refresh_count"></span> <a id="disable_auto" href="#">Disable automatic refreshing</a> <a id="enable_auto" href="#">Enable automatic refreshing</a> <img id="loading" src="res/loading.gif" alt="Loading..." title="Loading..." />
		</p>
		<div id="toolbar">
			<ul>
				<li><img id="refresh" src="res/icons/refresh.png" alt="Refresh" title="Refresh" class="disabled" /></li>
				<li><a id="add" href="<?php echo url::site('torrents/add'); ?>"><img src="res/icons/add.png" alt="Add New Torrent" title="Add New Torrent" /></a></li>
				<li class="end-section"><a id="rss" href="<?php echo url::site('feed/external/manage'); ?>"><img src="res/icons/rss.png" alt="Manage RSS" title="Manage RSS" /></a></li>
				<li><img id="start" src="res/icons/play.png" alt="Start Torrent" title="Start Torrent" class="disabled" /></li>
				<li><img id="pause" src="res/icons/pause.png" alt="Pause Torrent" title="Pause Torrent" class="disabled" /></li>
				<li><img id="stop" src="res/icons/stop.png" alt="Stop Torrent" title="Stop Torrent" class="disabled" /></li>
				<li class="end-section"><img id="delete" src="res/icons/trash.png" alt="Delete Torrent" title="Delete Torrent" class="disabled" /></li>
				<li><a href="<?php echo url::site('user/logout'); ?>"><img src="res/icons/logout.png" alt="Log Out" title="Log Out" /></a></li>
			</ul>
		</div>
		
		<div id="sidebar">
			<ul>
				<li id="sidebar_all" class="selected">All (<span>x</span>)</li>
				<li id="sidebar_seeding">Seeding (<span>x</span>)</li>
				<li id="sidebar_downloading">Downloading (<span>x</span>)</li>
				<li id="sidebar_finished">Finished (<span>x</span>)</li>
				<li id="sidebar_stopped">Stopped (<span>x</span>)</li>
				<li id="sidebar_paused">Paused (<span>x</span>)</li>
			</ul>
		</div>
		
		<div id="torrents">
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
				<div>
					<h3>Transfer</h3>
					<p>
						Time: <span id="elapsed"></span> elapsed, <span id="remaining"></span> remaining<br />
						Speed: <span id="download_speed"></span> down, <span id="upload_speed"></span> up<br />
						Totals: <span id="total_down"></span> downloaded, <span id="total_up"></span> uploaded<br />
					</p>
				</div>
				<div>
					<h3>General</h3>
					<p>						
						Hash: <span id="hash"></span><br />
						Owner: <span id="owner"></span><br />
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
							<th>Seeder/Leacher</th>
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
	</div>
</body>
</html>