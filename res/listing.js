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
 * Our additional number functionality
 */
Number.implement({
	/**
	 * Format a file size
	 */
	'format_size': function()
	{
		if (this > 1073741824)
			return (this / 1073741824).toFixed(2) + ' GB';
		if (this > 1048576)
			return (this / 1048576).toFixed(2) + ' MB';
		if (this > 1024)
			return (this / 1024).toFixed(2) + ' KB';
		return this + ' B';
	}
});

/**
 * TODO: MOve this elsewhere
 */
var Settings = 
{
	// Is auto-refresh enabled?
	'autorefresh': false,
	// How many seconds before auto refreshing?
	'autorefresh_interval': 10
};

/**
 * TODO: Move this elsewhere
 */
var Lang = 
{
};

/**
 * Sortable table stuff
 */
var SortTable = 
{
	/**
	 * Initialise (make a table sortable). Pass this the ID of the table, or
	 * the element itself
	 */
	'init': function(el)
	{
		el = $(el);
		// Attach the sort function to the table
		el.sort = SortTable.sort.bind(el);
		// Make sure we remove this function on unload, to prevent memory leaks
		window.addEvent('unload', function() { this.sort = null; }.bind(el));
		// Store our custom stuff!
		// What is it currently sorted by?
		el.store('sorted_by', null);
		// Are we sorting in descending order?
		el.store('sort_desc', false);
		
		// Attach all our sort links
		var i = 0;
		$A(el.tHead.rows[0].cells).each(function(header)
		{
			// Attach the click listener 
			header.addEvent('click', el.sort.pass(i++));
			// Add a little arrow that says if we're sorting by this column
			new Element('span', {'class': 'sorted'}).inject(header);
		});
	},
	
	/**
	 * Do the actual sort. If passed a column number, it was clicked, and sort
	 * by that column. If not, sort by whatever we have set.
	 */
	'sort': function(columnNum)
	{
		var head_cells = this.tHead.rows[0].cells;
		var tbody = this.tBodies[0];
		
		var sorted_by = this.retrieve('sorted_by', 0);
		var sort_desc = this.retrieve('sort_desc');
	
		//  Need to remove the little sort arrow from the current sort column
		head_cells[sorted_by].getElement('span.sorted').set('html', '&nbsp;');
		
		// Passed column is the same as the current one? Reverse our current sort
		if (columnNum == sorted_by)
			sort_desc = !sort_desc;
		// Otherwise, if the column number is passed, we have to set the new one
		else if ($defined(columnNum))
		{
			sorted_by = columnNum;
			sort_desc = false;
		}
		// Otherwise, if there was no column number passed, we just redo the 
		// current sort
		
		// Save our information
		this.store('sorted_by', sorted_by);
		this.store('sort_desc', sort_desc);
		
		// Create a new array to store the sorting data
		// Format is an array of [text, row] elements, and then we sort on the
		// text.
		var temp = [];
		$A(tbody.rows).each(function(row)
		{
			temp[temp.length] = [row.cells[sorted_by].get('html'), row];
		});
		
		// No data? We're done here
		if (tbody.rows.length == 0)
		{
			return;
		}
		
		// Try to work out the type (and hence how we have to sort it) based on the first value
		var sorter = SortTable.sorters.case_insensitive;
		if (SortTable.sorters.filesize_regex.test(temp[0][0]))
			sorter = SortTable.sorters.filesize;
		else if (!isNaN(parseFloat(temp[0][0])))
			sorter = SortTable.sorters.numeric;
		
		temp.sort(sorter);
		// Are we sorting in reverse order? If so, reverse it
		if (sort_desc == true)
			temp = temp.reverse();
		
		temp.each(function(row)
		{
			row[1].inject(tbody, 'bottom');
		});
		
		// Show a sorting arrow on our sort column
		head_cells[sorted_by].getElement('span.sorted').set('html', sort_desc ? '&uarr;' : '&darr;');
	},
	
	/**
	 * All the different functions to sort the table
	 */
	'sorters': 
	{
		/**
		 * A numeric sort
		 */
		'numeric': function(a, b)
		{
			a[0] = parseFloat(a[0]);
			b[0] = parseFloat(b[0]);
			return a[0] - b[0];
		},
		
		/**
		 * A file size sort
		 */
		'filesize_regex': /([0-9]+(?:\.[0-9]+)?) ([KMG]?B)/, 
		'filesize': function(a, b)
		{
			var a_matches = SortTable.sorters.filesize_regex.exec(a[0]);
			var b_matches = SortTable.sorters.filesize_regex.exec(b[0]);
			// Work out the actual sizes
			[a_matches, b_matches].each(function(match)
			{
				if (match[2] == 'GB')
					match[1] *= 1073741824;
				else if (match[2] == 'MB')
					match[1] *= 1048576;
				else if (match[2] == 'KB')
					match[1] *= 1024;
			});
			
			// Now, actually do the comparison!
			return a_matches[1] - b_matches[1];
		},
		
		/**
		 * Case-insensitive string sort (default)
		 */
		'case_insensitive': function(a, b)
		{
			a[0] = a[0].toLowerCase();
			b[0] = b[0].toLowerCase();
			if (a[0] < b[0]) return -1;
			if (a[0] > b[0]) return 1;
			return 0;
		}
	}
};

