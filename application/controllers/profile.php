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

class Profile_Controller extends Base_Controller
{
	public $template = 'template';
	
	/**
	 * General settings
	 */
	public function index()
	{
		$this->template->title = 'User Profile';
		$page = View::factory('profile/settings');
		
		// Did they submit?
		if ($this->input->post('submit'))
		{
			// Validate their settings
			$post = Validation::factory($_POST)
				->pre_filter('trim')
				->add_rules('autorefresh_interval', 'required', 'numeric');
				
			// Are they all valid?
			if ($post->validate())
			{
				// Fix some stuff
				unset($post['submit']);
				$post['autorefresh'] = !empty($post['autorefresh']);
				// Save the settings
				$this->user->settings = $post->as_array();
				$this->user->save();
				$this->template->top_message = 'Your settings were saved.';
			}
			// Not valid? :(
			else
			{
				$page->errors = $post->errors();
			}
			$page->settings = $post;
		}
		// Not submitted
		else
		{
			$page->settings = $this->user->settings;
			$page->errors = $this->session->get('errors', array());
		}
		
		$this->template->content = $page;
	}
	
	/**
	 * Change the password
	 */
	public function password()
	{
		$post = $_POST;
		if ($this->user->change_password($post, true))
		{
			$this->session->set_flash('top_message', 'Your password was changed');
			url::redirect('profile');
		}
		else
		{
			$this->session->set_flash('errors', $post->errors('user_errors'));
			url::redirect('profile');
		}
	}
	
	
	/**
	 * Load the user's settings
	 */
	public function get_settings()
	{
		$this->template = View::factory('profile/settings_js');
		$this->template->settings = $this->user->settings;

		// Get the labels
		$this->template->labels = array();
		$label_rows = $this->user->orderby('name')->labels;
		foreach ($label_rows as $label)
			$this->template->labels[$label->id] = $label->as_array();
	}
	
	/**
	 * Save a setting
	 */
	public function save_setting()
	{
		if (!request::is_ajax())
			die();
		
		// Settings we can set via JavaScript
		if (!in_array($this->input->post('variable'), array('only_mine', 'sidebar_width')))
			die('Invalid setting');
			
		// Set it!
		$settings = $this->user->settings;
		$settings[$this->input->post('variable')] = $this->input->post('value');
		$this->user->settings = $settings;
		$this->user->save();
		die();
	}
	
	/**
	 * Edit the available torrent labels
	 */
	public function labels()
	{
		$this->template->title = 'Labels';
		$page = View::factory('profile/labels');
		//$page->labels = ORM::factory('label')->where('id_;
		$page->labels = $this->user->where('internal', 0)->orderby('name')->labels;
		// Get a list of all the icons we have
		$page->icons = array();
		// TODO: Using kohana_pathinfo might be bad.
		$dir = opendir($GLOBALS['kohana_pathinfo']['dirname'] . '/res/label_icons/');
		while (($file = readdir($dir)) !== false)
		{
			// Skip hidden files and . and .. and blank
			if ($file[0] == '.' || $file == 'blank.png')
				continue;
				
			$page->icons[] = substr($file, 0, strrpos($file, '.'));
		}
		$page->add_errors = $this->session->get('add_errors', array());
		
		$this->template->content = $page;
	}
	
	/**
	 * Add a label
	 */
	public function add_label()
	{
		$label = ORM::factory('label');
		$post = $_POST;
		
		if ($label->validate($post))
		{
			$label->user_id = $this->user->id;
			$label->save();
			$this->session->set_flash('top_message', 'Added label "' . $post['name'] . '".');
		}
		else
		{
			$this->session->set_flash('add_errors', $post->errors());
		}
		
		url::redirect('profile/labels');
	}
	
	/**
	 * Delete a label
	 */
	public function delete_label($id)
	{
		// Better check we own it
		// TODO: Should the errors be formatted better? They'll rarely be encountered...
		$label = ORM::factory('label', $id);
		if ($label->user_id != $this->user->id)
			die('Error: That\'s not your label!');
			
		// Let's go!
		$name = $label->name;
		$label->delete();
		$this->session->set_flash('top_message', 'Deleted label "' . $name. '".');
		url::redirect('profile/labels');
	}
}

?>