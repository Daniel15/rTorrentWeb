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
	 * TODO: General settings?
	 */
	public function index()
	{
		url::redirect('profile/labels');
	}
	
	/**
	 * Load the user's settings
	 */
	public function get_settings()
	{
		$this->template = View::factory('profile/settings_js');

		// Get the labels
		$this->template->labels = array();
		$label_rows = $this->user->orderby('name')->labels;
		foreach ($label_rows as $label)
			$this->template->labels[$label->id] = $label->as_array();
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