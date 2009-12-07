<?php
/*
 * rTorrentWeb version 0.1 prerelease
 * $Id$
 * Copyright (C) 2009, Joseph Stubberfield (stubbers101) <stubbers at stubbers101.net>
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

/**
 * Helper for the legal torrents feed
 *
 * The legal torrents feed is not evil, it is gooooooood!!! :-D
 */
class lt_feed
{
	public static function get_torrents($url)
	{
		$formatted_feed = array();
		
		$raw_feed_items = feed::parse($url);
		
		foreach ($raw_feed_items as $raw_feed_item)
		{
			$formatted_feed[] = array(
				'title' => $raw_feed_item['title'],
				'guid' => $raw_feed_item['guid'],
				'torrent_url' => $raw_feed_item['enclosure']->attributes()->url,
			);
		}
		
		return $formatted_feed;
	}
}
?>