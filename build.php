<?php
const NAME = "installer";
const BUILD_PATH = "build/";

if (empty($argv[1])) {
	die("No version set");
}

$version = $argv[1];

$name = BUILD_PATH . NAME . "-" . $version . ".phar";

if (file_exists($name)) {
	die("Version $version already exists");
}

$phar = new Phar($name, FilesystemIterator::CURRENT_AS_FILEINFO | FilesystemIterator::KEY_AS_FILENAME, NAME . ".phar");

$phar->buildFromDirectory("src/", '/.php$/');

shell_exec("rm build/installer.phar");
shell_exec("php release.php $name");


