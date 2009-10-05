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
		parent::__construct();
		// Check if they're allowed here
		// TODO: Handle this better
		if (!$this->auth->logged_in('admin'))
			die('Error: No permission to access this area! You\'re not an admin.');
	}
	
	public function index()
	{
		$this->template->title = 'User Administration';
		
		$userpage = new View('admin/users/index');
		$userpage->add_errors = $this->session->get('add_errors', array());
		$this->template->content = $userpage;
		// Get a list of all the current users
		$userpage->users = ORM::factory('user')->orderby('username')->find_all();
	}
	
	/**
	 * Adding a user
	 * TODO: FINISH THIS LOL
	 */
	public function add()
	{
		// No POST data? Go to the index page
		if (empty($_POST['submit']))
			url::redirect('admin/users');
		//$this->session->set_flash('errors', array('1','2','3'));
		// Let's see if our data is valid
		$user = ORM::factory('user');
		$post = $_POST;

		if ($user->validate($post))
		{
			// TODO: Should be configurable
			$user->homedir = Kohana::config('config.torrent_dir') . '/' . $user->username;
			$user->add(ORM::factory('role', 'login'));
			$user->save();
		}
		else
		{
			$this->session->set_flash('add_errors', $post->errors('user_errors'));
		}
		
		url::redirect('admin/users');
	}
}
?>