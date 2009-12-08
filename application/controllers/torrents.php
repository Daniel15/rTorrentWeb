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
			{
				$torrents[$row->hash]['owner']['name'] = $row->user->username;
				$torrents[$row->hash]['owner']['id'] = $row->user->id;
			}
			
		}
			
		echo json_encode(array(
			'error' => false,
			'data' => $torrents,
		));
	}
	
	/**
	 * Refresh the torrent listing, based on a label
	 */
	public function refresh_label()
	{
		if (false === ($torrents = $this->rtorrent->listing()))
			die(json_encode(array(
				'error' => true,
				'message' => $this->rtorrent->error()
			)));
		
		$results = array();
		
		// We have to add owner information to all the torrents now...
		/* Get database stuff
		 * This assumes that only this user's torrents are there, which should
		 * be the case - Users are restricted to only adding labels to their own
		 * torrents
		 */
		$rows = ORM::factory('label', $this->input->post('label'))->torrents;
		foreach ($rows as $row)
		{
			// Do we not have this torrent? Missing somehow? O_o
			if (!isset($torrents[$row->hash]))
				continue;

			// Just to be safe:
			// Is it private and not ours?
			if ($row->private && $row->user_id != $this->user->id)
				continue;
			
			// Add it!
			$results[$row->hash] = $torrents[$row->hash];
			$results[$row->hash]['owner']['name'] = $row->user->username;
			$results[$row->hash]['owner']['id'] = $row->user->id;
		}
			
		echo json_encode(array(
			'error' => false,
			'data' => $results,
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
		$all_errors = array();
		$torrents = array();
		
		// Did they actually submit the form?
		if (isset($_POST['submit']))
		{
			// Are they uploading a torrent via file upload?
			if ($this->input->post('type') == 'file')
			{				
				$_FILES = Validation::factory($_FILES)->
					add_rules('*', 'required', 'upload::valid', 'upload::type[torrent]')->
					add_callbacks('*', array($this, '_unique_torrent'));
					
				if (!$_FILES->validate())
				{					
					// Better check which torrents had errors
					foreach ($_FILES->errors('torrent_add_errors') as $torrent_file => $error)
					{
						// Better save this error
						$all_errors[] = htmlspecialchars($_FILES[$torrent_file]['name']) . ': ' . $error;
						// This torrent is BAD! Remove it!
						unset($_FILES[$torrent_file]);
					}
				}
				
				// So now we're left with all the good files. Better go through
				// them.
				foreach ($_FILES as $torrent_file => $file_info)
				{
					// Save this one
					$torrents[$file_info['hash']] = upload::save($torrent_file, null, $this->config->get('metadata_dir'));
				}
			}
			else
			{
				// TODO: Handle this better
				if (!isset($_POST['torrent_url']) || !is_array($_POST['torrent_url']))
					die('Invalid POST');
					
				// Better validate the URLs
				$urls = $_POST['torrent_url'];
				$post = Validation::factory($urls)->
					add_rules('*', 'valid::url_ok');
					
				if (!$post->validate())
				{
					foreach ($post->errors('torrent_add_errors') as $url_id => $error)
					{
						$all_errors[] = htmlspecialchars($urls[$url_id]) . ': ' . $error;
						// This one is BAD! Remove it :o
						unset($urls[$url_id]);
					}
					
				}
				
				/* Now we can try loading the torrents, since we know the URLs are
				 * valid. It might be an evil URL though, so later we check
				 * if it's actually a torrent!
				 * Temp filename = [time]-[user id]-[url id]
				 */
				foreach ($urls as $id => $url)
				{
					$filename = $this->config->get('metadata_dir') . '/' . time() . '-' . $this->user->id . '-' . $id . '.torrent';
					$buffer = file_get_contents($url);
					file_put_contents($filename, $buffer);

					// Check that it's not a duplicate
					// Calculate the hash of the torrent
					// Hash = SHA1 of the encoded torrent info
					$torrent_data = new Bdecode($filename);
					if (empty($torrent_data->result['info']))
					{
						$all_errors[] = htmlspecialchars($url) . ': ' . Kohana::lang('torrent_add_errors.default.torrent_invalid');
						continue;
					}
					$bencode = new Bencode();
					$hash = strtoupper(bin2hex(sha1($bencode->encode($torrent_data->result['info']), true)));
					// Check this torrent
					if ($this->rtorrent->exists($hash))
					{
						$all_errors[] = htmlspecialchars($url) . ': ' . Kohana::lang('torrent_add_errors.default.torrent_exists');
						continue;
					}
					
					// If we get here, it should (hopefully) be valid!
					$torrents[$hash] = $filename;
				}
			}
			
			// Do we not have any torrents left? O_o
			if (count($torrents) == 0)
			{
				echo 'None of the torrents you uploaded could be added:<br />
	<ul>
		<li>', implode('</li>
		<li>', $all_errors), '</li>
	</ul>';
				die();
			}
			
			// Now let's go through all our torrents
			foreach ($torrents as $hash => $filename)
			{
				// Try to add it to rTorrent
				$this->rtorrent->add($filename, $this->user->homedir);
				// Check that the torrent was added properly
				if (!$this->rtorrent->exists($hash))
				{
					$all_errors[] = htmlspecialchars($filename) . ': ' . Kohana::lang('torrent_add_errors.default.torrent_invalid');
					// Better go to the next one, and ignore this one.
					continue;
				}
			
				// Add this torrent into the database
				$torrent = ORM::factory('torrent');
				$torrent->hash = $hash;
				$torrent->private = (bool)$this->input->post('private');
				// This marks it as our torrent - Adds the torrent->user relation
				//$torrent->add($this->user);
				$torrent->user_id = $this->user->id;
				
				if ($this->input->post('label_id'))
					$torrent->add(ORM::factory('label', $this->input->post('label_id')));
				
				$torrent->save();
			}
			
			// Are they choosing files, and did they only upload one torrent?
			if ($this->input->post('choose_files') && count($torrents) == 1)
			{
				// Better bring them to the right place
				url::redirect('torrents/add_files/' . array_shift(array_keys($torrents)));
			}
			
			// Otherwise, we start all the torrents, if we're starting them
			if ($this->input->post('start'))
			{
				foreach ($torrents as $hash => $filename)
					$this->rtorrent->start($hash);
			}
			
			// TODO: Messy!!
			$template = new View('template_popup');
			$template->title = 'Torrents Added Successfully';
			$template->content = '<script type="text/javascript">window.opener.List.refresh();</script>';
			// Did we have any errors?
			if (count($all_errors) > 0)
			{
				$template->content .= 'However, the following errors were encountered:<br />
	<ul>
		<li>' . implode('</li>
		<li>', $all_errors) . '</li>
	</ul><br />
	<a href="javascript:self.close();">Close this window</a>';
			}
			else
				$template->content .= '<script type="text/javascript">self.close();</script>';
				
			$template->render(true);
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
			if (false === $this->rtorrent->set_file_priorities($hash, $priorities))
			{
				die('An error occured while setting the file priorities.');
			}
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
	 * Change the owner of the torrent. Requires admin permission
	 */
	public function change_owner()
	{
		// Better make sure they're an admin
		if (!$this->auth->logged_in('admin'))
		{
			die(json_encode(array(
				'error' => true,
				'message' => 'No permission to access this area! You\'re not an admin.'
				)));
		}
		
		// Try to get this torrent
		$torrent = ORM::factory('torrent', $this->input->post('hash'));
		// If it doesn't exist, it was added before rTorrentWeb was installed.
		// We can add it here.
		if (!$torrent->loaded)
		{
			// Add this torrent into the database
			$torrent->hash = $this->input->post('hash');
			// TODO: Don't assume this?
			$torrent->private = false;
		}
		
		// Set the owner
		// TODO: Validation
		$torrent->user_id = $this->input->post('user_id');
		$torrent->save();
		
		echo json_encode(array(
			'error' => false,
			'hash' => $this->input->post('hash'),
			'username' => $this->input->post('username'),
		));
	}
	
	/**
	 * Get a list of labels for a torrent
	 */
	public function labels($hash)
	{
		// Check that this user owns this torrent before we allow this!
		if (!$this->_check_owner($hash))
		{
			die(json_encode(array(
				'error' => true,
				'message' => 'You don\'t own that torrent!',
				'hash' => $hash,
			)));
		}
		
		$torrent = ORM::factory('torrent', $hash);
		// No torrent?
		if (!$torrent->loaded)
			die(json_encode(array(
				'error' => false,
				'labels' => array(),
				'hash' => $hash,
			)));
		
		// Let's get all its labels
		$labels = array();
		foreach ($torrent->orderby('name')->labels as $label)
		{
			//$labels[$label->id] = $label->as_array();
			// Only get aruff we need
			$labels[$label->id] = array(
				'name' => $label->name,
				'icon' => $label->icon,
			);
		}
		
		echo json_encode(array(
			'error' => false,
			'labels' => $labels,
			'hash' => $hash
		));
	}
	
	/**
	 * Delete a label from a torrent
	 */
	public function del_label()
	{
		// Check that this user owns this torrent before we allow this!
		if (!$this->_check_owner($this->input->post('hash')))
		{
			die(json_encode(array(
				'error' => true,
				'message' => 'You don\'t own that torrent!'
			)));
		}
		
		// Get the torrent
		$torrent = ORM::factory('torrent', $this->input->post('hash'));
		// Delete the relationship
		$torrent->remove(ORM::factory('label', $this->input->post('label_id')));
		$torrent->save();
		
		echo json_encode(array(
			'error' => false,
			'hash' => $this->input->post('hash'),
			'label_id' => $this->input->post('label_id'),
		));
	}
	
	/**
	 * Add a label to a torrent
	 */
	public function add_label()
	{
		// Check that this user owns this torrent before we allow this!
		if (!$this->_check_owner($this->input->post('hash')))
		{
			die(json_encode(array(
				'error' => true,
				'message' => 'You don\'t own that torrent!'
			)));
		}
		
		// Get the torrent
		$torrent = ORM::factory('torrent', $this->input->post('hash'));
		// Add the relationship
		$torrent->add(ORM::factory('label', $this->input->post('label_id')));
		$torrent->save();
		
		echo json_encode(array(
			'error' => false,
			'hash' => $this->input->post('hash'),
			'label_id' => $this->input->post('label_id'),
		));
	}
	
	/**
	 * Check if we own a torrent
	 */
	private function _check_owner($hash)
	{
		// Admins can do ANYTHING :o
		if ($this->auth->logged_in('admin'))
			return true;
			
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
		// If there's no info, assume it's invalid and bail
		if (empty($torrent_data->result['info']))
		{
			$validation->add_error($field, 'torrent_invalid');
			return;
		}
		$bencode = new Bencode();
		$file['hash'] = strtoupper(bin2hex(sha1($bencode->encode($torrent_data->result['info']), true)));
		// Check this torrent
		if ($this->rtorrent->exists($file['hash']))
			$validation->add_error($field, 'torrent_exists');
	}
}
?>