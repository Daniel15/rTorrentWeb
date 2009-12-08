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
	 * Add a new RSS feed
	 */
	public function add()
	{
		if (isset($_POST['submit'])) // has a form been submitted
		{
			$feed = ORM::factory('ext_feed');
			$post = $_POST;
			
			if ($feed->validate($post)) // there are no validation errors
			{
				if (torrent_feed::_is_feed_supported($this->input->post('url')) || $this->input->post('confirmed')) // we are adding a support feed or if the feed has been confirmed
				{
					$label = ORM::factory('label');
					$label->name = $feed->name;
					$label->internal = true;
					$label->user_id = $this->user->id;
					$label->icon = 'feed';
					$label->save(); // create the new label for the feed
					
					$feed->label_id = $label->id;
					$feed->user_id = $this->user->id; // set the feeds owner to the current user
					$feed->save(); // save the feed

					url::redirect('/feed/external/manage/'); // redirect back to the feed management page
				}
				else // we are adding an unsupported feed and it needs confirmation
				{
					$template = new View('template_popup');
					$template->title = 'Confirm Adding Unsupported Feed';
					$template->message = '<strong>WARNING</strong> : The feed you are creating is not supported by RTorrentWeb at this time, the feed may not function as desired. Please go here <!-- TODO : Add url --> to contact us if you wish to have your site added to the supported list.';
					$template->content = new View('feed/external/feed_form');
					$template->content->url = $this->input->post('url');
					$template->content->name = $this->input->post('name');
					$template->content->submit_text = 'Confirm Feed Details';
					$template->content->hidden_fields = array('confirmed' => 'true');
					$template->render(true);
				}
			}
			else // there are some validation errors
			{
				$template = new View('template_popup');
				$template->title = 'Add New Feed';
				$template->message = 'Some errors where encountered while handling your feed:
				<ul>
					<li>' . implode('</li>
					<li>', $post->errors()) . '</li>
				</ul>';
				$template->content = new View('feed/external/feed_form');
				$template->content->name = $this->input->post('name');
				$template->content->url = $this->input->post('url');
				$template->content->submit_text = 'Add Feed';
				$template->render(true);				
			}
		}
		else // no data has been submitted
		{
			$template = new View('template_popup');
			$template->title = 'Add New Feed';
			$template->content = new View('feed/external/feed_form');
			$template->content->name = '';
			$template->content->url = '';
			$template->content->submit_text = 'Add Feed';
			$template->render(true);
		}
	}
	
	/**
	 * Delete an RSS feed
	 */
	public function delete($id)
	{
		if (!$this->_check_exists($id)) // does the feed not exist
		{
			$this->_render_error('You cannot delete a feed that doesn\'t exist');
			die();
		}
		
		if (!$this->_check_owner($id)) // is the logged in user authorised to delete the feed
		{
			$this->_render_error('You cannot delete someone elses feed');
			die();
		}
		
		$feed = ORM::factory('ext_feed', $id); // get the feed
		ORM::factory('label', $feed->label_id)->delete(); // delete the label associated wtih the feed
		$feed->delete(); // delete the feed
		url::redirect('/feed/external/manage/'); // redirect to the management page
	}
	
	/**
	 * Edit an RSS feed
	 */
	public function edit($id)
	{
		if(!$this->_check_exists($id)) // does the feed not exist
		{
			$this->_render_error('You cannot edit a feed that doesn\'t exist');
			die();
		}
		
		if(!$this->_check_owner($id)) // is the logged in user authorised to edit the feed
		{
			$this->_render_error('You cannot edit someone elses feed');
			die();
		}
		
		if (isset($_POST['submit'])) // has the form been submitted
		{
			$feed = ORM::factory('ext_feed', $id);
			$post = $_POST;
			
			if ($feed->validate($post)) // there are no validation errors
			{
				if (torrent_feed::_is_feed_supported($this->input->post('url')) || $this->input->post('confirmed')) // we are adding a support feed or if the feed has been confirmed
				{
					$feed->user_id = $this->user->id; // set the feeds owner to the current user
					$feed->save(); // save the feed
					
					$label = ORM::factory('label', $feed->label);
					$label->name = $feed->name;
					$label->save();
					
					url::redirect('/feed/external/manage/'); // redirect back to the feed management page
				}
				else // we are adding an unsupported feed and it needs confirmation
				{
					$template = new View('template_popup');
					$template->title = 'Confirm Adding Unsupported Feed';
					$template->message = '<strong>WARNING</strong> : The feed you are creating is not supported by RTorrentWeb at this time, the feed may not function as desired. Please go here <!-- TODO : Add url --> to contact us if you wish to have your site added to the supported list.';
					$template->content = new View('feed/external/feed_form');
					$template->content->url = $this->input->post('url');
					$template->content->name = $this->input->post('name');
					$template->content->submit_text = 'Confirm Feed Details';
					$template->content->hidden_fields = array('confirmed' => 'true');
					$template->render(true);
				}
			}
			else // there are some validation errors
			{
				$template = new View('template_popup');
				$template->title = 'Edit Feed';
				$template->message = 'Some errors where encountered while handling your feed:
				<ul>
					<li>' . implode('</li>
					<li>', $post->errors()) . '</li>
				</ul>';
				$template->content = new View('feed/external/feed_form');
				$template->content->name = $this->input->post('name');
				$template->content->url = $this->input->post('url');
				$template->content->submit_text = 'Save Feed';
				$template->render(true);				
			}
		}
		else // has the form not been submitted
		{
			$feed = ORM::factory('ext_feed', $id);
			
			$template = new View('template_popup');
			$template->title = 'Edit Feed';
			$template->content = new View('feed/external/feed_form');
			$template->content->name = $feed->name;
			$template->content->url = $feed->url;
			$template->content->submit_text = 'Save Feed';
			$template->render(true);
		}
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
		
		$template = new View('template_popup');
		$template->title = 'RSS Entries For : ' . $feed->name;
		$template->content = new View('feed/external/view');
		$template->content->feed_id = $id;
		$template->content->label_id = $feed->label_id;
		
		if (count($feed_items) < 1) // are there items in the feed
		{
			$template->message = 'There are currently no valid items in this RSS feed. There may be a problem with your feed provider. Please check the url ' . $feed->url . ' in your browser.';
		}
		else
		{
			$template->content->feed_items = $feed_items;
			$template->content->last_seen_guid = $feed->last_seen_guid;

			$this->_reset_seen_guid($feed, $feed_items[0]['guid']);
		}
		
		$template->render(true);
	}
	
	/**
	 * Manage feeds
	 */
	public function manage()
	{
		$template = new View('template_popup');
		$template->title = 'Manage RSS Feeds';
		$template->content = new View('feed/external/manage');
		$template->content->feeds = ORM::factory('ext_feed')->where('user_id', $this->user->id)->find_all();
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