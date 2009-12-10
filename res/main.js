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
 
/**
 * User administration page
 */
var Users = 
{
	'init': function()
	{
		// Attach all the delete handlers
		$$('a.delete').addEvent('click', function()
		{
			return confirm('Are you SURE you want to delete this user?');
		});
	}
};

/**
 * Label administration page
 */
var Labels = 
{
	'init': function()
	{
		$('icon').addEvent('change', Labels.change_icon);
		// Firefox doesn't fire the change event when changing via the keyboard.
		$('icon').addEvent('keypress', Labels.change_icon);
	},
	
	'change_icon': function()
	{
		$('label_icon').set('src', 'res/label_icons/' + this.value + '.png');
	},
};

/**
 * Feed administration page
 */
var Feeds = 
{
	'init': function()
	{
		// Make these pop up
		$$('li.feed > a').addEvent('click', function()
		{
			window.open(this.href, 'ManageRSSWindow', 'location=no,menubar=no,status=no,titlebar=no,toolbar=no,height=800,width=700');
			// Cancel the click.
			return false;
		});
	}
};