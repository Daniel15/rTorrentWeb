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
		parent::__construct();
		// Check if they're allowed here
		// TODO: Handle this better
		if (!$this->auth->logged_in('admin'))
			die('Error: No permission to access this area! You\'re not an admin.');
	}
	
	/**
	 * General settings
	 */
	public function index()
	{
		// Did they submit the page?
		if ($this->input->post('submit'))
		{
			// Let's go through all the possible settings
			foreach (array('rpcurl', 'metadata_dir', 'torrent_dir') as $key)
			{
				// If it's not set, skip it
				if (null === ($value = $this->input->post($key)))
					continue;
				// Save it
				// TODO: Validation
				$this->config->set($key, $value);
			}
			$this->template->top_message = 'Your changes were saved';
		}
		
		$this->template->title = 'General Settings';
		$this->template->content = View::factory('admin/index');
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