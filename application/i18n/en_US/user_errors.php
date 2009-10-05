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

$lang = array
(
	'username' => array
	(
		'required' => 'The username cannot be blank.',
		'length' => 'The username must be between 4 and 32 characters long.',
		'alpha'    => 'Only alphabetic characters are allowed.',
		'default'  => 'Invalid username.',
	),
	
	'email' => array
	(
		'required' => 'The email address cannot be blank.',
		'default' => 'Invalid email address.',
	),
	
	'password' => array
	(
		'required' => 'The password cannot be blank.',
		'length' => 'The password was too short.',
		'default' => 'Invalid password.',
	),
	
	'password_confirm' => array
	(
		'default' => 'The password confirmation did not match the password',
	),
);

?>