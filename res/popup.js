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
 * Adding a new torrent
 */
var Add = 
{
	'file_count': 0,
	'init': function()
	{
		// Hide URL download section by default...
		$('url').setStyle('display', 'none');
		// ...but let them choose what to do!
		$('type_file').addEvent('click', function()
		{
			$('file').setStyle('display', 'block');
			$('url').setStyle('display', 'none');
		});
		$('type_url').addEvent('click', function()
		{
			$('file').setStyle('display', 'none');
			$('url').setStyle('display', 'block');
		});
		
		// Make the "add another?" buttons actually work
		$('another').addEvent('click', function()
		{
			// What type is chosen?
			if ($('type_file').checked)
			{
				new Element('input',
				{
					'type': 'file',
					'name': 'torrent_file_' + ++Add.file_count,
					'id': 'torrent_file_' + Add.file_count,
					'size': 40
				}).inject($('file'));
			}
			else
			{
				new Element('input',
				{
					'type': 'text',
					'name': 'torrent_url[]',
					'id': 'torrent_url_' + Add.file_count,
					'size': 50
				}).inject($('url'));
			}
			
			// They can't choose the files individually any more.
			$('choose_files_span').setStyle('display', 'none');
		});
	}
}
 
/**
 * Scripts for when we're adding a new torrent, selecting the files to download
 */
var AddFiles = 
{
	'init': function()
	{
		// Initialise the directory selections
		$$('li.dir').each(function(el)
		{
			// Make the checkbox tick all items inside this directory
			el.getElement('input').addEvent('click', AddFiles.toggle_dir_checked);
			// Add the "-" to minimise the directory
			/*new Element('img', 
			{
				'src': 'res/minus.png',
				'alt': 'Toggle display of this directory',
				'class': 'toggle',
				'events': 
				{
					'click': AddFiles.toggle_dir.bind(el)
				}
			}).inject(el, 'top');*/
			
			new Element('a', 
			{
				'html': '+',
				'href': '#',
				'class': 'toggle',
				'events': 
				{
					'click': AddFiles.toggle_dir.bind(el)
				}
			}).inject(el, 'top');
			
			
			// Collapse directory by default
			AddFiles.toggle_dir.bind(el)();
			el.store('collapsed', true);
		});
		
		// Add the button event handlers
		$('all').addEvent('click', AddFiles.check_all);
		$('invert').addEvent('click', AddFiles.invert);
	},
	
	/**
	 * Toggle a directory being checked
	 */
	'toggle_dir_checked': function()
	{
		var check = this.checked;
		this.getParent().getParent().getElements('input').each(function(el)
		{
			el.checked = check;
		});
	},
	
	/**
	 * Toggle a directory being shown
	 */
	'toggle_dir': function()
	{
		//var toggler = this.getElement('img.toggle');
		var toggler = this.getElement('a.toggle');
		// Whether we have to hide, or show.
		//var hide = (toggler.get('src') == 'res/minus.png');
		var hide = !this.retrieve('collapsed');
		this.getElement('ul').setStyle('display', hide ? 'none' : 'block');
		//toggler.set('src', hide ? 'res/plus.png' : 'res/minus.png');
		toggler.set('html', hide ? '+' : '-');
		this.store('collapsed', hide);
		
		// Cancel the click
		return false;
	},
	
	/**
	 * Tick all
	 */
	'check_all': function()
	{
		$$('input[type=checkbox]').each(function(el)
		{
			el.checked = true;
		});
	},
	
	/**
	 * Invert selection
	 */
	'invert': function()
	{
		$$('input[type=checkbox]').each(function(el)
		{
			el.checked = !el.checked;
		});
	}
};