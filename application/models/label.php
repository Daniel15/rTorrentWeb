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
defined('SYSPATH') or die('No direct script access.');

class Label_model extends ORM
{
	protected $belongs_to = array('user');
	protected $has_and_belongs_to_many = array('torrents');
	
	/**
	 * Validates and optionally saves a new label record from an array.
	 *
	 * @param  array    values to check
	 * @param  boolean  save the record when validation succeeds
	 * @return boolean
	 */
	public function validate(array &$array, $save = false)
	{
		// TODO: Validate icon?
		$array = Validation::factory($array)
			->pre_filter('trim', 'title')
			->add_rules('icon', 'required', 'alpha_dash')
			->add_rules('name', 'required', 'standard_text');

		return parent::validate($array, $save);
	}
}
?>