#!/usr/bin/env php
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
 
// Not meant to access this via the web
if (isset($_SERVER['HTTP_HOST']))
	die('Error: This is a command-line script. Please run it in a shell.');

/**
 * rTorrentWeb version information
 * TDOO: install.php and index.php should read this from the same place
 */
define('VERSION', '0.1 Alpha');
// Let's get this party started... :D
chdir(dirname(__FILE__));
error_reporting(E_ALL);
$installer = new Installer;
$installer->run();

/**
 * Installer for rTorrentWeb
 */
class Installer
{
	// Are we root?
	private $is_root = false;
	// The user the web server runs under
	private $webuser = null;
	
	/**
	 * Run the installer! :D
	 */
	public function run()
	{
		echo ' === rTorrentWeb ', VERSION, " Installer ===\n";
		$this->check_php_environment();
		$this->check_environment();
		$settings = $this->get_settings();
		$this->copy_web_files($settings['wwwdir']);
		$this->copy_core_files($settings['coredir']);
		
		// Create the directories we need
		@mkdir($settings['torrentdir'], 0755, true);
		@mkdir($settings['metadir'], 0755, true);
		// If they're root, we have to chown metadir to the web user
		if ($this->is_root)
			chown($settings['metadir'], $this->webuser);
		
		echo '
================================================================================
The first stage of the rTorrentWeb installation is complete. To continue the
installation, please point your web browser to:
[todo: install URL]
';
	}
	
	/**
	 * Check that the PHP installation meets all prerequisites
	 */
	private function check_php_environment()
	{
		echo 'Checking PHP environment... ';
		$checks = array(
			// Kohana checks
			// TODO: Do we just need 5.2, or something newer (eg. 5.2.3)
			'Your PHP version (' . PHP_VERSION . ') is too old. rTorrentWeb requires PHP 5.2 or higher.' 
				=> !version_compare(PHP_VERSION, '5.2', '>='),
			'PHP PCRE support is missing.'
				=> !function_exists('preg_match'),
			'PHP PCRE has not been compiled with UTF-8 support.'
				=> !@preg_match('/^.$/u', 'ñ'),
			'PHP PCRE has not been compiled with Unicode property support.'
				=> !@preg_match('/^\pL$/u', 'ñ'),
			'PHP reflection is either not loaded or not compiled in.'
				=> !class_exists('ReflectionClass'),
			'The PHP filter extension is either not loaded or not compiled in.'
				=> !function_exists('filter_list'),
			'The PHP iconv extension is not loaded.'
				=> !extension_loaded('iconv'),
			'PHP SPL is not enabled.'
				=> !function_exists('spl_autoload_register'),
			
			// rTorrentWeb checks
			'The PHP XMLRPC extension is not installed. On a Debian based system, you may install it by typing "apt-get install php5-xmlrpc"'
				=> !function_exists('xmlrpc_encode_request'),
			'PDO SQLite isn\'t installed'
				=> !class_exists('PDO'),
			
		);
		
		// Did none of the checks fail?
		if (!in_array(true, $checks))
		{
			echo "Everything looks OK.\n";
			return true;
		}
		
		echo "\nSome errors were encountered, which may cause rTorrentWeb to not work properly:\n";
		foreach ($checks as $name => $failed)
		{
			// Skip the checks that actually passed
			if (!$failed)
				continue;
				
			echo ' - ' . $name . "\n";
		}
		echo '
Do you still want to try installation? It might fail, so it is recommended that
you fix these issues before installation';
		$this->continue_or_abort();
	}
	
	/**
	 * Check system prerequisites
	 */
	private function check_environment()
	{
		echo 'Checking environment... ';
		// Are we GOD?
		$this->is_root = (posix_geteuid() == 0 || posix_getlogin() == 'root');
		// If they're not root :(, show a warning.
		if (!$this->is_root)
		{
			echo '
You are not running this installer as the root user. rTorrentWeb will be
installed to your home directory. To install rTorrentWeb in a proper location,
please re-run this script as root.
';
			self::continue_or_abort();
		}
		
		echo "Everything looks OK.\n";
	}
	
