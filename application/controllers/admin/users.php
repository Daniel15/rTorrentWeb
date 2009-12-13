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

		// Get a list of all the current users
		$userpage->users = ORM::factory('user')->orderby('username')->find_all();

		// Here comes the form
		$userpage->form = new View('includes/admin/user_form');
		$userpage->form->add_errors = $this->session->get('add_errors', array());
		// Get a list of all the roles		
		$userpage->form->roles = ORM::factory('role')->find_all();
		// Default data
		$userpage->form->data = array(
			'username' => '',
			'email' => '',
			// By default, the user will only have "login" permission.
			'roles' => array(ORM::factory('role', 'login')->id),
		);
		
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
			if (!@mkdir($user->homedir, 0775, true))
			{
				$message .= 'The directory ' . $user->homedir . ' could not be created automatically. Please create it manually, otherwise this user will not be able to start any downloads!';
			}
			
			$this->session->set_flash('top_message', $message);
			url::redirect('admin/users');
		}
		
		// Otherwise, if we're here, we have an error :(
		$page = new View('admin/users/add');
		$this->template->title = 'Add User';
		
		// Here comes the form
		$page->form = new View('includes/admin/user_form');
		$page->form->add_errors = $post->errors('user_errors');
		// Get a list of all the roles		
		$page->form->roles = ORM::factory('role')->find_all();
		$page->form->data = $post->as_array();
		
		$this->template->content = $page;
	}
	
	/**
	 * Change a user's password
	 * @param int User ID of the user to edit	 
	 */
	public function change_password($id)
	{
		// Load the user 
		$user = ORM::factory('user', $id);
			
		// What? Doesn't exist? O_o
		if (!$user->loaded)
			url::redirect('admin/users');
			
		$this->template->title = 'Changing ' . $user->username . '\'s password';
		$page = new View('admin/users/change_password');
		
		// Did they post?
		if (isset($_POST['submit']))
		{
			$post = Validation::factory($_POST)
				->add_rules('password', 'required', 'length[5,42]')
				->add_rules('password_confirm', 'matches[password]');
				
			// Validate!
			if ($post->validate())
			{
				$user->password = $post['password'];
				$user->save();
				$this->session->set_flash('top_message', 'Changed password for ' . $user->username);
				url::redirect('admin/users');
			}
			// Otherwise, something is wrong :(
			else
			{
				$page->errors = $post->errors('user_errors');
			}
		}
		
		$this->template->content = $page;
		
	}
	
	/**
	 * Deleting a user
	 * @param int User ID of the user to delete
	 */
	public function delete($id)
	{
		// Easy as pie
		ORM::factory('user', $id)->delete();
		ORM::factory('label')->where('user_id', $id)->delete();
		// TODO: Delete their directory?
		// TODO: Delete their torrents?
		$this->session->set_flash('top_message', 'Deleted user ' . $id);
		url::redirect('admin/users');
		
	}
}
?>