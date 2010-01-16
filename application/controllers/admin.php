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
 *
 * This file contains basic administration actions. More advanced stuff is in the
 * "admin" directory.
 */
defined('SYSPATH') OR die('No direct access allowed.');

/*abstract */class Admin_Controller extends Base_Controller
{
	public $template = 'template';
	public function __construct()
	{
		// AJAX requests don't have a template
		if (request::is_ajax())
			$this->template = null;
			
		parent::__construct();
		// Check if they're allowed here
		// TODO: Handle this better
		if (!$this->auth->logged_in('admin'))
		{
			if (request::is_ajax())
				die(json_encode(array(
					'error' => true,
					'message' => 'No permission to access this area! You\'re not an admin.'
				)));
			else
				die('Error: No permission to access this area! You\'re not an admin.');
			
		}
		
	}
	
	/**
	 * General settings
	 */
	public function index()
	{
		$rtorrent = new Rtorrent;
		
		$this->template->title = 'General Settings';
		$page = View::factory('admin/index');	
				
		
		// Did they submit the page?
		if ($this->input->post('submit'))
		{
			// Validate the form
			$post = Validation::factory($_POST)
				->pre_filter('trim')
				->add_rules('rpcurl', 'required', 'standard_text')
				->add_rules('metadata_dir', 'required', 'standard_text')
				->add_rules('torrent_dir', 'required', 'standard_text')
				->add_rules('down_rate', 'required', 'digit')
				->add_rules('up_rate', 'required', 'digit');
			
			// Is it valid
			if ($post->validate())
			{
				// Let's go through all the possible settings
				foreach (array('rpcurl', 'metadata_dir', 'torrent_dir') as $key)
				{
					// If it's not set, skip it
					if (null === ($value = $this->input->post($key)))
						continue;
					// Save it
					// TODO: Better Validation
					$this->config->set($key, $value);
				}
				
				$rtorrent->do_call('set_upload_rate', $this->input->post('up_rate') * 1024);
				$rtorrent->do_call('set_download_rate', $this->input->post('down_rate') * 1024);
				
				$this->template->top_message = 'Your changes were saved';
			}
			else
			{
				$page->errors = $post->errors();
			}
			
			$page->settings = $post;
		}
		else
		{
			$page->settings = array(
				'rpcurl' => $this->config->get('rpcurl'),
				'metadata_dir' => $this->config->get('metadata_dir'),
				'torrent_dir' => $this->config->get('torrent_dir'),
				'up_rate' => $rtorrent->do_call('get_throttle_up_max', '') / 1024,
				'down_rate' => $rtorrent->do_call('get_throttle_down_max', '') / 1024
			);
		}
		
		$this->template->content = $page;
	}
	
	/** 
	 * Show the rTorrentWeb "About" page 
	 */ 
	public function about() 
	{ 
		$this->template->title = 'About rTorrentWeb'; 
		$this->template->content = View::factory('admin/about'); 
	} 
}
?>