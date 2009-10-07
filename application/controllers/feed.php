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
	 * Add a new RSS feed
	 */
	public function add()
	{
		// Did they actually submit the form?
		if (isset($_POST['submit']))
		{
			// Validation
			$post = Validation::factory($_POST)->
				add_rules('feed_url', 'required', 'valid::url_ok')->
				add_rules('feed_name', 'required', 'alpha_numeric');
			
			if (!$post->validate())
			{
				// TODO : Proper error handling
				echo 'Some errors were encountered while adding your torrent:<br />
<ul>
	<li>', implode('</li>
	<li>', $post->errors()), '</li>
</ul>';
				die();
			}
			
			$feed = ORM::factory('feed');
			$feed->url = $this->input->post('feed_url');
			$feed->name = $this->input->post('feed_name');
			$feed->user_id = $this->user->id;
			$feed->save();
			
			url::redirect('/feed/manage/'); // TODO : Ajax Options
		}
		else
		{
			$template = new View('template_popup');
			$template->title = 'Add New Feed';
			$template->content = new View('feed/add');
			$template->render(true);
		}
	}
	
	/**
	 * Delete an RSS feed
	 */
	public function delete($id)
	{
		if (!$this->_check_exists($id))
			url::redirect(''); // TODO : Proper page
		
		if (!$this->_check_owner($id))
			url::redirect('');
		
		ORM::factory('feed', $id)->delete();
		
		url::redirect('/feed/manage/'); // TODO : Ajax Options
	}
	
	/**
	 * View a feeds items
	 */
	public function view($id)
	{
		if (!$this->_check_exists($id))
			url::redirect(''); // TODO : Proper page
		
		if (!$this->_check_owner($id))
			url::redirect('');
		
		$feed = ORM::factory('feed', $id);
		
		// TODO : Feed Error Checking
		
		$template = new View('template_popup');
		$template->title = 'RSS Entries For : ' . $feed->name;
		$template->content = new View('feed/view');
		$template->content->feed = feed::parse($feed->url);
		$template->content->feed_id = $id;
		$template->render(true);
	}
	
	/**
	 * Manage feeds
	 */
	public function manage()
	{
		$template = new View('template_popup');
		$template->title = 'Manage RSS Feeds';
		$template->content = new View('feed/manage');
		$template->content->feeds = ORM::factory('feed')->where('user_id', $this->user->id)->find_all();
		$template->render(true);
	}
	
	/**
	 * Check if we own the feed
	 */
	private function _check_owner($id)
	{
		$feed = ORM::factory('feed', $id);
		
		return ($feed->user_id == $this->user->id);
	}
	
	/**
	 * Check if the feed exists
	 */
	private function _check_exists($id)
	{
		return ORM::factory('feed', $id)->loaded;
	}
}
?>