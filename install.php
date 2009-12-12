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
		/* This heading will be centered. Spacing contains the number of spaces
		 * needed to push the heading to the centre.
		 */
		$head = 'rTorrentWeb ' . VERSION . ' Installer';
		$spacing = str_repeat(' ', floor((80 - strlen($head)) / 2));
		
		echo $spacing, $head, '
', $spacing, str_repeat('=', strlen($head)), '

Welcome to the rTorrentWeb installer! This installer will help you get
rTorrentWeb up and running. First, we\'ll check that your environment is
alright for running rTorrentWeb.
';
		$this->check_php_environment();
		$this->check_environment();
		echo 
'
--------------------------------------------------------------------------------
Now, just a few questions about how you want to install it. To accept the
default value (which is indicated in brackets, and should be fine for most
answers), simply press ENTER when prompted.

';
		$settings = $this->get_settings();
		$this->copy_core_files($settings['coredir']);
		$this->copy_web_files($settings['wwwdir']);
		
		// Create the directories we need
		@mkdir($settings['torrentdir'], 0755, true);
		@mkdir($settings['datadir'], 0777, true);
		@mkdir(dirname($settings['db']), 0755, true);
		// If they're root, we have to chown stuff to the web user
		if ($this->is_root)
		{
			chown($settings['coredir'] . 'application/cache/', $this->webuser);
			chown($settings['coredir'] . 'application/logs/', $this->webuser);
			chown(dirname($settings['db']), $this->webuser);
		}
		
		$this->write_config($settings);
		$this->create_db($settings['db']);
		
		echo '
================================================================================
rTorrentWeb installation is completed! You may access your new rTorrentWeb 
installation at:
', $settings['url'], '
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
			'The PHP POSIX functions are not enabled. These are REQUIRED by the installer!'
				=> !function_exists('posix_geteuid'),
			'The PHP XMLRPC extension is not installed. On a Debian based system, you may install it by typing "apt-get install php5-xmlrpc"'
				=> !function_exists('xmlrpc_encode_request'),
			'PHP PDO isn\'t installed'
				=> !class_exists('PDO'),
			'The PHP SQLite extension is not installed. On a Debian based system, you may install it by typing "apt-get install php5-sqlite"'
				=> !extension_loaded('pdo_sqlite'),
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
you abort installation now, fix these issues, and then retry installation.';
		self::continue_or_abort();
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
				'datadir'    => $homedir . '/rtorrentweb/',
				'url'        => 'http://' . Utils::get_ip() . '/~' . posix_getlogin() . '/rtorrentweb/'
			);
		}
		// Huzzah, root!
		else
		{
			$settings = array(
				'wwwdir'     => is_dir('/var/www/html') ? '/var/www/html/rtorrentweb/' : '/var/www/rtorrentweb/',
				// TODO: Is this FHS compliant?
				'coredir'    => '/usr/local/share/rtorrentweb/',
				'datadir'    => '/var/local/rtorrentweb/',
				'url'        => 'http://' . Utils::get_ip() . '/rtorrentweb/',
			);
			
		}
		
		$settings['wwwdir']     = Console::readLine('Web directory', $settings['wwwdir']);
		$settings['coredir']    = Console::readLine('rTorrentWeb directory', $settings['coredir']);
		$settings['datadir']    = Console::readLine('Data directory', $settings['datadir']);
		$settings['url']        = Console::readLine('URL to WWW dir', $settings['url']);

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
		
		// TODO: Maybe this shouldn't be hard-coded?
		$settings['db'] = $settings['datadir'] . 'db/rtorrent.db';
		
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
			'index.php.template',
			'res',
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
	 * Write configuration files for rTorrentWeb
	 */
	private function write_config($settings)
	{
		$appdir = $settings['coredir'] . 'application/';
		$configdir = $appdir . 'config/';
		$systemdir = $settings['coredir'] . 'system/';
		$modulesdir = $settings['coredir'] . 'modules/';
		
		echo 'Creating configuration files... ';
		// Here's our config stuff
		$config = array(
			'%site_domain%' => parse_url($settings['url'], PHP_URL_PATH),
			'%db_file%'     => $settings['db'],
			'%salt_pattern%'=> Utils::gen_salt_pattern(),
			'%appdir%'      => $appdir,
			'%modulesdir%'  => $modulesdir,
			'%systemdir%'   => $systemdir,
		);		
		
		// Split the config into two arrays - Keys, and values.
		$keys = array_keys($config);
		$values = array_values($config);
		
		// Here we go!
		$this->write_config_file($configdir . 'config.php', $keys, $values);
		$this->write_config_file($configdir . 'database.php', $keys, $values);
		$this->write_config_file($configdir . 'auth.php', $keys, $values);
		// Index file :o
		$this->write_config_file($settings['wwwdir'] . 'index.php', $keys, $values);
		echo "Done.\n";
	}
	
	/**
	 * Write a configuration file
	 */
	private function write_config_file($filename, $keys, $values)
	{
		if (file_exists($filename))
		{
			echo 'Not creating ', basename($filename), ", it already exists!\n";
			return;
		}
		
		$file = file_get_contents($filename . '.template');
		$file = str_replace($keys, $values, $file);
		file_put_contents($filename, $file);
	}
	
	/**
	 * Create the database
	 */
	private function create_db($filename)
	{
		// Does the database already exist?
		if (is_file($filename))
		{
			echo "Not creating database, it already exists!\n";
			return;
		}
		echo 'Creating database... ';
		/* TODO: Should this use Kohana's database functionality? It seems 
		 * kinda silly repeating Kohana's stuff here. We could use the "sqlite3"
		 * command-line utility, but it's not guaranteed to be the same version
		 * as in PHP, so might not be compatible =[
		 */
		try
		{
			$db = new PDO('sqlite:' . $filename);
		}
		catch (PDOException $ex)
		{
			echo 'ERROR creating database: ' . $ex->getMessage() . "\n";
			echo "Installation aborted. \n";
			exit(2);
		}
		
		$query = '';
		// Read the database file
		foreach (file('rtorrent.sql') as $line)
		{
			// Skip it if it's a comment
			if (substr($line, 0, 2) == '--' || $line == '')
				continue;
				
			// Add this line to the current segment
			$query .= $line;
			// If it has a semicolon at the end, it's the end of the query
			if (substr(trim($line), -1, 1) == ';')
			{
				// Perform the query. Did it not work? :(
				if ($db->exec($query) === false)
				{
					$error = $db->errorInfo();
					echo 'Error: ' . $error[2] . "\n";
				}
				$query = '';
			}
		}
		
		echo "Done.\n";
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

/**
 * General utilities
 */
class Utils
{
	/**
	 * Get the IP of the local host
	 */
	public static function get_ip()
	{
		// TODO: This is messy and might not work all the time :(
		return trim(`/sbin/ifconfig | grep 'inet addr:'| grep -v '127.0.0.1' | cut -d: -f2 | awk '{print $1}'`);
	}
	
	/**
	 * Make a salt offset, as defined at http://docs.kohanaphp.com/addons/auth#configuration
	 */
	public static function gen_salt_pattern()
	{
		// Make a salt pattern
		//1, 3, 5, 9, 14, 15, 20, 21, 28, 30
		$salt_pattern = array();
		$curr_pos = mt_rand(1, 4);
		$nums = 0;
		do
		{
			$salt_pattern[] = $curr_pos;
			$curr_pos += mt_rand(1, 8);
		} while ($curr_pos < 40 && $nums < 20);
		
		return implode(', ', $salt_pattern);
	}
}
?>