/**
 * Code for the torrent listing itself
 */ 
var List = 
{	
	'init': function()
	{
		// Initialise some stuff
		List.refresh_count = Settings.autorefresh_interval;
		
		// Set the sizes of the pane stuff correctly
		List.resize_window();
		// Also, let's do that every resize
		window.addEvent('resize', List.resize_window);
		// Make the "Refresh" link work, and handle refreshing stuff
		$('refresh').addEvent('click', List.refresh);
		// Links to disable and enable automatic refreshing
		$('disable_auto').addEvent('click', function()
		{
			Settings.autorefresh = false;
			$clear(List.refresh_timer);
			$('toolbar_message').set('html', '');
			$('enable_auto').setStyle('display', 'inline');
			this.setStyle('display', 'none');
			return false;
		});
		
		$('enable_auto').addEvent('click', function()
		{
			Settings.autorefresh = true;
			List.refresh_count = Settings.autorefresh_interval;
			List.refresh_timer = List.autorefresh.periodical(1000);
			$('toolbar_message').set('html', '');
			$('disable_auto').setStyle('display', 'inline');
			this.setStyle('display', 'none');
			return false;
		});
		// Are we not automatically refreshing by default?
		if (!Settings.autorefresh)
		{
			$('disable_auto').setStyle('display', 'none');
			$('enable_auto').setStyle('display', 'inline');
		}
		
		// Add the torrent toolbar buttons
		$('delete').addEvent('click', Torrent.del);
		$('start').addEvent('click', Torrent.start);
		$('pause').addEvent('click', Torrent.pause);
		$('stop').addEvent('click', Torrent.stop);
		
		// Make the tabs do stuff
		$$('div#tabs li').addEvent('click', List.show_tab);
		// Set the tab to general (the default)
		List.show_tab.bind($('tab_general'))();
		
		// TODO: delete
		//$$('div#torrents th').makeResizable({modifiers: {y: 'top'}});
		// Make the table sortable
		SortTable.init($('torrents').getElement('table'));
		// Add the resize handle for the sidebar
		var sidebar = $('sidebar');
		var sidebarResizer = new Element('div', {
			'id': 'sidebar_resize_handle',
			'styles': 
			{
				'float': 'right',
				'width': '10px',
				'height': '100%',
				'cursor': 'e-resize'
			}
		}).inject(sidebar, 'top');
		sidebar.makeResizable({
			'handle': sidebarResizer,
			'modifiers': {'x': 'width', 'y': null}
		});
		
		// Add the resize handle for the panes
		var topPane = $('top_pane');
		var paneResizer = new Element('div', {
			'id': 'pane_resize_handle',
			'styles': 
			{
				'float': 'left',
				'width': '100%',
				'height': '10px',
				'cursor': 'n-resize',
			}
		}).inject(topPane, 'bottom');
		
		topPane.makeResizable({
			'handle': paneResizer,
			'modifiers': {'y': 'height', 'x': null},
			'onDrag': function(el, event)
			{
				//console.log($('top_pane').getStyle('height'));
				//console.log($('bottom_pane').getStyle('height') + '=' + $('bottom_pane').getStyle('height').toInt());
				List.resize_window();
			}
		});
		
		$('bottom_pane').makeResizable({
			'handle': paneResizer,
			'modifiers': {'y': 'height', 'x': null},
			'invert': true
		});
		
		// Do a refresh
		List.refresh();
	},
	
	/**
	 * The refresh AJAX request
	 */
	'refresh_request': new Request(
	{
		method: 'post',
		url: base_url + 'torrents/refresh',
		// Defined as a separate function as "List" isn't defined yet here :P.
		onSuccess: function(data_text) { List.refresh_callback(data_text) }
	}),
	
	/**
	 * Refresh the listing
	 */
	'refresh': function()
	{
		$(document.body).setStyle('cursor', 'wait');
		$('loading').setStyle('display', 'inline');
		Log.write('Refreshing...');
		$('refresh_count').set('html', '');
		$('refresh').addClass('disabled');
		
		// Cancel the automatic refreshing (for now)
		$clear(List.refresh_timer);
		
		// Send the request
		List.refresh_request.send();
		
		// Cancel the event propagation (if the link was clicked)
		return false;
	},
	
	/**
	 * Handle the refresh data
	 */
	'refresh_callback': function(data_text)
	{
		// JSON decode the data
		var response = JSON.decode(data_text);
		// Was there an error?
		if (response.error)
		{
			Log.write('An error occurred while refreshing: ' + response.message);
			$('loading').setStyle('display', 'none');
			$('refresh').removeClass('disabled');
			$(document.body).setStyle('cursor', 'default');
			return;
		}
		
		// Counts for the sidevar
		var cnt_seed = 0;
		var cnt_down = 0;
		var cnt_fin = 0;
		var cnt_stop = 0;
		var cnt_paused = 0;
		
		// Get our table
		var table = $('torrents').getElement('table').getElement('tbody');				
		var data = $H(response.data);	
		data.each(function(torrent, hash)
		{
			var row;
			// Does this row already exist?
			if (row = $('tor_' + hash))
			{
				// Delete all its cells, so we can update the data
				row.empty();
			}
			// Doesn't exist? Create a new row for it.
			else
			{
				// Create a new row for this torrent
				row = new Element('tr', 
				{
					'id': 'tor_' + hash,
					'events':
					{
						'click': List.click
					}
				});
				row.inject(table);
			}
			
			// Make sure these are integers
			torrent.size = parseInt(torrent.size);
			torrent.done = parseInt(torrent.done);
			torrent.total.up = parseInt(torrent.total.up);
			
			// The stuff that's in the table
			new Element('td', {'html': torrent.name, 'class': 'name'}).inject(row);
			new Element('td', {'html': torrent.state}).inject(row);
			new Element('td', {'html': torrent.size.format_size()}).inject(row);
			//new Element('td', {'html': torrent.done.format_size()}).inject(row);
			new Element('td', {'html': (torrent.done / torrent.size * 100) + "%"}).inject(row);
			new Element('td', {'html': torrent.rate.down.format_size() + '/s'}).inject(row);
			new Element('td', {'html': torrent.rate.up.format_size() + '/s'}).inject(row);
			new Element('td', {'html': torrent.ratio}).inject(row);
			// All the other details
			row.store('hash', hash);
			row.store('data', torrent);
			
			// Add it to our counts for the sidebar
			// TODO: Remove hardcoded strings
			switch (torrent.state)
			{
				case 'seeding':
					cnt_seed++;
					break;
				case 'downloading':
					cnt_down++;
					break;
				case 'finished':
					cnt_fin++;
					break;
				case 'stopped':
					cnt_stop++;
					break;
				case 'paused':
					cnt_paused++;
					break;
			}
		});
		
		// Set the data for the sidebar
		$('sidebar_all').getElement('span').set('html', data.getLength());
		$('sidebar_seed').getElement('span').set('html', cnt_seed);
		$('sidebar_down').getElement('span').set('html', cnt_down);
		$('sidebar_fin').getElement('span').set('html', cnt_fin);
		$('sidebar_stop').getElement('span').set('html', cnt_stop);
		$('sidebar_paused').getElement('span').set('html', cnt_paused);
		
		// Do we have a torrent currently selected? Better update its data
		if (List.selected != null)
			List.click.bind(List.selected)();
		
		// Sort the table
		$('torrents').getElement('table').sort();

		$('loading').setStyle('display', 'none');
		$('toolbar_message').set('html', '');
		$('refresh').removeClass('disabled');
		$(document.body).setStyle('cursor', 'default');
		
		// Start auto-refreshing again, if we should
		if (Settings.autorefresh)
		{
			List.refresh_count = Settings.autorefresh_interval;
			List.refresh_timer = List.autorefresh.periodical(1000);
		}
	},
	
	/**
	 * How long before we automatically refresh (in seconds). 0 = disabled
	 */
	'refresh_count': null,
	/**
	 * The timer for auto-refreshing, in case we have to cancel it
	 */
	'refresh_timer': null,
	/**
	 * Start the auto-refresh, and if it gets to 0, do it!
	 */
	'autorefresh': function()
	{
		$('refresh_count').set('html', 'Automatically refreshing in ' + List.refresh_count + ' seconds.');
		// Are we there yet? Are we there yet? 
		if (List.refresh_count == 0)
			List.refresh();
		// Aww, not there yet
		else
			List.refresh_count--;
	},
	
	/**
	 * The torrent that's currently selected
	 */
	'selected': null,
	/**
	 * Called when a torrent is clicked on, in the list
	 */
	'click': function()
	{
		// Unselect the currently selected one
		if (List.selected != null)
		{
			List.selected.removeClass('selected');
		}
		// Set the currently selected one
		List.selected = this;
		this.addClass('selected');
		
		// Set its data
		var data = this.retrieve('data');
		$('general').set('html', JSON.encode(data));
		// Now, enable the buttons that we need
		$('delete').removeClass('disabled');
		// Seeding or downloading? Stop and pause are needed.
		if (data.state == 'seeding' || data.state == 'downloading')
		{
			$('start').addClass('disabled');
			$('pause').removeClass('disabled');
			$('stop').removeClass('disabled');
		}
		// Paused? Start and stop are needed
		else if (data.state == 'paused')
		{
			$('start').removeClass('disabled');
			$('pause').addClass('disabled');
			$('stop').removeClass('disabled');
		}
		// Finished or stopped? Play is needed
		else if (data.state == 'stopped' || data.state == 'finished')
		{
			$('start').removeClass('disabled');
			$('pause').addClass('disabled');
			$('stop').addClass('disabled');
		}
		// Otherwise (what?! O_o), better enable them all!
		else
		{
			$('start').removeClass('disabled');
			$('pause').removeClass('disabled');
			$('stop').removeClass('disabled');
		}
		// Retrieve the file list for this torrent
		Torrent.files.bind(this)();
	},	
	/**
	 * Stuff that runs when the window is resized. Make sure the torrent listing
	 * is a good height - Seems to be hard to properly do in CSS.
	 * @todo: Figure out how to do this using pure CSS
	 */
	'resize_window': function()
	{
		var top_height = $('top_pane').getSize().y - $('toolbar').getSize().y - 10;
		$('torrents').setStyle('height', top_height);
		$('sidebar').setStyle('height', top_height);
		$('details').setStyle('height', $('bottom_pane').getSize().y - $('tabs').getSize().y - 10);
	},
	
	'current_tab': null,
	/**
	 * Show a tab (at the bottom of the page
	 * In this function, "this" refers to the tab that was clicked on
	 */
	'show_tab': function()
	{
		var tab = this.id.substring(4);
		
		// If we have a selected tab, deselect it. If we don't, hide all.
		if (List.current_tab)
		{
			$(List.current_tab).setStyle('display', 'none');
			$('tab_' + List.current_tab).removeClass('selected');
		}
		else
		{
			// Hide all the tabs
			$$('div#details div').setStyle('display', 'none');
			// Make them all deselected
			$$('div#tabs li').removeClass('selected');
		}
		
		// And show the one we want
		List.current_tab = tab;
		$(tab).setStyle('display', 'block');
		$('tab_' + tab).addClass('selected');
	}
};

