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

class User_Controller extends Base_Controller
{
	public function index()
	{
	}
	
	/**
	 * Log in
	 */
	public function login()
	{
		// Are they already logged in? Go to the index page
		if ($this->auth->logged_in())
			url::redirect('');
			
		// Let's load the login form
		$login = new View('user/login');
		$login->error = '';
		
		// Did the user post the form?
		if (isset($_POST['submit']))
		{
			// Make sure the user exists
			$user = ORM::factory('user', $this->input->post('username'));
			if ($user->loaded && $this->auth->login($user, $this->input->post('password')))
			{
				url::redirect('');
			}
			else
			{
				$login->error = 'Invalid username or password';
			}
		}
		
		// Actually show the login form
		$login->render(true);
	}
	
	/**
	 * Log out and redirect to the home page
	 */
	public function logout()
	{
		$this->auth->logout();
		url::redirect('');
	}
	
	/*public function test()
	{
		$user = ORM::factory('user');
		$user->username = 'admin';
		$user->password = 'admin';
		$user->add(ORM::factory('role', 'login'));
		$user->add(ORM::factory('role', 'admin'));
		$user->save();
	}*/
}
?>