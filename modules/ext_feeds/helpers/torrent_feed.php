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
 * Torrent feed helper
 */
class torrent_feed
{
	/**
	 * Selects the appropriate handler for the RSS feed or attempts to use the default handler
	 */
	public static function get_torrents($url)
	{
		$hostname = parse_url($url, PHP_URL_HOST);
		
		switch($hostname)
		{
			case 'rss.torrentleech.org':
			case 'www.rss.torrentleech.org':
				return tl_feed::get_torrents($url);
			
			case 'www.legaltorrents.com':
			case 'legaltorrents.com':
				return lt_feed::get_torrents($url);
				
			case 'www.mininova.org':
			case 'mininova.org':
				return mininova_feed::get_torrents($url);
			
			case 'www.ezrss.it':
			case 'ezrss.it':
				return eztv_feed::get_torrents($url);
		}
		
		// Begin default feed parsing
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
	
	/**
	 * Is a particular site supported by RTWeb at this stage
	 */
	public static function _is_feed_supported($url)
	{
		$supported_hosts = array(
			'rss.torrentleech.org',
			'www.rss.torrentleech.org',
			'www.legaltorrents.com',
			'legaltorrents.com',
			'www.mininova.org',
			'mininova.org',
			'www.ezrss.it',
			'ezrss.it',
		);
		
		return in_array(parse_url($url, PHP_URL_HOST), $supported_hosts);
	}
}
?>