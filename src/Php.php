<?php

/**
 * Created by PhpStorm.
 * User: luky
 * Date: 22.6.16
 * Time: 8:15
 */
class Php
{
	const MSG_MISSING_EXTENSION = "Install missing extensions, script terminated";
	/** @var Bash */
	private $bash;
	
	/**
	 * Php constructor.
	 *
	 * @param Bash $bash
	 */
	public function __construct(Bash $bash)
	{
		$this->bash = $bash;
	}
	
	/**
	 * @param      $extension
	 * @param bool $die
	 *
	 * @return mixed
	 */
	public function checkExtension($extension, $die = true)
	{
		
		if (is_array($extension)) {
			$this->bash->title("PHP extensions");
			
			$should = false;
			
			foreach ($extension as $module) {
				
				$state = $this->checkExtension($module, false);
				
				if ($state == false) {
					$should = true;
				}
				
				print Bash::STAR . $module;
				
				Bash::space($module);
				
				$state ? Bash::ok('Installed') : Bash::error('Missing');
				print "\n";
			}
			
			if ($die && $should) {
				$this->bash->terminate(self::MSG_MISSING_EXTENSION);
			}
		} else {
			$should = extension_loaded($extension);
			
			if ($die && $should) {
				$this->bash->terminate(self::MSG_MISSING_EXTENSION);
			}
			
			return $should;
		}
	}
	
	public function version()
	{
		return PHP_VERSION_ID;
	}
	
	/**
	 * Check if version is lower than defined in parameter
	 *
	 * @param $version
	 *
	 * @return bool
	 */
	public function versionLower($version)
	{
		return $this->version() < $this->versionConvert($version);
	}
	
	/**
	 * Check if version is same or higher than defined in parameter
	 *
	 * @param $version
	 *
	 * @return bool
	 */
	public function versionSameOrHigher($version)
	{
		return $this->version() >= $this->versionConvert($version);
	}
	
	/**
	 * Convert 7.0.0 format to number format 70000 (PHP_VERSION_ID)
	 *
	 * @param $version
	 *
	 * @return int
	 */
	private function versionConvert($version)
	{
		if (is_numeric($version)) {
			return $version;
		} else {
			$ver = explode(".", $version);
			
			return (isset($ver[0]) ? $ver[0] : 0) * 10000 + (isset($ver[1]) ? $ver[1] : 0) * 100 + (isset($ver[2]) ? $ver[2] : 0);
		}
	}
	
}
