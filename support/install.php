<?php
	// Portable Apache, MySQL, PHP installer.
	// (C) 2012 CubicleSoft.  All Rights Reserved.

	$basepath = str_replace("\\", "/", dirname(dirname(__FILE__))) . "/";
	$basepath2 = $basepath;

	// Attempt to detect if everything is installed already.  If so, bail.
	if (file_exists($basepath . "php/php.ini"))
	{
		echo "The installer has already completed.  If you are upgrading, run 'upgrade.bat' instead.\n";

		sleep(2);
		exit();
	}

	$fp = fopen("php://stdin", "rb");

	echo "Welcome to the Portable Apache, MySQL, and PHP installer.\n\n";

	echo "Downloading software...\n";
	echo "(This will take a while, so go get some coffee or a snack)\n\n";
	require_once $basepath2 . "support/download.php";

	echo "\n";
	echo "-----------------------------------------------------------------\n";
	echo "\n";
	echo "You will now be asked a few questions about your desired setup.\n";
	echo "Press 'Enter' to accept default values.\n";
	echo "Press 'Ctrl-C' at any time to abort the install.\n";

	echo "\n";
	echo "-----\n";
	echo "Path mode can be either 'portable' or 'hardcoded'.\n";
	echo "'portable' strips the drive letter.  'hardcoded' keeps it.\n";
	echo "\n";
	echo "Hardcoded path:  " . $basepath . "\n";
	$portablepath = (substr($basepath, 1, 1) == ":" ? substr($basepath, 2) : $basepath);
	echo "Portable path:  " . $portablepath . "\n";
	echo "\n";
	do
	{
		echo "Path mode [portable]: ";
		$line = substr(trim(strtolower(fgets($fp))), 0, 1);
	} while ($line != "" && $line != "p" && $line != "h");
	if ($line != "h")  $basepath = $portablepath;

	echo "\n";
	echo "-----\n";
	echo "The document root is where you will store your web server files.\n";
	echo "For the most flexibility and to avoid upgrade issues,\n";
	echo "picking another location other than the default is a good idea.\n";
	echo "\n";
	echo "Document root path [" . $basepath . "apache/htdocs]: ";
	$apache_docroot = str_replace("\\", "/", trim(fgets($fp)));
	if (substr($apache_docroot, -1) == "/")  $apache_docroot = trim(substr($apache_docroot, 0, -1));
	if ($apache_docroot == "")  $apache_docroot = $basepath . "apache/htdocs";

	echo "\n";
	echo "-----\n";
	echo "The Apache root is where the main Apache server files reside.\n";
	echo "The default value is probably correct.\n";
	echo "\n";
	echo "Apache root path [" . $basepath . "apache]: ";
	$apache_root = str_replace("\\", "/", trim(fgets($fp)));
	if (substr($apache_root, -1) == "/")  $apache_root = trim(substr($apache_root, 0, -1));
	if ($apache_root == "")  $apache_root = $basepath . "apache";

	echo "\n";
	echo "-----\n";
	echo "The name of the INI file to copy to 'my.ini'.\n";
	echo "The default is fine for most development scenarios.\n";
	echo "\n";
	do
	{
		echo "MySQL config [my-default.ini]: ";
		$mysql_config = trim(fgets($fp));
	} while (!file_exists($basepath . "mysql/" . $mysql_config));
	if ($mysql_config == "")  $mysql_config = "my-default.ini";

	echo "\n";
	echo "-----\n";
	echo "The PHP configuration type to use from the distribution.\n";
	echo "\n";
	do
	{
		echo "PHP config (dev or prod) [dev]: ";
		$line = substr(trim(strtolower(fgets($fp))), 0, 1);
	} while ($line != "" && $line != "d" && $line != "p");
	$php_config = ($line == "" || $line == "d" ? "php.ini-development" : "php.ini-production");

	// All of the information has been gathered.  Perform install.
	echo "\n";
	echo "-----\n";
	echo "Configuring.  This should only take a moment to complete.\n";

	require_once $basepath2 . "support/base.php";

	// Apache.
	if (!is_dir($basepath2 . "apache/cgi-bin"))  CopyDirectory($basepath2 . "apache/orig-cgi-bin", $basepath2 . "apache/cgi-bin");
	if (!is_dir($basepath2 . "apache/conf"))
	{
		CopyDirectory($basepath2 . "apache/orig-conf", $basepath2 . "apache/conf");

		// Modify the Apache configuration so it can find itself.
		$data = file_get_contents($basepath2 . "apache/conf/httpd.conf");
		$lineend = (strpos($data, "\r\n") !== false ? "\r\n" : (strpos($data, "\n") !== false ? "\n" : "\r"));
		$data = str_replace(array("c:/Apache24/htdocs", "c:/Apache24", "DirectoryIndex index.html", "#ServerName www.example.com:80"), array($apache_docroot, $apache_root, "DirectoryIndex index.html index.php", "ServerName localhost"), $data);
		$pos = strpos($data, "LoadModule xml2enc_module modules/mod_xml2enc.so" . $lineend);
		if ($pos !== false)
		{
			$pos += strlen("LoadModule xml2enc_module modules/mod_xml2enc.so" . $lineend);
			$data2 = "LoadModule php5_module \"" . $basepath . "php/php5apache2_4.dll\"" . $lineend;
			$data2 .= "AddHandler application/x-httpd-php .php" . $lineend;
			$data2 .= $lineend;
			$data2 .= "PHPIniDir \"" . $basepath . "php\"" . $lineend;
			$data = substr($data, 0, $pos) . $data2 . substr($data, $pos);
		}
		file_put_contents($basepath2 . "apache/conf/httpd.conf", $data);
	}
	if (!is_dir($basepath2 . "apache/htdocs"))  CopyDirectory($basepath2 . "apache/orig-htdocs", $basepath2 . "apache/htdocs");
	if (!is_dir($basepath2 . "apache/logs"))  CopyDirectory($basepath2 . "apache/orig-logs", $basepath2 . "apache/logs");

	// MySQL.
	if (!is_dir($basepath2 . "mysql/data"))  CopyDirectory($basepath2 . "mysql/orig-data", $basepath2 . "mysql/data");
	if (!is_file($basepath2 . "mysql/my.ini"))  copy($basepath2 . "mysql/" . $mysql_config, $basepath2 . "mysql/my.ini");

	// PHP.
	if (!is_file($basepath2 . "php/php.ini"))  copy($basepath2 . "php/" . $php_config, $basepath2 . "php/php.ini");

	echo "Done.\n";
	sleep(3);
?>