	/**
	 * Ask the user for stuff
	 */
	private function get_settings()
	{
		$settings = array();
		// Let's set some defaults
		// Not running as root?
		if (!$this->is_root)
		{
			// TODO: This just feels ugly. :(
			$homedir = `echo -n ~`;
			$settings = array(
				'wwwdir'     => $homedir . '/public_html/rtorrentweb/',
				'coredir'    => $homedir . '/rtorrentweb/',
				'torrentdir' => $homedir . '/rtorrentweb/torrents/',
				'metadir'    => $homedir . '/rtorrentweb/metadata/',
			);
		}
		// Hozzah, root!
		else
		{
			$settings = array(
				'wwwdir'     => is_dir('/var/www/html') ? '/var/www/html/rtorrentweb/' : '/var/www/rtorrentweb/',
				// TODO: Is this FHS compliant?
				'coredir'    => '/usr/local/share/rtorrentweb/',
				'torrentdir' => '/var/local/rtorrentweb/torrents/',
				'metadir'    => '/var/local/rtorrentweb/metadata/',
			);
			
		}
		
		echo "To accept the default value, press ENTER when prompted\n";
		$settings['wwwdir']     = Console::readLine('WWW directory', $settings['wwwdir']);
		$settings['coredir']    = Console::readLine('rTorrentWeb directory', $settings['coredir']);
		$settings['torrentdir'] = Console::readLine('Torrent directory', $settings['torrentdir']);
		$settings['metadir']    = Console::readLine('Metadata directory', $settings['metadir']);
		$this->webuser = null;
		// If we are root, we also can chown to a web server user, so we have to
		// know that. Let's try find it.
		if ($this->is_root)
		{			
			// Let's try guess based on a list of common usernames.
			$possible_users = array('www-data', 'nobody');
			foreach ($possible_users as $user)
			{
				if (posix_getpwnam($user))
				{
					$this->webuser = $user;
					break;
				}
			}
			
			// And verify it from the user.
			$this->webuser = Console::readLine('Web server username', $this->webuser);			
		}
		
		echo "\n";
		return $settings;
	}
	
	/**
	 * Copy required files to the web directory
	 */
	private function copy_web_files($dest, $owner = null)
	{
		echo 'Copying web files... ';
		$this->copy_files(array(
			'index.php',
			'res',
			// TODO: Is this necessary?
			'rtorrent.sql',
		), $dest);
		echo "Done. \n";
	}
	
	/**
	 * Copy required files to the rTorrent directory
	 */
	private function copy_core_files($dest)
	{
		echo 'Copying core files... ';
		$this->copy_files(array(
			'application',
			'modules',
			'system',
			'COPYING',
			'Kohana License.html'
		), $dest);
		echo "Done. \n";
	}
	
	/**
	 * Copy files or directories from current directory to a destination
	 */
	private function copy_files(array $files, $dest)
	{
		// First we have to make the directory
		@mkdir($dest, 0777, true);
		
		// So let's go through these
		foreach ($files as $file)
		{
			// Does it not exist? O_o
			if (!file_exists($file))
			{
				echo 'ERROR: Source file or directory "' . $file . "\" does not exist!\n";
				continue;
			}
			
			// This is a bit messy, but it works :P
			passthru('cp -r ' . escapeshellarg($file) . ' ' . escapeshellarg($dest));
		}
	}
	
	/**
	 * Asks the user if they want to continue installation, or abort.
	 */
	private static function continue_or_abort()
	{
		echo '
 - Type Y and press ENTER to continue installation
 - Type N and press ENTER to abort installation 
';
		if (strtoupper(Console::readKey()) != 'Y')
		{
			echo "Installation aborted. \n";
			exit(1);
		}
		return true;
	}
}

/**
 * Console helper. 
 */
class Console
{
	/**
	 * Get a single character from the input
	 */
	public static function readKey()
	{
		return trim(fgets(STDIN, 128));
	}
	
	/**
	 * Ask the user a question
	 */
	public static function readLine($prompt, $default = null)
	{
		echo $prompt;
		// Do we have a default?
		if ($default != null)
			echo ' [' . $default . ']';
		
		echo '? ';
		$response = trim(fgets(STDIN, 128));
		return ($response == '') ? $default : $response;
			
	}
}
?>