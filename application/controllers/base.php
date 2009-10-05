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

abstract class Base_Controller extends Controller
{
	// Template view name - Default to none
	//public $template = 'template';
	public $template = null;
	// Default to do auto-rendering
	public $auto_render = true;
	
	// OM NOM NOM NOM
	public function __construct()
	{
		parent::__construct();
		// Let's load the session and auth libraries
		$this->session = Session::instance();
		$this->auth = new Auth();
		
		//  Make sure we're logged in!
		if (!$this->auth->logged_in() && url::current() != 'user/login')
		{
			// Not logged in, and it's an AJAX request? Die with an error
			if (request::is_ajax())
				die(json_encode(array(
					'error' => true,
					'message' => 'You are not logged in. Please log in.'
				)));
			// Otherwise, we just redirect to the login page.
			else
				url::redirect('user/login');
		}
		
		// This might be helpful ^_^
		$this->user = $this->auth->get_user();
		
		// Load the template, if we're using one
		if ($this->template != null)
		{
			$this->template = new View($this->template);
			// Render the template immediately after the controller method
			Event::add('system.post_controller', array($this, '_render'));
		}
	}

	/**
	 * Render the loaded template.
	 */
	public function _render()
	{
		if ($this->auto_render == TRUE)
		{
			// Render the template when the class is destroyed
			$this->template->render(TRUE);
		}
	}
}
?>