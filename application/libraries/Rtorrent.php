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
 
class Rtorrent_Core
{
	private $_rpcerror;
	
	public function error()
	{
		return $this->_rpcerror;
	}
	
	/**
	 * Send a request to the rTorrent XMLRPC server
	 */
	public function do_call($function, $params)
	{
		$this->_rpcerror = '';
		// Encode the request
		$request = xmlrpc_encode_request($function, $params);
		// Create the stream for the request
		$context = stream_context_create(array('http' => array(
			'method' => 'POST',
			'header' => 'Content-Type: test/xml',
			'content' => $request
		)));
		
		// Actually try the request
		if (!($xml = @file_get_contents(Config::instance()->get('rpcurl'), false, $context)))
		{
			// Didn't work?
			$this->_rpcerror = 'Could not connect to rTorrent XMLRPC';
			return false;
		}

		// Fix as per http://au2.php.net/manual/en/function.xmlrpc-decode.php#93080
		$xml = str_replace('i8>', 'i4>', $xml);
		$response = xmlrpc_decode($xml);
		
		// Is it an error?
		if (is_array($response) && xmlrpc_is_fault($response))
		{
			$this->_rpcerror = $response['faultCode'] . ': ' . $response['faultString'];
			return false;
		}
		
		return $response;
	}
	
	/**
	 * Send a multicall to the rTorrent XMLRPC server. A multicall returns 
	 * multiple types of data in one request
	 */
	public function do_multicall($type, $params)
	{
		/* Sometimes rTorrent returns NULL, but works fine the next request. For example,
		 * on my test development server, getting the file listing fails about 1/10 of the
		 * time, and refreshing the page works fine in fixing it. This loop is a horrible 
		 * terrible UGLY HACK, but I guess it works for now. Really have to figure out
		 * the proper solution to this, as this could cause serious issues (infinite loop,
		 * anyone?)
		 */
		do
		{
			// Actually do the call
			if (($response = $this->do_call($type . '.multicall', $params)) === false)
			{
				return false;
			}
		} while (is_null($response)); // See big comment above
			
		// Shift the parameters if we have a view as the first one (so we just have actual parameters)
		if ($type == 'd')
			array_shift($params);
		elseif ($type == 'f' || $type == 'p')
		{
			array_shift($params);
			array_shift($params);
		}
		
		// Set the parameter names (so we don't just have numeric indexes in the result)
		foreach ($response as &$response_item)
			$response_item = array_combine($params, $response_item);
		
		return $response;
	}
	
