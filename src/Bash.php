<?php

/**
 * Created by PhpStorm.
 * User: luky
 * Date: 22.6.16
 * Time: 8:15
 */
class Bash
{
	const USER = "www-data";
	const GROUP = "www-data";
	
	const DONE = "\033[0;32mDone\033[0m\n";
	const ERROR = "\033[0;31mError\033[0m\n";
	const STAR = " \e[0;36m*\e[0m ";
	
	const SPACE_SIZE = 50;
	
	private $user = null;
	private $group = null;
	
	private $arguments = [];
	private $options = [
		self::SKIP_CONFIRM => false,
		self::USE_SUDO => true,
		self::COLORLESS => false,
		self::DEVELOPMENT => false,
	];
	
	const SKIP_CONFIRM = 'skip_confirm';
	const USE_SUDO = 'use_sudo';
	const COLORLESS = 'colorless';
	
	const DEVELOPMENT = 'development';
	
	public function __construct($argv)
	{
		$this->parseArguments($argv);
	}
	
	private function parseArguments($argv)
	{
		foreach ($argv as $argument) {
			if (substr($argument, 0, 2) == "--") {
				$x = explode("=", substr($argument, 2));
				
				$this->arguments[$x[0]] = $x[1];
			} elseif (substr($argument, 0, 1) == "-") {
				$cmd = substr($argument, 1);
				
				if ($cmd == "s") {
					$this->options[self::SKIP_CONFIRM] = true;
				} elseif ($cmd == "a") {
					$this->options[self::USE_SUDO] = false;
				} elseif ($cmd == "c") {
					$this->options[self::COLORLESS] = true;
				} elseif ($cmd == "d") {
					$this->options[self::DEVELOPMENT] = true;
				}
			}
		}
	}
	
	public function getOption($name = false)
	{
		if ($name) {
			if (isset($this->options[$name])) {
				return $this->options[$name];
			}
		} else {
			return $this->options;
		}
	}
	
	public function showArguments()
	{
		Bash::info("\nArguments:\n");
		
		foreach ($this->arguments as $key => $value) {
			print self::STAR . $key . " ";
			
			self::space($key);
			
			print "$value\n";
		}
		print "\n";
		print "\n";
		
		Bash::info("\nOptions:\n");
		
		foreach ($this->options as $key => $value) {
			print self::STAR . $key . " ";
			
			self::space($key);
			
			print ($value ? 'TRUE' : 'FALSE');
			print "\n";
		}
		print "\n";
	}
	
	function mkdir($path)
	{
		$this->step("mkdir $path -m 775", $path);
	}
	
	/**
	 * @return null
	 */
	public function getUser()
	{
		if ($this->user == null) {
			$this->user = $this->getParameter("user", self::USER);
		}
		
		return $this->user;
	}
	
	/**
	 * @param null $user
	 */
	public function setUser($user)
	{
		$this->user = $user;
	}
	
	/**
	 * @return null
	 */
	public function getGroup()
	{
		if ($this->group == null) {
			$this->group = $this->getParameter("group", self::GROUP);
		}
		
		return $this->group;
	}
	
	/**
	 * @param null $group
	 */
	public function setGroup($group)
	{
		$this->group = $group;
	}
	
	function grantAccess($name)
	{
		$sudo = "";
		if ($this->options[self::USE_SUDO]) {
			$sudo = "sudo ";
		}
		print $this->step("$sudo chown " . $this->getUser() . ":" . $this->getGroup() . " $name -Rf && $sudo chmod 777 $name -Rf", $name);
	}
	
	function title($msg)
	{
		self::normal("\n" . $msg . "...\n");
	}
	
	function msg($msg, $enter = true)
	{
		print $msg . ($enter ? "\n" : '');
	}
	
	function wget($url)
	{
		$this->step("wget $url", "wget --> $url");
	}
	
	function symlink($from, $to)
	{
		$this->step("ln -s $from $to", "symlink $to --> $from");
	}
	
	function run($command, $msg = null)
	{
		print $msg;
		$output = shell_exec($command . " 2>&1");
		print self::DONE;
		
		return $output;
	}
	
	function step($command, $msg)
	{
		print self::STAR;
		print $msg;
		$output = shell_exec($command . " 2>&1");
		
		self::space($msg);
		print self::DONE;
		
		return $output;
	}
	
