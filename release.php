<?php
if (empty($argv[1])) {
	die("No name set");
}

sleep(1);

$name = $argv[1];

shell_exec("git add $name");
shell_exec("cp $name build/installer.phar");

