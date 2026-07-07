<?php

namespace Booknetic_Mpdf\Fonts;

class FontFileFinder
{

	private $directories;

	public function __construct($directories)
	{
		$this->setDirectories($directories);
	}

	public function setDirectories($directories)
	{
		if (!is_array($directories)) {
			$directories = [$directories];
		}

		$this->directories = $directories;
	}

	public function findFontFile($name)
	{
		foreach ($this->directories as $directory) {
			$filename = $directory . '/' . $name;
			if (file_exists($filename)) {
				return $filename;
			}
		}

		throw new \Booknetic_Mpdf\Booknetic_MpdfException(sprintf('Cannot find TTF TrueType font file "%s" in configured font directories.', $name));
	}
}
