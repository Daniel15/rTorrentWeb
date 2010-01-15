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
header('Content-type: text/javascript');
header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
header('Cache-Control: no-store, no-cache, must-revalidate');
?>
var Settings = 
{
	'only_mine': <?php echo !empty($settings['only_mine']) ? 'true' : 'false'; ?>,
	'autorefresh': <?php echo !empty($settings['autorefresh']) ? 'true' : 'false'; ?>,
	'autorefresh_interval': <?php echo $settings['autorefresh_interval']; ?>,
	'sidebar_width': '<?php echo $settings['sidebar_width']; ?>',
	'labels': new Hash(<?php echo json_encode($labels); ?>),
	'customstatus_line' : "<?php echo $settings['customstatus_line']; ?>"
};