<?php

namespace Booknetic_Mpdf\Tag;

use Booknetic_Mpdf\Strict;

use Booknetic_Mpdf\Cache;
use Booknetic_Mpdf\Color\ColorConverter;
use Booknetic_Mpdf\CssManager;
use Booknetic_Mpdf\Form;
use Booknetic_Mpdf\Image\ImageProcessor;
use Booknetic_Mpdf\Language\LanguageToFontInterface;
use Booknetic_Mpdf\Booknetic_Mpdf;
use Booknetic_Mpdf\Otl;
use Booknetic_Mpdf\SizeConverter;
use Booknetic_Mpdf\TableOfContents;

abstract class Tag
{

	use Strict;

	/**
	 * @var \Booknetic_Mpdf\Booknetic_Mpdf
	 */
	protected $mpdf;

	/**
	 * @var \Booknetic_Mpdf\Cache
	 */
	protected $cache;

	/**
	 * @var \Booknetic_Mpdf\CssManager
	 */
	protected $cssManager;

	/**
	 * @var \Booknetic_Mpdf\Form
	 */
	protected $form;

	/**
	 * @var \Booknetic_Mpdf\Otl
	 */
	protected $otl;

	/**
	 * @var \Booknetic_Mpdf\TableOfContents
	 */
	protected $tableOfContents;

	/**
	 * @var \Booknetic_Mpdf\SizeConverter
	 */
	protected $sizeConverter;

	/**
	 * @var \Booknetic_Mpdf\Color\ColorConverter
	 */
	protected $colorConverter;

	/**
	 * @var \Booknetic_Mpdf\Image\ImageProcessor
	 */
	protected $imageProcessor;

	/**
	 * @var \Booknetic_Mpdf\Language\LanguageToFontInterface
	 */
	protected $languageToFont;

	const ALIGN = [
		'left' => 'L',
		'center' => 'C',
		'right' => 'R',
		'top' => 'T',
		'text-top' => 'TT',
		'middle' => 'M',
		'baseline' => 'BS',
		'bottom' => 'B',
		'text-bottom' => 'TB',
		'justify' => 'J'
	];

	public function __construct(
		Booknetic_Mpdf $mpdf,
		Cache $cache,
		CssManager $cssManager,
		Form $form,
		Otl $otl,
		TableOfContents $tableOfContents,
		SizeConverter $sizeConverter,
		ColorConverter $colorConverter,
		ImageProcessor $imageProcessor,
		LanguageToFontInterface $languageToFont
	) {

		$this->mpdf = $mpdf;
		$this->cache = $cache;
		$this->cssManager = $cssManager;
		$this->form = $form;
		$this->otl = $otl;
		$this->tableOfContents = $tableOfContents;
		$this->sizeConverter = $sizeConverter;
		$this->colorConverter = $colorConverter;
		$this->imageProcessor = $imageProcessor;
		$this->languageToFont = $languageToFont;
	}

	public function getTagName()
	{
		$tag = get_class($this);
		return strtoupper(str_replace('Booknetic_Mpdf\Tag\\', '', $tag));
	}

	protected function getAlign($property)
	{
		$property = strtolower($property);
		return array_key_exists($property, self::ALIGN) ? self::ALIGN[$property] : '';
	}

	abstract public function open($attr, &$ahtml, &$ihtml);

	abstract public function close(&$ahtml, &$ihtml);

}
