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

class External_Controller extends Base_Controller
{
	/**
	 * List feeds
	 */
	public function index()
	{
		$this->template = new View('template_popup');
		$this->template->title = 'Feeds';
		$this->template->content = new View('feed/external/list');
		$this->template->content->feeds = $this->user->ext_feeds;
		$this->template->render(true);
	}
	
	/**
	 * View a feeds items
	 */
	public function view($id)
	{
		if (!$this->_check_exists($id))
		{
			$this->_render_error('You cannot view a feed that doesn\'t exist');
			die();
		}
		
		if (!$this->_check_owner($id))
		{
			$this->_render_error('You cannot view a feed that you do not own');
			die();
		}
		
		$feed = ORM::factory('ext_feed', $id);		
		$feed_items = torrent_feed::get_torrents($feed->url);
		
		if (count($feed_items) < 1) // are there items in the feed
		{
			if (torrent_feed::_is_feed_supported($feed->url))
			{
				$this->_render_error('There are currently no valid items in this RSS feed. There may be a problem with your feed provider. Please check the url ' . $feed->url . ' in your browser.');
				die();
			}
			else
			{
				$this->_render_error('This feed has not provided any valid items. For an item to be valid it must have a title, guid, and enclosure attribute. Otherwise it will not be accepted by rTorrentWeb. This feed is not supported by rTorrentWeb.');
				die();
			}
		}
		
		$template = new View('template_popup');
		$template->title = 'RSS Entries For : ' . $feed->name;
		$template->content = new View('feed/external/view');
		$template->content->feed_id = $id;
		$template->content->label_id = $feed->label_id;
		$template->content->feed_items = $feed_items;
		$template->content->last_seen_guid = $feed->last_seen_guid;
		$template->content->auto_start = true;
		
		// check to ensure auto start is required
		if ($feed->auto_start == 1)
			$template->content->auto_start = false;
		
		$this->_reset_seen_guid($feed, $feed_items[0]['guid']);
		
		$template->render(true);
	}
	
	/**
	 * Check if we own the feed
	 */
	private function _check_owner($id)
	{
		$feed = ORM::factory('ext_feed', $id);
		
		return ($feed->user_id == $this->user->id);
	}
	
	/**
	 * Check if the feed exists
	 */
	private function _check_exists($id)
	{
		return ORM::factory('ext_feed', $id)->loaded;
	}
	
	/**
	 * Set a new last seen GUID for the feed
	 */
	private function _reset_seen_guid($feed, $new_guid)
	{
		$feed->last_seen_guid = $new_guid;
		$feed->save();
	}
	
	/**
	 * Render an error
	 */
	private function _render_error($message)
	{
		$template = new View('template_popup');
		$template->title = 'An Error Has Occured';
		$template->message = $message;
		$template->content = '';
		$template->render(true);
	}
}
?>