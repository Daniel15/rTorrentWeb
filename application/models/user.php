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

class User_Model extends Auth_User_Model
{
	protected $has_many = array('user_tokens', 'torrents', 'ext_feeds', 'labels');
	
	public function __get($key)
	{
		if ($key === 'settings')
		{
			$defaults = array(
					'autorefresh' => false,
					'autorefresh_interval' => 10,
					'only_mine' => false,
					'sidebar_width' => '',
					'customstatus_line' => 'Server statistics: {dsu} used, {dsf} available.',
				);
				
			// Let's try get the settings
			try
			{
				$settings = unserialize(parent::__get('settings'));
			}
			catch (Exception $ex)
			{
				$settings = null;
			}
			
			// No settings? We ONLY need defaults
			if ($settings == null)
				return $defaults;
				
			// Otherwise, we have defaults for only the settings we don't have.
			return arr::merge($defaults, $settings);
		}
		return parent::__get($key);
	}
	
	public function __set($key, $value)
	{
		if ($key === 'settings')
			$value = serialize($value);

		parent::__set($key, $value);
	}
}
?>