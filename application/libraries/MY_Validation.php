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

class Validation extends Validation_Core
{
	/**
	 * Return the string for an error message. Also check for a wildcard message
	 */
	public function errors($file = NULL)
	{
		if ($file === NULL)
		{
			return $this->errors;
		}
		else
		{

			$errors = array();
			foreach ($this->errors as $input => $error)
			{
				// The keys we look for
				$keys = array("$file.$input.$error", "$file.$input.default", "$file.default.$error");
				// Grab the first one we can find
				foreach ($keys as $key)
				{
					if (($errors[$input] = Kohana::lang($key)) !== $key)
						break;
				}
			}

			return $errors;
		}
	}
}
?>