	/**
	 * Get a listing of torrents
	 */
	public function listing()
	{
		$results = array();
		
		if (false === ($response = $this->do_multicall('d', array('', 
			'd.get_hash=',
			'd.get_name=',
			'd.get_size_bytes=',
			'd.get_completed_bytes=',
			'd.get_down_rate=',
			'd.get_up_rate=',
			'd.get_down_total=',
			'd.get_up_total=', 
			'd.is_open=',
			'd.is_active=',
			'd.is_hash_checking=',
			'd.get_ratio=',
			
			'd.get_chunk_size=',
			'd.get_size_chunks=',
			'd.get_completed_chunks='
		))))
		{
			return false;
		}
		
		foreach ($response as $torrentInfo)
		{
			// If the size overflows, use the chunk size to work it out
			// This works around the overflow bug in rTorrent (fixed with newer
			// versions of XMLRPC-c, but most packages of rTorrent still have 
			// the bugged version)
			/*if ($torrentInfo['d.get_size_bytes='] < 0)*/
			{
				// Do we have the BC library?
				if (function_exists('bcmul'))
				{
					$size = bcmul($torrentInfo['d.get_chunk_size='], $torrentInfo['d.get_size_chunks=']);
					$done = bcmul($torrentInfo['d.get_chunk_size='], $torrentInfo['d.get_completed_chunks=']);
					$total_up = bcmul($done, $torrentInfo['d.get_ratio='] / 1000);
				}
				// This will be inaccurate! But oh well, not much we can do :P
				else
				{
					$size = $torrentInfo['d.get_chunk_size='] * $torrentInfo['d.get_size_chunks='];
					$done = $torrentInfo['d.get_chunk_size='] * $torrentInfo['d.get_completed_chunks=']; // Chunk size * completed chunks
					$total_up = $done * $torrentInfo['d.get_ratio='] / 1000;
				}
			}
			/*else
			{
				$size = $torrentInfo['d.get_size_bytes='];
				$done = $torrentInfo['d.get_completed_bytes='];
			}*/
			
			$torrent = array(
				//'hash' => $torrentInfo[0],
				'name' => $torrentInfo['d.get_name='],
				'size' => $size,
				'done' => $done,
				'rate' => array(
					'down' => $torrentInfo['d.get_down_rate='],
					'up' => $torrentInfo['d.get_up_rate='],
				),
				'total' => array(
					'down' => $done,//$torrentInfo['d.get_down_total='],
					//'up' => $torrentInfo['d.get_up_total='],
					'up' => $total_up,
					
				),
				'ratio' => $torrentInfo['d.get_ratio='] / 1000,
				'complete' => $size == $done,
				// We set the state below
				'state' => 'unknown'
			);
			
			// Get the state of this torrent
			// Is it hash checking?
			if ($torrentInfo['d.is_hash_checking='])
			{
				$torrent['state'] = 'hashing';
				// TODO: Should also use get_chunks_hashed to get how many chunks have been hashed. Not necessary for now though.
			}
			// Is it open and active?
			elseif ($torrentInfo['d.is_open='] && $torrentInfo['d.is_active='])
			{
				// If it's complete, it's seeding
				if ($torrent['complete'])
					$torrent['state'] = 'seeding';
				// Otherwise, downloading
				else
					$torrent['state'] = 'downloading';
			}
			// Open, not active, and incomplete? Paused
			elseif ($torrentInfo['d.is_open='] && !$torrentInfo['d.is_active='] && !$torrent['complete'])
				$torrent['state'] = 'paused';
			// Not active and complete? Finished
			elseif (!$torrentInfo['d.is_active='] && $torrent['complete'])
				$torrent['state'] = 'finished';
			// Not open and not active? Stopped
			elseif (!$torrentInfo['d.is_open='] && !$torrentInfo['d.is_active='])
				$torrent['state'] = 'stopped';
			
			$results[$torrentInfo['d.get_hash=']] = $torrent;
		}
		
		return $results;
	}
	
	/**
	 * Get a list of files from a torrent
	 */
	public function files($hash)
	{
		if (false === ($response = $this->do_multicall('f', array($hash, '',
			'f.get_path=',
			'f.get_completed_chunks=',
			'f.get_size_chunks=',
			'f.get_priority=',
		))))
			return false;
			
		$results = array();
		foreach ($response as $file)
		{
			$results[] = array(
				'name' => $file['f.get_path='],
				'total_chunks' => $file['f.get_size_chunks='],
				'done_chunks' => $file['f.get_completed_chunks='],
				'priority' => $file['f.get_priority='],
			);
		}
		// Sort them by filename
		uasort($results, array($this, 'file_sort'));
		
		return $results;
	}
	
	/**
	 * Sort function for files
	 * Sorts by file['name']
	 */
	private function file_sort($a, $b)
	{
		return strcasecmp($a['name'], $b['name']);
	}
	
	/**
	 * Get a list of files as a tree
	 */
	public function files_tree($hash)
	{
		if (false === ($response = $this->files($hash)))
			return false;
		
		/* Here, we sort directories into a tree. We first sort files into 
		 * directories, and then put the directories as children of their 
		 * parents. Finally, we return just the root nodes.
		 */
		 
		// Stuff in the root directory
		$root = array(
			'dirs' => array(),
			'files' => array()
		);
		// An array of /all/ the directories
		$directories = array();
		
		// Here, we sort all the files into directories
		foreach ($response as $id => $file)
		{
			$file['num'] = $id;
			// Try to find the last slash in the directory name
			$slash_pos = strrpos($file['name'], '/');
			// If there's no slash, this must be a file in the root directory
			if ($slash_pos === false)
			{
				$root['files'][] = $file;
			}
			else
			{
				$path = substr($file['name'], 0, $slash_pos);
				// Set the filename (so it doesn't have the path in it)
				$file['name'] = substr($file['name'], $slash_pos + 1);
				// And now we add it to that directory
				$directories[$path]['files'][] = $file;
			}
		}
		
		// Now we process the directories - And make the tree :D
		foreach ($directories as $name => &$directory)
		{
			// Make sure its "dirs" setting is set (even if empty)
			if (!isset($directory['dirs']))
				$directory['dirs'] = array();
				
			// Try to find the last slash
			$slash_pos = strrpos($name, '/');
			// No slash? It's in the root
			if ($slash_pos === false)
			{
				$root['dirs'][$name] = &$directory;
			}
			// Otherwise, it's in another directory
			else
			{
				$parent = substr($name, 0, $slash_pos);
				// Replace the directory name with just its name (no path)
				$name = substr($name, $slash_pos + 1);
				// Now we add it as a child to the directory
				$directories[$parent]['dirs'][$name] = &$directory;
			}
		}
		
		// All done now!
		return $root;
	}
	