	/**
	 * @internal
	 *
	 * @param null $name
	 * @param null $default
	 * @param bool $required
	 *
	 * @return null
	 */
	function readline($name = null, $default = null, $required = false)
	{
		
		$val = readline($default ? "Select `$name` [$default]: " : "Select `$name`: ");
		
		if ($required && !$val) {
			return $this->readline($name, $default, $required);
		}
		
		return $val ?: $default;
	}
	
	public function getParameter($name, $default = null, $required = false)
	{
		if (isset($this->arguments[$name])) {
			print "Select `$name` -> '{$this->arguments[$name]}'\n";
			
			return $this->arguments[$name];
		} else {
			return $this->readline($name, $default, $required);
		}
	}
	
	function confirm($msg, $strict = true, $skipValue = true)
	{
		if ($this->options[self::SKIP_CONFIRM]) {
			Bash::quest("$msg [Y/n]: SKIP MODE ENABLED use " . ($skipValue ? "Y" : 'N') . "\n");
			
			return $skipValue;
		}
		
		$val = strtolower(readline(Bash::quest("$msg [Y/n]: ")));
		
		if ($strict) {
			
			if ($val != "y" && $val != "n") {
				return $this->confirm($msg, $strict);
			}
		}
		
		return $val == "y";
	}
	
	public function requirePackage($package)
	{
		if ($this->checkPackage($package) == false) {
			$this->msg(self::error("\n[!] Missing `$package` package, install $package to continue"));
			$this->msg(self::quest("\n  sudo apt-get install $package"));
			$this->terminate("Script terminated");
		}
	}
	
	function checkPackage($name)
	{
		$state = $this->checkPackageWhich($name) || $this->checkPackageDpgk($name);
		
		print self::STAR . $name;
		Bash::space($name);
		print $state ? Bash::ok('Installed') : Bash::error('Missing');
		print "\n";
		
		return $state;
	}
	
	public function requireNpmPackage($package)
	{
		if ($this->checkNpmPackage($package) == false) {
			$this->msg(self::error("\n[!] Missing npm package `$package`, install $package to continue"));
			$this->msg(self::quest("\n  sudo npm install -g $package"));
			$this->terminate("Script terminated");
		}
	}
	
	function checkNpmPackage($name)
	{
		exec("npm list -g $name 2>&1", $output, $code);
		
		$state = $code === 0;
		
		print self::STAR . "npm -> $name";
		Bash::space("npm -> $name");
		print $state ? Bash::ok('Installed') : Bash::error('Missing');
		print "\n";
		
		return $state;
	}
	
	function terminate($msg, $state = 0)
	{
		if ($state == 0) {
			$this->msg("\n\e[0;31m[!] $msg\e[0m\n");
		} else {
			$this->msg("\n\e[0;33m[!] $msg\e[0m\n");
		}
		
		$this->border("Install END");
		die;
	}
	
	public static function error($msg)
	{
		print "\033[0;31m" . $msg . "\033[0m";
	}
	
	public static function ok($msg)
	{
		print "\033[0;32m" . $msg . "\033[0m";
	}
	
	public static function warn($msg)
	{
		print "\033[0;33m" . $msg . "\033[0m";
	}
	
	public static function quest($msg)
	{
		print "\033[0;36m" . $msg . "\033[0m";
	}
	
	public static function info($msg)
	{
		print "\033[0;34m" . $msg . "\033[0m";
	}
	
	public static function normal($msg)
	{
		print "\033[0;30m" . $msg . "\033[0m";
	}
	
	public static function space($string)
	{
		print str_repeat(" ", self::SPACE_SIZE - strlen($string));
	}
	
	function border($msg)
	{
		$this->msg("-------------------------- \e[0;36m$msg\e[0m ------------------------------");
	}
	
	/**
	 * @param $name
	 *
	 * @return bool
	 */
	private function checkPackageWhich($name)
	{
		exec("which $name", $output);
		$output = trim(implode("", $output));
		
		return !empty($output);
	}
	
	/**
	 * @param $name
	 *
	 * @return bool
	 */
	private function checkPackageDpgk($name)
	{
		$output = shell_exec("dpkg -s $name 2>&1");
		
		return strpos($output, "install ok installed") !== false;
	}
	
}
