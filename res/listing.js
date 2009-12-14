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
 
// If we're on an unsupported browser (*cough* IE *cough*), tell them immediately
// IE8 seems to work, so we'll only warn for IE7 and lower
/*@cc_on
if (@_jscript_version < 5.8)
{
	alert('rTorrentWeb doesn\'t officially support Internet Explorer 7 and below. It is recommended to either upgrade to Internet Explorer 8, or get a better browser (such as Opera, Firefox, or Google Chrome). rTorrentWeb may not work properly otherwise.');
}
@*/

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
 * Our additional date functionality 
 */
Date.implement({
	/**
	 * Check if the current date is valid
	 */
	'is_valid': function()
	{
		return (this != 'Invalid Date' && this != 'NaN');
	}
});

/**
 * Our additional element functionality
 */
Element.implement({
	/**
	 * Make sure the specified child element is visible, and scroll to it if necessary
	 */
	'scrollToChild': function(child)
	{
		var height = this.getSize().y;
		var childY = child.getPosition(this).y;
		var childBottom = childY + child.getSize().y;
		
		// Is it within our visible area? No need to do anything.
		if (childY >= 0 && childBottom <= height)
			return;
			
		// Here's where we are right now
		var scroll = this.getScroll();
		// Do we have to scroll down?
		if (childBottom > height)
			this.scrollTo(scroll.x, scroll.y + (childBottom - height));
		// Otherwise, goin' up
		else
			// ChildY is negative, so we add it to go up.
			this.scrollTo(scroll.x, scroll.y + childY);
	}
});


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
			$(header).addEvent('click', el.sort.pass(i++));
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
		
		/* Try to work out the type (and hence how we have to sort it) based on the first value
		 * However, the name might screw up if it has a number at the start, so we explicitly
		 * check for that.
		 */
		
		var sorter;
		if (head_cells[sorted_by].get('text').trim() == 'Name')
			sorter = SortTable.sorters.case_insensitive;
		else if (SortTable.sorters.filesize_regex.test(temp[0][0]))
			sorter = SortTable.sorters.filesize;
		else if (!isNaN(parseFloat(temp[0][0])))
			sorter = SortTable.sorters.numeric;
		// Default to case insensitive
		else
			sorter = SortTable.sorters.case_insensitive;
		
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
		// Start a refresh before we do everything else (it can go in the background
		// while we initialise)
		List.refresh();
		// Initialise some stuff
		List.refresh_count = Settings.autorefresh_interval;
		List.init_settings();
		List.init_toolbar();
		List.init_tabs();
		List.init_sidebar();
		List.init_resize();
		List.init_labels();
		// Make the tables sortable
		SortTable.init($('torrents').getElement('table'));
		SortTable.init($('peers').getElement('table'));
		SortTable.init($('files').getElement('table'));
		
		// And if they're an admin, they have special things!
		Admin.init();
		// Other stuff:
		// Make the "Private change" button do something
		$('private_change').addEvent('click', Torrent.toggle_private);
		// Pressing keys in the torrent listing
		$('torrents').addEvent('keydown', List.keypress);
	},
	
	/**
	 * Load some settings
	 * Note: These are defined in /profile/get_settings
	 */
	'init_settings': function()
	{
		// Set the "only mine" tickbox depending on the setting
		$('only_mine').checked = Settings.only_mine;
		// Sidebar width
		$('sidebar').setStyle('width', Settings.sidebar_width);
	},
	
	/**
	 * Initialise the toolbar
	 */
	'init_toolbar': function()
	{
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
		$('add').addEvent('click', function()
		{
			Utils.popup(this.href, 400, 500);
			// Cancel the click.
			return false;
		});
		
		// Add the rss button
		$('rss').addEvent('click', function()
		{
			Utils.popup(this.href, 800, 700);
			// Cancel the click.
			return false;
		});
	},
	
	/**
	 * Initialise the resize handles
	 */
	'init_resize': function()
	{
		// Set the sizes of the pane stuff correctly
		List.resize_window();
		// Also, let's do that every resize
		window.addEvent('resize', List.resize_window);
		
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
			'modifiers': {'x': 'width', 'y': null},
			'onComplete': function()
			{
				Utils.save_setting('sidebar_width', $('sidebar').getStyle('width')); 
			}
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
				'cursor': 'n-resize'
			}
		}).inject(topPane, 'bottom');
		
		topPane.makeResizable({
			'handle': paneResizer,
			'modifiers': {'y': 'height', 'x': null},
			'onDrag': List.resize_window
		});
		
		$('bottom_pane').makeResizable({
			'handle': paneResizer,
			'modifiers': {'y': 'height', 'x': null},
			'invert': true
		});
	},

	/**
	 * Initialise the bottom tabs
	 */
	'init_tabs': function()
	{
		// Make the tabs do stuff
		$$('div#tabs li').addEvent('click', List.show_tab);
		// Set the tab to general (the default)
		List.show_tab.bind($('tab_general'))();
		// Hide all the stuff on the general tab by default
		$$('div#general > div').setStyle('display', 'none');
	},
	
	/**
	 * Initialise the sidebar
	 */
	'init_sidebar': function()
	{
		// Add the handlers for the sidebar filters
		$('sidebar').getElements('li.filter').each(function(el)
		{
			el.addEvent('click', function()
			{
				List.change_view(el.get('id').substring(8));
			});
		});
		
		// If they toggle the "only my torrents" tickbox, we have to re-filter the list
		$('only_mine').addEvent('click', List.only_mine);
	},
	
	/**
	 * Initialise labelling stuff
	 */
	'init_labels': function()
	{
	
		var sidebar_ul = $('sidebar').getElement('ul');
		var label_dropdown = $('label_dropdown');
		// Add all the labels to the sidebar
		Settings.labels.each(function(label)
		{
			// Add the filter
			new Element('li',
			{
				'id': 'label_' + label.id,
				'html': label.name,
				'class': 'filter',
				'styles':
				{
					'background-image': 'url(res/label_icons/' + label.icon + '.png)'
				},
				'events':
				{
					'click': List.label_click
				}
			}).store('id', label.id).inject(sidebar_ul);
		});
		
		// Make the "add label" button work
		$('label_add').addEvent('click', Torrent.add_label);
	},
	
	/**
	 * List keypress handler
	 */
	'keypress': function(event)
	{
		// Going down?
		if (event.key == 'down')
		{
			// Does this have someone under it? (that's what she said)
			var next_torrent = List.selected.getNext();
			if (next_torrent != null)
			{
				List.click.bind(next_torrent)();
				$('torrents').scrollToChild(next_torrent);
			}
			
			return false;
		}
		// Going up?
		else if (event.key == 'up')
		{
			// A torrent above this one?
			var prev_torrent = List.selected.getPrevious();
			if (prev_torrent != null)
			{
				List.click.bind(prev_torrent)();
				$('torrents').scrollToChild(prev_torrent);
			}
			
			return false;
		}
	},
	
	/**
	 * The "Only mine" tickbox was clicked
	 */
	'only_mine': function()
	{
		// Update the filter counts
		List.update_filter_counts();
		// And actually update the filter
		List.filter();
		// Save the setting		
		Utils.save_setting('only_mine', $('only_mine').checked ? 1 : 0); 
	},
	
	/**
	 * The currently selected label
	 */
	'label': null,
	
	/**
	 * A label was clicked
	 */
	'label_click': function()
	{
		var id = this.retrieve('id');

		// If they clicked the label they're already using, deselect it
		if (id == List.label)
		{
			List.label = null;
			$('label_' + id).removeClass('selected');
			List.refresh();
			return;
		}
		
		// Let's set the new label
		if (List.label != null)
			$('label_' + List.label).removeClass('selected');

		List.label = id;
		$('label_' + id).addClass('selected');
		List.refresh();
	},
		
	/**
	 * The current view (selected by sidebar)
	 */
	'view': 'all',
	
	/**
	 * Change the view
	 */
	'change_view': function(new_view)
	{
		// Deselect the old one
		$('sidebar_' + List.view).removeClass('selected');
		// Set the new one
		List.view = new_view;
		$('sidebar_' + List.view).addClass('selected');
		// Filter the table
		List.filter();
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
		/*List.refresh_request.send({
			data: Hash.toQueryString({'label': List.label})
		});*/
		// If we're getting by label, send the label request. Else send a general refresh
		if (List.label == null)
		{
			List.refresh_request.send({url: base_url + 'torrents/refresh'});
		}
		else
		{
			List.refresh_request.send({
				url: base_url + 'torrents/refresh_label',
				data: Hash.toQueryString({label: List.label})
			});
		}
		
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
		
		// Get our table
		var table = $('torrents').getElement('table').getElement('tbody');
		
		/* A list of all the torrents we have to remove. Starts with all of them,
		 * but gets filtered down to only what wasn't returned by the server
		 */
		var torrents_to_remove = new Hash();
		// Start by adding all to the remove list
		$each(table.rows, function(row)
		{
			torrents_to_remove[row.retrieve('hash')] = row;
		});
		
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
			
			// Style this row based on some stuff
			// Clear the current status
			row.set('class', '');
			// Add a class depending on owner
			// No owner?
			if (!torrent.owner)
			{
				row.addClass('not-mine');
				row.addClass('no-owner');
			}
			else if (torrent.owner.id != current_user)
				row.addClass('not-mine');
			else
				row.addClass('mine');
			// Also add a class based on state
			row.addClass('status-' + torrent.state);
			
			// Make sure these are integers
			torrent.size = parseInt(torrent.size);
			torrent.done = parseInt(torrent.done);
			torrent.total.up = parseInt(torrent.total.up);
			
			// The stuff that's in the table
			new Element('td', {'html': torrent.name, 'class': 'name'}).inject(row);
			new Element('td', {'html': torrent.state}).inject(row);
			new Element('td', {'html': torrent.size.format_size()}).inject(row);
			//new Element('td', {'html': torrent.done.format_size()}).inject(row);
			new Element('td', {'html': (torrent.done / torrent.size * 100).toFixed(2) + "%"}).inject(row);
			new Element('td', {'html': torrent.rate.down.format_size() + '/s'}).inject(row);
			new Element('td', {'html': torrent.rate.up.format_size() + '/s'}).inject(row);
			new Element('td', {'html': torrent.ratio, 'class': (torrent.ratio >= 1) ? 'good-ratio' : 'bad-ratio'}).inject(row);

			// All the other details
			row.store('hash', hash);
			row.store('data', torrent);
			// Since we know this is a valid torrent, we're not deleting it from the list.
			torrents_to_remove.erase(hash);
		});
		
		// Do we have a torrent currently selected? Better update its data
		if (List.selected != null)
			List.click.bind(List.selected)();
		
		// Remove all the torrents that are no longer available
		torrents_to_remove.each(function(row, hash)
		{
			row.dispose();
		});
		// Set the data for the sidebar
		List.update_filter_counts();
		// Filter the table
		List.filter();
		// Sort the table
		$('torrents').getElement('table').sort();
		
		// No selected torrent? Select the first one.
		if (List.selected == null)
		{
			var first_row = table.getElement('tr');
			if (first_row != null)
				List.click.bind(first_row)();
		}

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
	 * Update torrent counts for the sidebar filters
	 */
	'update_filter_counts': function()
	{
		// Start with 0 rows
		var cnt_all = 0;
		var cnt_seed = 0;
		var cnt_down = 0;
		var cnt_fin = 0;
		var cnt_stop = 0;
		var cnt_paused = 0;
		
		var rows = $A($('torrents').getElement('table').tBodies[0].rows);
		
		rows.each(function(row)
		{
			var data = row.retrieve('data');
			// Are we only showing *our* torrents?
			if ($('only_mine').checked && (!$defined(data.owner) || data.owner.id != current_user))
				return;
			
			cnt_all++;
			
			// Add it to our counts
			switch (data.state)
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
		$('sidebar_all').getElement('span').set('html', cnt_all);
		$('sidebar_seeding').getElement('span').set('html', cnt_seed);
		$('sidebar_downloading').getElement('span').set('html', cnt_down);
		$('sidebar_finished').getElement('span').set('html', cnt_fin);
		$('sidebar_stopped').getElement('span').set('html', cnt_stop);
		$('sidebar_paused').getElement('span').set('html', cnt_paused);
	},
	
	/**
	 * Filter the table based on the selected view
	 */
	'filter': function()
	{
		var rows = $A($('torrents').getElement('table').tBodies[0].rows);
		
		rows.each(function(row)
		{
			var data = row.retrieve('data');
			// By default, assume we should show it
			var show_this = true;
			// Are we showing based on a certain view, and it's not in that view?
			if (List.view != 'all' && data.state != List.view)
				show_this = false;
			// Are we only showing our torrents, but it's not ours?
			// Note: current_user is defined in listing.php view file (in the head of the page).
			if ($('only_mine').checked && (!$defined(data.owner) || data.owner.id != current_user))
				show_this = false;
			
			row.setStyle('display', show_this ? '' : 'none');
		});
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
		// Did it actually change? Let's assume YES for now.
		var torrent_changed = true;
		
		// Unselect the currently selected one
		if (List.selected != null)
		{
			List.selected.removeClass('selected');
			// Now we can check if it changed :o
			torrent_changed = List.selected.retrieve('hash') != this.retrieve('hash');
		}
		// Set the currently selected one
		List.selected = this;
		this.addClass('selected');
		
		var data = this.retrieve('data');
		
		// Now, enable the buttons that we need
		$('delete').removeClass('disabled');
		$('tab_peers').addClass('disabled');
		
		// Seeding or downloading? Stop and pause are needed.
		if (data.state == 'seeding' || data.state == 'downloading')
		{
			$('start').addClass('disabled');
			$('pause').removeClass('disabled');
			$('stop').removeClass('disabled');
			$('tab_peers').removeClass('disabled');
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
		
		// Update the current tab
		List.update_tab();
		// Do any admin stuff
		if (is_admin)
		{
			// If it changed, hide this.
			if (torrent_changed)
				Admin.hide_owner_change();
		}
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
		// Make sure the tab they clicked is enabled
		if (this.hasClass('disabled'))
			return;
		
		// If we have a selected tab, deselect it. If we don't, hide all.
		if (List.current_tab)
		{
			$(List.current_tab).setStyle('display', 'none');
			$('tab_' + List.current_tab).removeClass('selected');
		}
		else
		{
			// Hide all the tabs
			$$('div#details > div').setStyle('display', 'none');
			// Make them all deselected
			$$('div#tabs li').removeClass('selected');
		}
		
		// And show the one we want
		List.current_tab = tab;
		$(tab).setStyle('display', 'block');
		$('tab_' + tab).addClass('selected');
		// Better do an update
		List.update_tab();
	},
	
	/**
	 * Update the information in the current tab
	 */
	'update_tab': function()
	{
		// If there's no torrent, there's no data we have to load
		if (List.selected == null)
			return;
			
		// Is there a function for this tab?
		if ($defined(Torrent[List.current_tab]))
		{
			Torrent[List.current_tab].bind(List.selected)();
		}
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
					alert('An error occurred: ' + response.message);
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
			
		// Check if they want to delete the files
		var delete_data = confirm('Do you want to delete the data files as well as the torrent?\n - Click "OK" to delete the data files.\n - Click "Cancel" to just delete the torrent, and keep its data');
		Torrent.action(List.selected, delete_data ? 'delete_full' : 'delete');
	},
	
	/**
	 * Display general information about the torrent
	 */
	'general': function()
	{
		// Make sure the stuff is showing
		$$('div#general > div').setStyle('display', '');
		$('no_torrent').setStyle('display', 'none');
		
		var data = this.retrieve('data');
		var hash = this.retrieve('hash');
		// Let's work our how long ago this started
		var started_at = new Date();
		started_at.setTime(data.started_at * 1000);
		$('started').set('html', started_at.timeDiffInWords());
		$('started').set('title', started_at);
		
		// And also how long it's got left
		// Is it finished?
		if (data.complete)
		{
			$('remaining').set('html', 'Torrent is complete.');
			$('remaining').set('title', '');
		}
		else
		{
			// Timestamp for completion = now + (data left / download speed) seconds
			var completion = new Date();
			completion.setSeconds(completion.getSeconds() + (data.size - data.done) / data.rate.down);
			if (!completion.is_valid())
			{
				$('remaining').set('html', 'Unknown');
				$('remaining').set('title', '');
			}
			else
			{
				$('remaining').set('html', completion.timeDiffInWords());
				$('remaining').set('title', 'Estimated to complete in ' + (new Date()).timeDiff(completion, ' ') + ' (at ' + completion + ')');
			}
		}
		
		// Other stuff
		$('download_speed').set('html', data.rate.down.format_size() + '/s');
		$('upload_speed').set('html', data.rate.up.format_size() + '/s');
		$('total_down').set('html', data.done.format_size());
		$('total_up').set('html', data.total.up.format_size());
		// General
		$('hash').set('html',  hash);
		$('owner').set('html', data.owner ? data.owner.name : '[unknown]');
		$('private').set('html', data.private ? 'Yes' : 'No');
		// Only show the "change private" button if we're the owner
		$('private_change').setStyle('display', (data.owner && (data.owner.id == current_user)) ? '' : 'none');
		
		// Now to update the labels
		Torrent.get_labels.bind(this)();
	},
	
	/**
	 * Get the labels for the current torrent
	 */
	'get_labels': function()
	{
		$('labels').empty();
		
		// Do we have some already? Awesome
		if (this.retrieve('labels') != null)
		{
			Torrent.show_labels();
			return;
		}
		
		var hash = this.retrieve('hash');
		var data = this.retrieve('data');
		// Do we not own this torrent?
		if (!$defined(data.owner) || (data.owner.id != current_user))
		{
			new Element('li', {'html': 'You don\'t own this torrent, so can\'t modify its labels.'}).inject($('labels'));
			$('attach_label').setStyle('display', 'none');
			return;
		}
		
		// Otherwise, need to get them
		new Element('li', {'html': 'Loading...'}).inject($('labels'));
		// Send the request
		var myRequest = new Request({
			method: 'post',
			url: base_url + 'torrents/labels/' + hash,
			onSuccess: function(data_text)
			{
				// JSON decode the data
				var response = JSON.decode(data_text);
				// Was there an error?
				if (response.error)
				{
					Log.write('An error occurred while getting the label list: ' + response.message);
					return;
				}
				
				// Store the data
				$('tor_' + response.hash).store('labels', $H(response.labels));
				// And update it
				Torrent.show_labels();
			}
		}).send();
	},
	
	/**
	 * Update the label display for a torrent
	 */
	'show_labels': function()
	{
		// Labels this torrent currently has.
		var labels_on_torrent = new Array();
		
		var labels = $('labels');
		labels.empty();
		List.selected.retrieve('labels').each(function(label, id)
		{
			// TODO: Why is this not an integer initially? O_o
			id = parseInt(id);
			var labelEl = new Element('li',
			{
				'id': 'label-' + id,
				'html': ' ' + label.name //'<img alt="Label icon" src="res/label_icons/' + label.icon + '.png" width="16" height="16" /> ' + 
			}).inject(labels);
			
			// Delete button
			new Element('img', 
			{
				'class': 'icon',
				'alt': 'Delete',
				'title': 'Delete',
				'src': 'res/icons16/bin_closed.png',
				'events':
				{
					'click': Torrent.delete_label
				}
			}).inject(labelEl, 'top');
			
			labels_on_torrent.push(id);
		});
		
		// Now we have to fill the dropdown list
		var label_dropdown = $('label_dropdown');
		label_dropdown.empty();
		Settings.labels.each(function(label)
		{
			// Also add it to the labelling dropdown
			if (!label.internal && !labels_on_torrent.contains(label.id))
			{
				new Element('option', 
				{
					'html': label.name,
					'value': label.id
				}).inject(label_dropdown);
			}
		});
		// Did we end up with 0? Wow, this torrent has got it all!
		if (label_dropdown.options.length == 0)
		{
			$('no_labels').setStyle('display', 'block');
			$('attach_label').setStyle('display', 'none');
		}
		else
		{
			$('attach_label').setStyle('display', 'block');
			$('no_labels').setStyle('display', 'none');
			// They can add torrents now :D
			$('label_add').set('value', 'Add').disabled = false;
		}
	},
	
	/**
	 * Delete a label
	 */
	'delete_label': function()
	{
		// Show a deleting icon
		this.src = 'res/loading16.gif';
		var id = this.getParent().get('id').substring(6);
		// TODO: Possible race conditions, if they change torrent?
		var hash = List.selected.retrieve('hash');
		
		// Send the request
		var myRequest = new Request({
			method: 'post',
			url: base_url + 'torrents/del_label',
			data: Hash.toQueryString({'label_id': id, 'hash': hash}),
			onSuccess: function(data_text)
			{
				// JSON decode the data
				var response = JSON.decode(data_text);
				// Was there an error?
				if (response.error)
				{
					Log.write('An error occurred while deleting the label: ' + response.message);
					alert('An error occurred while deleting the label: ' + response.message);
					return;
				}
				
				// Do stuff
				// TODO: refresh just the labels, not the whole listing! :o
				// Our label listing is now old!
				$('tor_' + response.hash).store('labels', null);
				List.refresh();
			}
		}).send();
	},
	
	/**
	 * Add a label
	 */
	'add_label': function()
	{
		$('label_add').set('value', 'Adding...').disabled = true;
		Log.write('Adding label to ' + List.selected.retrieve('data').name + '...');
		
		var hash = List.selected.retrieve('hash');
		var id = $('label_dropdown').value;
		
		// Actually send the change request
		var myRequest = new Request({
			method: 'post',
			url: base_url + 'torrents/add_label',
			data: Hash.toQueryString({'label_id': id, 'hash': hash}),
			onSuccess: function(data_text)
			{
				var response = JSON.decode(data_text);
				// Was there an error?
				if (response.error)
				{
					Log.write('An error occurred: ' + response.message);
					alert('An error occurred: ' + response.message);
					return;
				}
				
				// Our label listing is now old!
				$('tor_' + response.hash).store('labels', null);
				// TODO: Should just refresh this torrent!!!
				List.refresh();			
			}
		}).send();
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
	},
		
	/**
	 * Get a list of peers for the torrent
	 */
	'peers': function()
	{
		//console.log(this.retrieve('hash'));
		var hash = this.retrieve('hash');
		var data = this.retrieve('data');
		
		// Hide the table initially.
		$('peers').getElement('table').setStyle('display', 'none');
		
		// If we aren't dowlnoading, or uploading there can be no peers can there :P
		if (data.state != 'seeding' && data.state != 'downloading')
		{
			$('peers').getElement('span').set('html', data.name + ' is stopped');
			return;
		}
		
		$('loading').setStyle('display', 'inline');
		Log.write('Retrieving peer listing for ' + data.name + '...');
		
		$('peers').getElement('span').set('html', 'Loading peer listing for ' +  data.name + '...');
		
		// Send the request
		var myRequest = new Request({
			method: 'post',
			url: base_url + 'torrents/peers/' + hash,
			onSuccess: function(data_text)
			{
				// JSON decode the data
				var response = JSON.decode(data_text);
				// Was there an error?
				if (response.error)
				{
					$('loading').setStyle('display', 'none');
					$(document.body).setStyle('cursor', 'default');
					$('peers').getElement('span').set('html', 'An error occured while retrieving peer listing for ' + data.name + ', it is possible your rTorrent build doesn\'t support this feature.');
					return;
					}
				
				// Actually process the peers now. 
				// Is this torrent still selected (they could have changed torrent by the time we get the reply)?
				if (List.selected.retrieve('hash') == response.hash)
				{
					// Here we go!
					Torrent.peers_process(response);
				}
				// Alllll done! :D
				$('peers').getElement('span').set('html', '');
				$('peers').getElement('table').setStyle('display', '');
				$('loading').setStyle('display', 'none');
				//$(document.body).setStyle('cursor', 'default');
				$('toolbar_message').set('html', '');
			}
		}).send();
	},
	
	/**
	 * Process a listing of peers
	 * @param hash The directory data (a hash containing "peers")
	 */
	'peers_process': function(data)
	{
		var tbody = $('peers').getElement('tbody');
		
		$('peers').getElement('tbody').empty();		
		
		// Go through all the peers
		data.peers.each(function(peer)
		{
			// Add this peer
			var row = new Element('tr').inject(tbody);
			new Element('td', {'html': peer.address}).inject(row);
			new Element('td', {'html': peer.client_version}).inject(row);
			new Element('td', {'html': peer.down_rate.format_size() + '/s'}).inject(row);
			new Element('td', {'html': peer.up_rate.format_size() + '/s'}).inject(row);
			new Element('td', {'html': peer.down_total.format_size()}).inject(row);
			new Element('td', {'html': peer.up_total.format_size()}).inject(row);
			new Element('td', {'html': peer.is_seeder}).inject(row);
		});
	},
	
	/**
	 * Toggle whether a torrent is private or not
	 */
	'toggle_private': function()
	{
		$('private_change').set('value', 'Changing...').disabled = true;
		var data = List.selected.retrieve('data');
		var hash = List.selected.retrieve('hash');
		Log.write('Changing private status of ' + data.name + '...');
		
		// Actually send the change request
		var myRequest = new Request({
			method: 'post',
			url: base_url + 'torrents/change_private',
			data: Hash.toQueryString({'hash': hash, 'private': data.private ? 0 : 1}),
			onSuccess: function(data_text)
			{
				var response = JSON.decode(data_text);
				// Was there an error?
				if (response.error)
				{
					Log.write('An error occurred: ' + response.message);
					alert('An error occurred: ' + response.message);
					return;
				}
				
				// All done.
				$('private_change').set('value', 'Change').disabled = false;
				$('private').set('html', response.private ? 'Yes' : 'No');
				List.refresh();
			}
		}).send();
	}
};

/**
 * Administration stuff
 */
var Admin = 
{
	/**
	 * Initialise stuff that only admins can access
	 */
	'init': function()
	{
		// If they're not an admin, they don't need this. :P
		if (!$defined(is_admin) || !is_admin)
			return;
			
		// Make the "change owner" button work
		$('owner_change').addEvent('click', Admin.show_owner_change);
		// And the save button, too
		$('owner_save').addEvent('click', Admin.change_owner);
	},
	
	/**
	 * Load a list of users for the owner dropdown
	 */
	'load_users': function()
	{
		// They can't save while we're loading!
		$('owner_save').disabled = true;
		$('owner_save').set('value', 'Save');
		// This is the dropdown of users
		var dropdown = $('owner_dropdown')
		// If it's loaded already, we don't need to re-load it.
		if (dropdown.retrieve('loaded'))
		{
			$('owner_save').disabled = false;
			// We need to select the current user
			var current_user = $('owner').get('html');
			$each(dropdown.options, function(option)
			{
				// Is it this user?
				// We could return here, but we have to disable "selected" on all the other items :(
				option.set('selected', (option.get('html') == current_user) ? 'selected' : '');
			});
			
			return;
		}
		
		// Delete all the currently listed users	
		dropdown.empty();
		// Add a "now loading"
		new Element('option', 
		{
			html: 'Loading...',
			selected: 'selected'
		}).inject(dropdown);
		
		// Now let's load the users
		var myRequest = new Request({
			method: 'post',
			url: base_url + 'admin/users/get_list',
			onSuccess: function(data_text)
			{
				var response = JSON.decode(data_text);
				// Was there an error?
				if (response.error)
				{
					Log.write('An error occurred: ' + response.message);
					alert('An error occurred: ' + response.message);
					return;
				}
				
				var dropdown = $('owner_dropdown');
				// Clear the current list
				dropdown.empty();
				var current_user = $('owner').get('html');
				// Fill the users list
				$each(response.users, function(username, id)
				{
					new Element('option', 
					{
						value: id,
						html: username,
						selected: current_user == username ? 'selected' : ''
					}).inject(dropdown);
				});
				
				// All loaded now!
				dropdown.store('loaded', true);
				$('owner_save').disabled = false;
			}
		}).send();
	},
	
	/**
	 * Show the owner change stuff
	 */
	'show_owner_change': function()
	{
		// Hide this button, and show the stuff to change
		$('owner_change').setStyle('display', 'none');
		$('owner').setStyle('display', 'none');
		$('owner_dropdown').setStyle('display', 'inline');
		$('owner_save').setStyle('display', 'inline');
		// Load a list of users
		Admin.load_users();
	},
	
	/**
	 * Hide the owner change stuff
	 */
	'hide_owner_change': function()
	{
		$('owner_change').setStyle('display', '');
		$('owner').setStyle('display', '');
		$('owner_dropdown').setStyle('display', '');
		$('owner_save').setStyle('display', '');
	},
	
	/**
	 * Change the owner of a torrent
	 */
	'change_owner': function()
	{
		$('owner_save').set('value', 'Saving...').disabled = true;
		Log.write('Changing owner for ' + List.selected.retrieve('data').name + '...');
		// Actually send the change request
		var myRequest = new Request({
			method: 'post',
			url: base_url + 'torrents/change_owner',
			data: Hash.toQueryString({
				'hash': List.selected.retrieve('hash'),
				'user_id': $('owner_dropdown').value,
				'username': $('owner_dropdown').getSelected().get('html')
			}),
			onSuccess: function(data_text)
			{
				var response = JSON.decode(data_text);
				// Was there an error?
				if (response.error)
				{
					Log.write('An error occurred: ' + response.message);
					alert('An error occurred: ' + response.message);
					return;
				}
				
				// Do stuff.
				$('owner').set('html', response.username);
				Admin.hide_owner_change();
				// TODO: Should just refresh this torrent. Need to fix some stuff.
				List.refresh();
				/*// Set the owner of this torrent
				var torrent = $('tor_' + response.hash);
				// Get its data
				var data = torrent.retrieve('data');
				data.owner.name = response.username
				data.owner.id = response.user_id
				// And now set the new data
				torrent.store('data', data);
				
				// Need to re-filter
				// Update the filter counts
				List.update_filter_counts();
				// And actually update the filter
				List.filter();*/			
			}
		}).send();
	}
};

/**
 * Utilities that don't really fit anywhere else
 */
var Utils = 
{
	/**
	 * Current window reference for the popup
	 */
	'popup_window': null,
	/**
	 * Open a popup
	 */
	'popup': function(url, height, width)
	{	
		// Do we already have a popup with this URL? Need to focus on it
		if (Utils.popup_window != null && !Utils.popup_window.closed && Utils.popup_window.location == url)
		{
			Utils.popup_window.focus();
		}
		// Otherwise, need to overwrite the URL, or open a new window
		else
		{
			if (Utils.popup_window != null)
				Utils.popup_window.close();
				
			Utils.popup_window = window.open(url, 'rTorrentPopup', 'location=no,menubar=no,status=no,titlebar=no,toolbar=no,height=' + height + ',width=' + width);
			
			if (Utils.popup_window == null)
			{
				alert('Please disable your popup blocker to use this feature.');
			}
			else
				Utils.popup_window.focus();
		}
	},
	
	/**
	 * Save a setting to the server
	 */
	'save_setting': function(variable, val)
	{
		// Save the setting
		var myRequest = new Request({
			method: 'post',
			url: base_url + 'profile/save_setting',
			data: Hash.toQueryString({'variable': variable, 'value': val}),
			onSuccess: function(data)
			{
				// It's assumed that any message is an error.
				if (data && data != '')
					alert(data);
			}
		}).send();
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
		if (hour <= 9)
			hour = "0" + hour;
		if (minute <= 9)
			minute = "0" + minute;
		if (second <= 9)
			second = "0" + second;
			
		// TODO: Make this format not hard coded (possibly include date instead of just time)
		var date = hour + ':' + minute + ':' + second;
		// First, write it to the log
		var logEntry = new Element('li', {html: text}).inject($('log').getElement('ul'), 'top');
		// Add the timestamp
		new Element('span', {'class': 'date', 'html': date}).inject(logEntry, 'top');
		
		// If it's not only for the log, put it in the status too
		if (!$defined(logonly) || !logonly)
			$('toolbar_message').set('html', text);
	}
};