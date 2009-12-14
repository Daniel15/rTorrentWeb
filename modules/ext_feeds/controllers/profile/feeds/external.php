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
	public $template = 'template';
	
	/**
	 * List the feeds
	 */
	public function index()
	{
		$this->template->title = 'Feeds';
		$page = View::factory('feed/external/manage');
		$page->feeds = $this->user->ext_feeds;
		$this->template->content = $page;
	}
	
	/**
	 * Add a new feed
	 */
	public function add()
	{
		$this->template->title = 'Add A New Feed';
		
		if (isset($_POST['submit'])) // has a form been submitted
		{
			$feed = ORM::factory('ext_feed');
			$post = $_POST;
			
			if ($feed->validate($post)) // there are no validation errors
			{
				if (torrent_feed::_is_feed_supported($this->input->post('url')) || $this->input->post('confirmed')) // we are adding a support feed or if the feed has been confirmed
				{
					// OK to add feed and its label
					$label = ORM::factory('label');
					$label->name = $feed->name;
					$label->internal = true;
					$label->user_id = $this->user->id;
					$label->icon = 'feed';
					$label->save(); // create the new label for the feed
					
					$feed->label_id = $label->id;
					$feed->user_id = $this->user->id; // set the feeds owner to the current user
					$feed->save(); // save the feed
					
					$this->session->set_flash('top_message', 'Added feed "' . $post['name'] . '".');
					url::redirect('profile/feeds/external'); // redirect back to the feed management page
				}
				else // we are adding an unsupported feed and it needs confirmation
				{
					$this->template->top_message = 'The URL you have entered leads to a site not supported by rTorrentWeb at this time. The feed may not work as intended with rTorrentWeb if it is not standards compliant. If you want to still try using the feed with rTorrentWeb, please confirm its details, then press the "Confirm Feed Details" button.';
					
					$page = View::factory('feed/external/feed_form');
					$page->url = $this->input->post('url');
					$page->name = $this->input->post('name');
					$page->submit_text = 'Confirm Feed Details';
					$page->hidden_fields = array('confirmed' => 'true');
					$this->template->content = $page;
				}
			}
			else // there are some validation errors
			{
				$page = View::factory('feed/external/feed_form');
				$page->url = $this->input->post('url');
				$page->name = $this->input->post('name');
				$page->submit_text = 'Add Feed';
				$page->add_errors = $post->errors();
				$this->template->content = $page;
			}
		}
		else
		{
			$page = View::factory('feed/external/feed_form');
			$page->url = '';
			$page->name = '';
			$page->submit_text = 'Add Feed';
		}
		
		$this->template->content = $page;
	}
	
	/**
	 * Edit a feed
	 */
	public function edit($id)
	{
		if(!$this->_check_exists($id)) // does the feed not exist
		{
			$this->session->set_flash('top_message', 'You cannot edit a feed that doesn\'t exist');
			url::redirect('profile/feeds/external');
		}
		
		if(!$this->_check_owner($id)) // is the logged in user authorised to edit the feed
		{
			$this->session->set_flash('top_message', 'You cannot edit someone elses feed');
			url::redirect('profile/feeds/external');
		}
		
		$this->template->title = 'Edit Feed';
		$feed = ORM::factory('ext_feed', $id);
		
		if (isset($_POST['submit'])) // has a form been submitted
		{
			$post = $_POST;
			
			if ($feed->validate($post)) // there are no validation errors
			{
				if (torrent_feed::_is_feed_supported($this->input->post('url')) || $this->input->post('confirmed')) // we are adding a support feed or if the feed has been confirmed
				{
					$feed->user_id = $this->user->id; // set the feeds owner to the current user
					$feed->save(); // save the feed
					
					$label = ORM::factory('label', $feed->label_id);
					$label->name = $this->input->post('name');
					$label->save();
					
					$this->session->set_flash('top_message', 'Feed "' . $post['name'] . '" saved.');
					url::redirect('profile/feeds/external'); // redirect back to the feed management page
				}
				else // we are adding an unsupported feed and it needs confirmation
				{
					$this->template->top_message = 'The URL you have entered leads to a site not supported by rTorrentWeb at this time. The feed may not work as intended with rTorrentWeb if it is not standards compliant.';
					
					$page = View::factory('feed/external/feed_form');
					$page->url = $this->input->post('url');
					$page->name = $this->input->post('name');
					$page->submit_text = 'Confirm Feed Details';
					$page->hidden_fields = array('confirmed' => 'true');
					$this->template->content = $page;
				}
			}
			else // there are some validation errors
			{
				$page = View::factory('feed/external/feed_form');
				$page->url = $this->input->post('url');
				$page->name = $this->input->post('name');
				$page->submit_text = 'Save Feed';
				$page->add_errors = $post->errors();
				$this->template->content = $page;
			}
		}
		else
		{
			$page = View::factory('feed/external/feed_form');
			$page->url = $feed->url;
			$page->name = $feed->name;
			$page->submit_text = 'Save Feed';
		}
		
		$this->template->content = $page;
	}
	
	/**
	 * Delete a feed
	 */
	public function delete($id)
	{
		if(!$this->_check_exists($id)) // does the feed not exist
		{
			$this->session->set_flash('top_message', 'You cannot delete a feed that doesn\'t exist');
			url::redirect('profile/feeds/external');
		}
		
		if(!$this->_check_owner($id)) // is the logged in user authorised to edit the feed
		{
			$this->session->set_flash('top_message', 'You cannot delete someone elses feed');
			url::redirect('profile/feeds/external');
		}
		
		$feed = ORM::factory('ext_feed', $id); // get the feed
		ORM::factory('label', $feed->label_id)->delete(); // delete the label associated wtih the feed
		$feed->delete(); // delete the feed
		
		$this->session->set_flash('top_message', 'Feed ' . $feed->name . ' deleted.');
		url::redirect('profile/feeds/external'); // redirect to the management page
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
}

?>