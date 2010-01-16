<?php
/*
 * rTorrentWeb version 1.1 Beta
 * $Id$
 * Copyright (C) 2009-2010, Daniel Lo Nigro (Daniel15) <daniel at d15.biz>
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

class url extends url_Core
{
	/**
	 * Returns the base URL, with or without the index page.
	 * @param   boolean  include the index page
	 * @param   mixed    if boolean true, detect the protocol. if string, use this protocol
	 * @return  string
	 */
	public static function base($index = FALSE, $protocol = FALSE)
	{
		// Detect the protocol?
		if ($protocol === true)
		{
			// Is server HTTPS variable set?
			if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on')
				$protocol = 'https';
			// Are they on port 443 (assume 443 = HTTPS)
			elseif (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == '443')
				$protocol = 'https';
			// Otherwise, they're using normal HTTP.
			else
				$protocol = 'http';
		}

		return parent::base($index, $protocol);
	}
}
?>