// Better do the init stuff when we're loaded
window.addEvent('domready', List.init);

/**
 * Stuff for torrents
 * TODO: Should use OO for torrents?
 */
var Torrent = 
{
	/**
	 * An action for a torrent
	 */
	'action': function(torrent_row, action)
	{
		var data = torrent_row.retrieve('data');
		var hash = torrent_row.retrieve('hash');
		$(document.body).setStyle('cursor', 'progress');
		$('loading').setStyle('display', 'inline');
		torrent_row.addClass('loading');

		// TODO: Proper lang stuff LOL
		Log.write('Performing action to ' + data.name + '...');
		
		// Send the request
		var myRequest = new Request({
			method: 'post',
			//url: base_url + 'torrents/' + action + '/' + hash,
			url: base_url + 'torrents/action/' + action + '/' + hash,
			onSuccess: function(data_text)
			{
				var response = JSON.decode(data_text);
				$('tor_' + response.hash).removeClass('loading');
				// Was there an error?
				if (response.error)
				{
					Log.write('An error occurred: ' + response.message);
					$(document.body).setStyle('cursor', 'default');
					$('loading').setStyle('display', 'none');
					return;
				}
				// Did they only delete? If so, just remove the row
				if (response.action == 'delete')
				{
					$('tor_' + response.hash).dispose();
					// Better disable these, since there's no selected torrent now
					List.selected = null;
					$('start').addClass('disabled');
					$('stop').addClass('disabled');
					$('pause').addClass('disabled');
					$('delete').addClass('disabled');
					$(document.body).setStyle('cursor', 'default');
					$('loading').setStyle('display', 'none');
				}
				else
				{
					// TODO: This should just refesh the torrent we changed, not all of them!
					List.refresh();
				}
			}
		}).send();
	},
	
	/**
	 * Start a torrent
	 */
	'start': function()
	{
		//Torrent.action.bind(List.selected, 'start')(); 
		Torrent.action(List.selected, 'start');
	},
	
	/**
	 * Pause a torrent
	 */
	'pause': function()
	{
		//Torrent.action.bind(List.selected, 'pause')(); 
		Torrent.action(List.selected, 'pause');
	},
	
	/**
	 * Stop a torrent
	 */
	'stop': function()
	{
		//Torrent.action.bind(List.selected, 'stop')(); 
		Torrent.action(List.selected, 'stop');
	},
	
	'del': function()
	{
		if (!confirm('Are you SURE you want to delete ' + List.selected.retrieve('data').name + '?'))
			return;
		Torrent.action(List.selected, 'delete');
	},
	
	/**
	 * Get a list of files in the torrent
	 */
	'files': function()
	{
		//console.log(this.retrieve('hash'));
		var hash = this.retrieve('hash');
		var data = this.retrieve('data');
		
		// Do we already have a file listing for this torrent?
		var files = this.retrieve('files');
		if (files != null)
		{
			// Since this takes a long time, we run it asynchronously by delaying by 10ms
			Torrent.files_process.pass([files, 0]).delay(10);
			return;
		}
		
		// Otherwise, if we get to here, we need to load the listing.
		//$(document.body).setStyle('cursor', 'progress');
		$('loading').setStyle('display', 'inline');
		Log.write('Retrieving file listing for ' + data.name + '...');
		
		$('files').getElement('span').set('html', 'Loading file listing for ' +  data.name + '...');
		$('files').getElement('table').setStyle('display', 'none');
		
		// Send the request
		var myRequest = new Request({
			method: 'post',
			url: base_url + 'torrents/files/' + hash,
			onSuccess: function(data_text)
			{
				// JSON decode the data
				var response = JSON.decode(data_text);
				// Was there an error?
				if (response.error)
				{
					Log.write('An error occurred while refreshing: ' + response.message);
					$('loading').setStyle('display', 'none');
					$(document.body).setStyle('cursor', 'default');
					return;
				}
				
				// Store the file listing for later
				$('tor_' + response.hash).store('files', response);
				
				// Actually process the files now. 
				// Is this torrent still selected (they could have changed torrent by the time we get the reply)?
				if (List.selected.retrieve('hash') == response.hash)
				{
					//$('files').getElement('tbody').empty();
					// Here we go!
					Torrent.files_process(response, 0);
				}
				// Alllll done! :D
				$('files').getElement('span').set('html', '');
				$('files').getElement('table').setStyle('display', '');
				$('loading').setStyle('display', 'none');
				//$(document.body).setStyle('cursor', 'default');
				$('toolbar_message').set('html', '');
			}
		}).send();
	},
	
	/**
	 * Process a listing of files
	 * @param hash The directory data (a hash containing "files" and "dirs")
	 * @param int The level that we're at
	 */
	'files_process': function(data, level)
	{
		//console.log('Starting file processing at level ' + level);
		// Level 0? Delete all the current files
		if (level == 0)
			$('files').getElement('tbody').empty();
		// This is where we're putting all the files
		var tbody = $('files').getElement('tbody');
		
		// Go through all the files
		data.files.each(function(file)
		{
			//console.log('Process file ' + file.name + ' at level ' + level);
			// Add this file
			var row = new Element('tr', {'class': 'file level-' + level}).inject(tbody);
			// TODO: The image should really be a CSS background-image, if that worked properly...
			new Element('td', {'html': '<img src="res/icons16/file.png" alt="File" title="' + file.name + ' [File]" /> ' +  file.name, 'class': 'filename'}).inject(row);
			new Element('td', {'html': file.total_chunks}).inject(row);
			new Element('td', {'html': file.done_chunks}).inject(row);
			new Element('td', {'html': file.priority}).inject(row);
		});

		// Now go through all the directories
		($H(data.dirs)).each(function(contents, dir)
		{
			//console.log('Process ' + dir + ' at level ' + level);
			var row = new Element('tr', {'class': 'dir level-' + level}).inject(tbody);
			// TODO: The image should really be a CSS background-image, if that worked properly...
			var cell = new Element('td', {'colspan': 4, 'html': '<img src="res/icons16/folder.png" alt="Directory" title="' + dir + ' [Directory]" /> ' + dir, 'class': 'filename'}).inject(row);			
			
			Torrent.files_process(contents, level + 1);
		});
	}
};

/**
 * Simple logging
 */
var Log = 
{
	'write': function(text, logonly)
	{
		var dateObj = new Date();
		var hour = dateObj.getHours();
		var minute = dateObj.getMinutes();
		var second = dateObj.getSeconds();
		// Format the numbers nicely
		if (hour < 9)
			hour = "0" + hour;
		if (minute < 9)
			minute = "0" + minute;
		if (second < 0)
			second = "0" + second;
			
		// TODO: Make this format not hard coded (possibly include date instead of just time)
		var date = hour + ':' + minute + ':' + second;
		// First, write it to the log
		var logEntry = new Element('li', {html: text}).inject($('log').getElement('ul'));
		// Add the timestamp
		new Element('span', {class: 'date', 'html': date}).inject(logEntry, 'top');
		
		// If it's not only for the log, put it in the status too
		if (!$defined(logonly) || logonly == false)
			$('toolbar_message').set('html', text);
	}
};