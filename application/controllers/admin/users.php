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

class Users_Controller extends Admin_Controller
{
	public function __construct()
	{
		// This handles permissions!
		parent::__construct();
	}
	
	public function index()
	{
		$userpage = new View('admin/users/index');
		$this->template->title = 'User Administration';

		$userpage->add_errors = $this->session->get('add_errors', array());
		// Get a list of all the current users
		$userpage->users = ORM::factory('user')->orderby('username')->find_all();
		// Get a list of all the roles
		$userpage->roles = ORM::factory('role')->find_all();
		$this->template->content = $userpage;
	}
	
	/**
	 * Get a list of users
	 */
	public function get_list()
	{
		$this->auto_render = false;
		// Get a list of all the current users
		$user_rows = ORM::factory('user')->orderby('username')->find_all();
		$users = array();
		
		foreach ($user_rows as $user)
			$users[$user->id] = $user->username;
		
		echo json_encode(array(
			'error' => false,
			'users' => $users
		));
	}
	
	/**
	 * Adding a user
	 */
	public function add()
	{
		// No POST data? Go to the index page
		if (empty($_POST['submit']))
			url::redirect('admin/users');
		// Let's see if our data is valid
		$user = ORM::factory('user');
		$post = $_POST;

		if ($user->validate($post))
		{
			// TODO: Should be configurable
			$user->homedir = $this->config->get('torrent_dir') . '/' . $user->username;
			// TODO: More validation
			$user->roles = $_POST['roles'];
			$user->save();
			$message = 'Added user ' . $user->id . '.';
			
			// Create a directory for this user. If there's an error, tell the admin.
			// TODO: Security?
			if (!@mkdir($user->homedir, null, true))
			{
				$message .= 'The directory ' . $user->homedir . ' could not be created automatically. Please create it manually, otherwise this user will not be able to start any downloads!';
			}
			
			$this->session->set_flash('top_message', $message);
		}
		else
		{
			$this->session->set_flash('add_errors', $post->errors('user_errors'));
		}
		
		url::redirect('admin/users');
	}
	
	/**
	 * Deleting a user
	 */
	public function delete($id)
	{
		// Easy as pie
		ORM::factory('user', $id)->delete();
		// TODO: Delete their directory?
		// TODO: Delete their torrents?
		$this->session->set_flash('top_message', 'Deleted user ' . $id);
		url::redirect('admin/users');
		
	}
}
?>