	/**
	 * Get peers from a torrent
	 */
	public function peers($hash)
	{
		if (false === ($response = $this->do_multicall('p', array($hash, '',
			'p.get_address=',
			'p.get_client_version=',
			'p.get_down_rate=',
			'p.get_down_total=',
			'p.get_up_rate=',
			'p.get_up_total=',
			'p.is_incoming=',
		))))
			return false;
		
		$results = array();
		foreach ($response as $peer)
		{
			$results[] = array(
				'address' => $peer['p.get_address='],
				'client_version' => $peer['p.get_client_version='],
				'down_rate' => $peer['p.get_down_rate='],
				'down_total' => $peer['p.get_down_total='],
				'up_rate' => $peer['p.get_up_rate='],
				'up_total' => $peer['p.get_up_total='],
				'is_incoming' => $peer['p.is_incoming='],
			);
		}
		
		return $results;
	}

	/**
	 * Pause a torrent
	 */
	public function pause($hash)
	{
		$this->do_call('d.stop', array($hash));
	}
	
	/**
	 * Start a torrent
	 */
	public function start($hash)
	{
		$this->do_call('d.open', array($hash));
		$this->do_call('d.start', array($hash));
	}
	
	/**
	 * Stop a torrent
	 */
	public function stop($hash)
	{
		$this->do_call('d.stop', array($hash));
		$this->do_call('d.close', array($hash));
	}
	
	/**
	 * Check if a certain torrent exists on the server
	 */
	public function exists($hash)
	{
		return $this->do_call('d.get_name', $hash) !== false;
	}
	
	/**
	 * Add a new torrent
	 */
	public function add($torrent, $directory)
	{
		// Set the directory
		$this->do_call('set_directory', $directory);
		// Actually add the download
		// TODO: Error checking. rTorrent seems to return int(0) whether this worked
		// or not, so I don't know exactly how we'd check for an error. Perhaps check
		// if the hash was added successfully?
		$this->do_call('load', $torrent);
		// Change back to the default directory
		$this->do_call('set_directory', $this->config->get('torrent_dir'));
	}
	
	/**
	 * Set the priority of files in the torrent
	 */
	public function set_file_priorities($hash, $priorities)
	{
		$calls = array();
		// We need to construct a multicall to set all the priorities.
		// Better just do 100 at a time, rTorrent seems to not like doing too
		// many at once.
		foreach ($priorities as $file_id => $priority)
		{
			$calls[] = array(
				'methodName' => 'f.set_priority',
				'params' => array($hash, $file_id, $priority)
			);
			
			if (count($calls) == 100)
			{
				// If a call fails, just bail! TODO: Make this nicer
				if (false === $this->do_call('system.multicall', array($calls)))
					return false;
				$calls = array();
			}
		}
		
		// Better do any remaining calls
		if (count($calls) != 0)
			$this->do_call('system.multicall', array($calls));
			
		// And this tells rTorrent that they've changed (so it can update internally)
		$this->do_call('d.update_priorities', array($hash));
		return true;
	}
	
	/**
	 * Delete a torrent
	 */
	public function delete($hash)
	{
		// Better stop it first
		$this->stop($hash);
		$this->do_call('d.erase', array($hash));
	}
}
?>