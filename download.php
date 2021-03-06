<?php
	$password = htmlspecialchars($_POST["Password"], ENT_QUOTES);
	$directory = htmlspecialchars($_POST["Select"], ENT_QUOTES);
	require "local.php";
	if ($password != $admin_password)
	{
		header("HTTP/1.1 403 Forbidden");
		die("Wrong Password! ");
	}
	// Validate the target path
	$workDirectory = $upload_directory . $directory . "/";
	if (!is_dir($workDirectory))
	{
		header("HTTP/1.1 400 Bad Request");
		die("Not directory: " . $directory);
	}
	// Zip the homework files
	$zipPath = tempnam(sys_get_temp_dir(), $directory);
	$zip = new ZipArchive;
	if ($zip->open($zipPath, ZipArchive::CREATE) !== TRUE)
	{
		header("HTTP/1.1 500 Internal Server Error");
		die("Fail zip: ZipArchive failure! ");
	}
	require "sql.php";
	$studentList = selectInMysql("students", array());
	$submitted = array_fill(0, count($studentList), false);
	foreach (scandir($workDirectory) as $fileName)
	{
		if ($fileName != "." && $fileName!= ".." && !is_dir($workDirectory . $fileName))
		{
			$zip->addFile($workDirectory . $fileName, $fileName);
			foreach ($studentList as $index => $student)
			{
				if (strpos($fileName, $student["number"] . "-" . $student["name"]) === 0)
				{
					$submitted[$index] = true;
				}
			}
		}
	}
	$tmpFilename = tempnam(sys_get_temp_dir(), "HOSS");
	foreach ($studentList as $index => $student)
	{
		if (!$submitted[$index])
		{
			file_put_contents($tmpFilename, $student["number"] . "," . $student["name"] . "\n", FILE_APPEND);
		}
	}
	$zip->addFile($tmpFilename, "missing.csv");
	$zip->close();
	// Output the zip file
	header("Cache-Control: no-store");
	header("Content-Description: File Transfer");
	header("Content-Disposition: attachment; filename=" . $directory . ".zip");
	header("Content-Length: " . filesize($zipPath));
	header("Content-Transfer-Encoding: binary");
	header("Content-Type: application/zip");
	readfile($zipPath);
	unlink($zipPath);
	die();
?>
