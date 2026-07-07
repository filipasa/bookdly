<?php

namespace Booknetic_Mpdf;

use Booknetic_Mpdf\Color\ColorConverter;
use Booknetic_Mpdf\Color\ColorModeConverter;
use Booknetic_Mpdf\Color\ColorSpaceRestrictor;

use Booknetic_Mpdf\Fonts\FontCache;
use Booknetic_Mpdf\Fonts\FontFileFinder;

use Booknetic_Mpdf\Image\ImageProcessor;

use Booknetic_Mpdf\Pdf\Protection;
use Booknetic_Mpdf\Pdf\Protection\UniqidGenerator;

use Booknetic_Mpdf\Writer\BaseWriter;
use Booknetic_Mpdf\Writer\BackgroundWriter;
use Booknetic_Mpdf\Writer\ColorWriter;
use Booknetic_Mpdf\Writer\BookmarkWriter;
use Booknetic_Mpdf\Writer\FontWriter;
use Booknetic_Mpdf\Writer\FormWriter;
use Booknetic_Mpdf\Writer\ImageWriter;
use Booknetic_Mpdf\Writer\JavaScriptWriter;
use Booknetic_Mpdf\Writer\MetadataWriter;
use Booknetic_Mpdf\Writer\OptionalContentWriter;
use Booknetic_Mpdf\Writer\PageWriter;

use Booknetic_Mpdf\Writer\ResourceWriter;
use Psr\Log\LoggerInterface;

class ServiceFactory
{

	public function getServices(
		Booknetic_Mpdf $mpdf,
		LoggerInterface $logger,
		$config,
		$restrictColorSpace,
		$languageToFont,
		$scriptToLanguage,
		$fontDescriptor,
		$bmp,
		$directWrite,
		$wmf
	) {
		$sizeConverter = new SizeConverter($mpdf->dpi, $mpdf->default_font_size, $mpdf, $logger);

		$colorModeConverter = new ColorModeConverter();
		$colorSpaceRestrictor = new ColorSpaceRestrictor(
			$mpdf,
			$colorModeConverter,
			$restrictColorSpace
		);
		$colorConverter = new ColorConverter($mpdf, $colorModeConverter, $colorSpaceRestrictor);

		$tableOfContents = new TableOfContents($mpdf, $sizeConverter);

		$cache = new Cache($config['tempDir']);
		$fontCache = new FontCache(new Cache($config['tempDir'] . '/ttfontdata'));

		$fontFileFinder = new FontFileFinder($config['fontDir']);

		$cssManager = new CssManager($mpdf, $cache, $sizeConverter, $colorConverter);

		$otl = new Otl($mpdf, $fontCache);

		$protection = new Protection(new UniqidGenerator());

		$writer = new BaseWriter($mpdf, $protection);

		$gradient = new Gradient($mpdf, $sizeConverter, $colorConverter, $writer);

		$formWriter = new FormWriter($mpdf, $writer);

		$form = new Form($mpdf, $otl, $colorConverter, $writer, $formWriter);

		$hyphenator = new Hyphenator($mpdf);

		$remoteContentFetcher = new RemoteContentFetcher($mpdf, $logger);

		$imageProcessor = new ImageProcessor(
			$mpdf,
			$otl,
			$cssManager,
			$sizeConverter,
			$colorConverter,
			$colorModeConverter,
			$cache,
			$languageToFont,
			$scriptToLanguage,
			$remoteContentFetcher,
			$logger
		);

		$tag = new Tag(
			$mpdf,
			$cache,
			$cssManager,
			$form,
			$otl,
			$tableOfContents,
			$sizeConverter,
			$colorConverter,
			$imageProcessor,
			$languageToFont
		);

		$fontWriter = new FontWriter($mpdf, $writer, $fontCache, $fontDescriptor);
		$metadataWriter = new MetadataWriter($mpdf, $writer, $form, $protection, $logger);
		$imageWriter = new ImageWriter($mpdf, $writer);
		$pageWriter = new PageWriter($mpdf, $form, $writer, $metadataWriter);
		$bookmarkWriter = new BookmarkWriter($mpdf, $writer);
		$optionalContentWriter = new OptionalContentWriter($mpdf, $writer);
		$colorWriter = new ColorWriter($mpdf, $writer);
		$backgroundWriter = new BackgroundWriter($mpdf, $writer);
		$javaScriptWriter = new JavaScriptWriter($mpdf, $writer);

		$resourceWriter = new ResourceWriter(
			$mpdf,
			$writer,
			$colorWriter,
			$fontWriter,
			$imageWriter,
			$formWriter,
			$optionalContentWriter,
			$backgroundWriter,
			$bookmarkWriter,
			$metadataWriter,
			$javaScriptWriter,
			$logger
		);

		return [
			'otl' => $otl,
			'bmp' => $bmp,
			'cache' => $cache,
			'cssManager' => $cssManager,
			'directWrite' => $directWrite,
			'fontCache' => $fontCache,
			'fontFileFinder' => $fontFileFinder,
			'form' => $form,
			'gradient' => $gradient,
			'tableOfContents' => $tableOfContents,
			'tag' => $tag,
			'wmf' => $wmf,
			'sizeConverter' => $sizeConverter,
			'colorConverter' => $colorConverter,
			'hyphenator' => $hyphenator,
			'remoteContentFetcher' => $remoteContentFetcher,
			'imageProcessor' => $imageProcessor,
			'protection' => $protection,

			'languageToFont' => $languageToFont,
			'scriptToLanguage' => $scriptToLanguage,

			'writer' => $writer,
			'fontWriter' => $fontWriter,
			'metadataWriter' => $metadataWriter,
			'imageWriter' => $imageWriter,
			'formWriter' => $formWriter,
			'pageWriter' => $pageWriter,
			'bookmarkWriter' => $bookmarkWriter,
			'optionalContentWriter' => $optionalContentWriter,
			'colorWriter' => $colorWriter,
			'backgroundWriter' => $backgroundWriter,
			'javaScriptWriter' => $javaScriptWriter,

			'resourceWriter' => $resourceWriter
		];
	}

}
