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

class Feed_Controller extends Base_Controller
{
	/**
	 * Create new outgoing RSS feed
	 */
	public function add()
	{
	
	}
	
	/**
	 * Get an outgoing RSS feed
	 */
	public function get($key)
	{
		if (!$this->_check_exists($key))
			url::redirect(''); // TODO : Proper page
			
		/**
		 * It should be noted there is no authentication on getting a feed
		 *
		 * The idea is that the key is to be a randomly generated string
		 * of decent complexity, this negates the need for authentication
		 * while getting an outgoing RSS feed
		 */
		
		/**
		 * TODO : Filters to be implemented
		 *
		 * User Filters
		 * - standard					- shows all items owned by user and all other public items
		 * - admin override		- overrides permissions and shows all items
		 * - user only					- only shows items owned by the creator of the feed
		 *
		 * Status Filters
		 * - only show completed 	- self explanatory???
		 */
		
		
	}
	
	/**
	 * Delete an outgoing RSS feed
	 */
	public function delete($key)
	{
		if (!$this->_check_exists($key))
			url::redirect(''); // TODO : Proper page
		
		if (!$this->_check_owner($key))
			url::redirect('');
		
		ORM::factory('rss', $key)->delete();
		
		url::redirect('/rss/manage/'); // TODO : Ajax Options
	}
	
	/**
	 * Manage outgoing RSS feeds
	 */
	public function manage()
	{
	
	}
	
	/**
	 * Check if we own the feed
	 */
	private function _check_owner($key)
	{
		$feed = ORM::factory('rss', $key);
		
		return ($feed->user_id == $this->user->id);
	}
	
	/**
	 * Check if the feed exists
	 */
	private function _check_exists($key)
	{
		return ORM::factory('rss', $key)->loaded;
	}	 
}
?>