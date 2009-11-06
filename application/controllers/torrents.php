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

class Torrents_Controller extends Base_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->rtorrent = new Rtorrent;
	}
	public function index()
	{
		//new View('index');
		View::factory('listing')->render(true);
	}
	
	/**
	 * Refresh the torrent listing
	 */
	public function refresh()
	{
		if (false === ($torrents = $this->rtorrent->listing()))
			die(json_encode(array(
				'error' => true,
				'message' => $this->rtorrent->error()
			)));
		
		// We have to add owner information to all the torrents now...
		// Get database stuff
		$rows = ORM::factory('torrent')->find_all();
		foreach ($rows as $row)
		{
			// Do we not have this torrent?
			if (!isset($torrents[$row->hash]))
				continue;
				
			// Is it private and not ours? If so, remove it from our listing
			if ($row->private && $row->user_id != $this->user->id)
				unset($torrents[$row->hash]);
			// Otherwise, we have to add its owners name
			else
				$torrents[$row->hash]['owner'] = $row->user->username;
			
		}
			
		echo json_encode(array(
			'error' => false,
			'data' => $torrents,
		));
	}
	
	/**
	 * Get a list of the files in a particular torrent
	 */
	public function files($hash)
	{
		if (!($results = $this->rtorrent->files_tree($hash)))
			die(json_encode(array(
				'error' => true,
				'message' => $this->rtorrent->error()
			)));
			
		echo json_encode(array(
			'error' => false,
			'hash' => $hash,
			'files' => $results['files'],
			'dirs' => $results['dirs'],
		));
	}
	
	/**
	 * Get a list of the peers for a particular torrent
	 */
	public function peers($hash)
	{
		if (!($results = $this->rtorrent->peers($hash)))
			die(json_encode(array(
				'error' => true,
				'message' => $this->rtorrent->error()
			)));
		
		echo json_encode(array(
			'error' => false,
			'hash' => $hash,
			'peers' => $results,
		));
	}
	
	/**
	 * A few simple actions (pause, stop, start)
	 */
	public function action($action, $hash)
	{
		//Check that this user owns this torrent before we allow this!
		if (!$this->_check_owner($hash))
		{
			die(json_encode(array(
				'error' => true,
				'message' => 'You don\'t own that torrent!',
				'hash' => $hash,
			)));
		}
		// The really simple ones with no return values. We just call directly
		// through to the rTorrent library
		if (in_array($action, array('pause', 'start', 'stop')))
		{
			$this->rtorrent->$action($hash);
			echo json_encode(array(
				'error' => false,
				'hash' => $hash
			));
		}
		// Otherwise, it's something more advanced? It'll have its own function,
		// let's just point them there.
		elseif (in_array($action, array('delete')))
		{
			$this->$action($hash);
		}
		else
		{
			echo json_encode(array(
				'error' => true,
				'message' => 'Unknown action',
				'hash' => $hash
			));
		}
	}
	
	/**
	 * Deleting a torrent :o
	 * Can only be called via action() handler above, so we know that the user is 
	 * authenticated to do stuff with this torrent!
	 * TODO: Delete data files too?
	 */
	private function delete($hash)
	{
		// Delete it from rTorrent
		$this->rtorrent->delete($hash);
		// Also delete it from the database
		ORM::factory('torrent', $hash)->delete();
		
		echo json_encode(array(
			'error' => false,
			'action' => 'delete',
			'hash' => $hash
		));
	}
	
	
	/** 
	 * Add a new torrent
	 * TODO: This  function is a MESS. Improve it! :D
	 */
	public function add()
	{
		// Did they actually submit the form?
		if (isset($_POST['submit']))
		{
			// Are they uploading a torrent via file upload?
			if ($this->input->post('type') == 'file')
			{
				// Let's try this upload
				$_FILES = Validation::factory($_FILES)->
					add_rules('torrent_file', 'required', 'upload::valid', 'upload::type[torrent]')->
					add_callbacks('torrent_file', array($this, '_unique_torrent'));
				if (!$_FILES->validate())
				{
					// TODO: Proper error handling
					echo 'Some errors were encountered while adding your torrent:<br />
	<ul>
		<li>', implode('</li>
		<li>', $_FILES->errors()), '</li>
	</ul>';
					die();
				}
				
				// Better save it to a proper location
				$filename = upload::save('torrent_file', null, Kohana::config('config.metadata_dir'));
				$hash = $_FILES->torrent_file['hash'];
			}
			else
			{
				// Better validate some stuffs
				$post = Validation::factory($_POST)->
					add_rules('torrent_url', 'required', 'valid::url_ok');
					
				if (!$post->validate())
				{
					// TODO: Proper error handling
					echo 'Some errors were encountered while adding your torrent:<br />
	<ul>
		<li>', implode('</li>
		<li>', $post->errors()), '</li>
	</ul>';
					die();
				}
				
				// Now we can try loading the torrent, since we know the URL is
				// valid. It might be an evil URl though, so later we check
				// if it's actually a torrent!
				// Temp filename = [time]-[user id]
				$filename = Kohana::config('config.metadata_dir') . '/' . time() . '-' . $this->user->id . '.torrent';
				$buffer = file_get_contents($this->input->post('torrent_url'));
				file_put_contents($filename, $buffer);
				
				// Check that it's not a duplicate
				// Calculate the hash of the torrent
				// Hash = SHA1 of the encoded torrent info
				// It's stored so we don't need to calculate it twice
				$torrent_data = new Bdecode($filename);
				$bencode = new Bencode();
				$hash = strtoupper(bin2hex(sha1($bencode->encode($torrent_data->result['info']), true)));
				// Check this torrent
				if ($this->rtorrent->exists($hash))
					die('Torrent already exists on the server');
				
			}
			
			// Try to add it to rTorrent
			$this->rtorrent->add($filename, $this->user->homedir);
			// Check that the torrent was added properly
			if (!$this->rtorrent->exists($hash))
				die('Torrent seems invalid');
				
			// Add this torrent into the database
			$torrent = ORM::factory('torrent');
			$torrent->hash = $hash;
			$torrent->private = (bool)$this->input->post('private');
			// This marks it as our torrent - Adds the torrent->user relation
			//$torrent->add($this->user);
			$torrent->user_id = $this->user->id;
			$torrent->save();
			// Are they choosing which files to download?
			if ($this->input->post('choose_files'))
				// Better bring them to the right place
				url::redirect('torrents/add_files/' . $hash);
			// Otherwise, we're done here!
			else
			{
				$this->rtorrent->start($hash);
				// TODO: Messy!!
				$template = new View('template_popup');
				$template->title = 'Torrent Added Successfully';
				$template->content = '<script type="text/javascript">window.opener.List.refresh(); self.close();</script>';
				$template->render(true);
			}
		}
		else
		{
			$template = new View('template_popup');
			$template->title = 'Add New Torrent';
			$template->content = new View('torrent/add');
			$template->content->homedir = $this->user->homedir;
			$template->render(true);
		}
	}
	
	/**
	 * Step 2 of adding a torrent - Choosing files for it
	 */
	public function add_files($hash)
	{
		// Is this not our torrent?
		if (!$this->_check_owner($hash))
			url::redirect('');
		
		// Did they actually submit?
		if (isset($_POST['submit']))
		{
			$priorities = array();
			// Get a list of all the files in the torrent
			// TODO: Is this really needed? I just need the count, really. :P
			$file_info = $this->rtorrent->files($hash);
			
			// Now we go through and see exactly what we have to enable
			foreach ($file_info as $file_id => &$file)
				$priorities[$file_id] = isset($_POST['files'][$file_id]) ? 1 : 0;

			// Actually set the priorities
			$this->rtorrent->set_file_priorities($hash, $priorities);
			// Now, start the torrent
			$this->rtorrent->start($hash);
			//url::redirect('');
			// TODO: Messy!!
			$template = new View('template_popup');
			$template->title = 'Torrent Added Successfully';
			$template->content = '<script type="text/javascript">window.opener.List.refresh(); self.close();</script>';
			$template->render(true);
		}
		// Get the files in this torrent
		$results = $this->rtorrent->files_tree($hash);
		
		$template = new View('template_popup');
		$template->title = 'Add New Torrent &mdash; Step 2';
		$template->content = new View('torrent/add_files');
		$template->content->dirs = $results['dirs'];
		$template->content->files = $results['files'];
		$template->render(true);
	}
	
	/**
	 * Check if we own a torrent
	 */
	private function _check_owner($hash)
	{
		// Load this torrent
		$torrent = ORM::factory('torrent', $hash);
		// Does it even exist in the database? If not, assume we can do stuff 
		// with it TODO: Is this acceptable?
		if (!$torrent->loaded)
			return true;

		return $torrent->user_id == $this->user->id;
	}
	
	/**
	 * Check if a torrent already exists on the server
	 */
	public function _unique_torrent(Validation $validation, $field)
	{
		$file =& $validation[$field];

		// Calculate the hash of the torrent
		// Hash = SHA1 of the encoded torrent info
		// It's stored so we don't need to calculate it twice
		$torrent_data = new Bdecode($file['tmp_name']);
		$bencode = new Bencode();
		$file['hash'] = strtoupper(bin2hex(sha1($bencode->encode($torrent_data->result['info']), true)));
		// Check this torrent
		if ($this->rtorrent->exists($file['hash']))
			$validation->add_error($field, 'torrent_exists');
	}
}
?>