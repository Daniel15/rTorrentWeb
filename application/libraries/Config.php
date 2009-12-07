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

/**
 * Configuration for rTorrentWeb. Configuration items are stored in a database.
 */
class Config_Core
{
	// Singleton
	protected static $instance;
	// All the setting data
	protected $settings;
	
	/**
	 * Get a reference to the config singleton
	 */
	public static function instance()
	{
		if (self::$instance === null)
			self::$instance = new Config();
			
		return self::$instance;
	}
	
	/**
	 * Create an instance of the config class
	 */
	protected function __construct()
	{
		Benchmark::start('config_load');
		
		// Here are the default settings.
		$defaults = array(
			'rpcurl' => 'http://' . $_SERVER['HTTP_HOST'] . '/RPC2/',
			'metadata_dir' => APPPATH . '../torrent_metadata',
			'torrent_dir' => APPPATH . '../torrents',
		);
	
		// Load all the settings.
		$rows = ORM::factory('setting')->find_all();
		foreach ($rows as $row)
		{
			$this->settings[$row->variable] = $row->value;
			unset($defaults[$row->variable]);
		}
		// Merge the remaining default settings into the array.
		$this->settings = array_merge($this->settings, $defaults);
		
		Benchmark::stop('config_load');
	}
	
	/**
	 * Get a setting
	 */
	public function get($variable)
	{
		return array_key_exists($variable, $this->settings) ? $this->settings[$variable] : null;
	}
	
	/**
	 * Set a setting
	 */
	public function set($variable, $value)
	{
		$this->settings[$variable] = $value;
		// Let's  get it from the database
		$row = ORM::factory('setting', $variable);
		// Doesn't exist yet? Create a new one
		if (!$row->loaded)
		{
			$row = ORM::factory('setting');
			$row->variable = $variable;
		}
		// Persist it.
		$row->value = $value;
		$row->save();
	}
}
?>