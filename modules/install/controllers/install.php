<?php
/*
 * rTorrentWeb version 0.1 prerelease
 * $Id$
 * Copyright (C) 2009, Daniel Lo Nigro (daniel15) <daniel at d15.biz>
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

class Install_Controller extends Controller
{
	public function __construct()
	{
		// No access via HTTP
		if (isset($_SERVER['HTTP_HOST']))
			die('Access denied.');
			
		$this->config = Config::instance();
		/* TODO: Access permissions? This module should be completely disabled
		 * after installation, so I haven't worried too much just yet.
		 */
	}
	
	/**
	 * Save settings to the database
	 */
	public function save_settings()
	{

		// Here, we're reading data from stdin
		$input = unserialize(fread(STDIN, 8192));
		// Now, insert it
		$this->config->set('metadata_dir', $input['datadir'] . 'torrent_data');
		$this->config->set('torrent_dir', $input['datadir'] . 'torrents');
	}
	
	/**
	 * Add default admin user
	 */
	public function create_admin()
	{
		$password = self::_generate_pass();
		$user = ORM::factory('user');
		$user->username = 'admin';
		$user->password = $password;
		$user->homedir = $this->config->get('torrent_dir') . '/admin/';
		$user->add(ORM::factory('role', 'login'));
		$user->add(ORM::factory('role', 'admin'));
		$user->save();
		
		echo serialize(array(
			'username' => 'admin',
			'password' => $password
		));
	}
	
	/**
	 * Generate a password
	 */
	private static function _generate_pass()
	{
		$chars = array_merge(range('A', 'Z'), range('a', 'z'), range('0', '9'));
		$length = mt_rand(8, 20);
		$password = '';
		
		for ($i = 0; $i < $length; $i++)
			$password .= $chars[array_rand($chars)];
		
		return $password;
	}
}
?>