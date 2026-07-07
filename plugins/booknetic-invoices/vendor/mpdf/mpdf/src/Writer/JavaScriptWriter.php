<?php

namespace Booknetic_Mpdf\Writer;

use Booknetic_Mpdf\Strict;
use Booknetic_Mpdf\Booknetic_Mpdf;

final class JavaScriptWriter
{

	use Strict;

	/**
	 * @var \Booknetic_Mpdf\Booknetic_Mpdf
	 */
	private $mpdf;

	/**
	 * @var \Booknetic_Mpdf\Writer\BaseWriter
	 */
	private $writer;

	public function __construct(Booknetic_Mpdf $mpdf, BaseWriter $writer)
	{
		$this->mpdf = $mpdf;
		$this->writer = $writer;
	}

	public function writeJavascript() // _putjavascript
	{
		$this->writer->object();
		$this->mpdf->n_js = $this->mpdf->n;
		$this->writer->write('<<');
		$this->writer->write('/Names [(EmbeddedJS) ' . (1 + $this->mpdf->n) . ' 0 R ]');
		$this->writer->write('>>');
		$this->writer->write('endobj');

		$this->writer->object();
		$this->writer->write('<<');
		$this->writer->write('/S /JavaScript');
		$this->writer->write('/JS ' . $this->writer->string($this->mpdf->js));
		$this->writer->write('>>');
		$this->writer->write('endobj');
	}

}
