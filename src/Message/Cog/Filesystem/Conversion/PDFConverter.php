<?php

namespace Message\Cog\Filesystem\Conversion;

use InvalidArgumentException;

use fpdf\FPDF;
use fpdi\FPDI;
use Knp\Snappy\Pdf;
use Message\Cog\Filesystem\File;

class PDFConverter extends AbstractConverter implements CombinerInterface {

	/**
	 * {@inheritDoc}
	 */
	public function generate($path, $html)
	{
		$ext = pathinfo($path, PATHINFO_EXTENSION);

		if (null === $ext) {
			$path .= ".ext";
		}
		elseif ("pdf" !== $ext) {
			throw new InvalidArgumentException(sprintf("Your destination path must have a .pdf extension for conversion, '%s' passed.", $ext));
		}

		$pdf = new Pdf;
		$pdf->setBinary($this->_getBinDir() . 'wkhtmltopdf-' . $this->_getBinaryType());

		foreach ($this->_options as $key => $value) {
			$pdf->setOption($key, $value);
		}

		if (is_file($path)) {
			unlink($path);
		}

		// Strip out `@font-face{ ... }` style tags as these break the pdf
		$html = preg_replace('/@font-face{([^}]*)}/ms', '', $html);


		// Remove wrapping `@media print { ... }` tag and braces. Print styles
		// should be applied to PDFs but the media query will not be
		// recognised.
		$mediaTag = '@media print {';

		// While there is a print media query found, get it's position
		while (false !== $mediaPos = strpos($html, $mediaTag)) {

			// We start with 1 opening pair belonging to the media query
			$openPairs = 1;

			// Loop each character after the media query tag, checking for
			// braces and counting how many open pairs we currently have.
			$chars = str_split($html);
			for ($i = $mediaPos + strlen($mediaTag); $i < strlen($html); $i++) {
				if ('{' == $chars[$i]) {
					$openPairs++;
				}
				elseif ('}' == $chars[$i]) {
					$openPairs--;
				}

				// If we have closed the media query brace pair, remove the
				// tag and closing brace from the characters and implode these
				// back into the html, ready to be checked again.
				if (0 === $openPairs) {
					unset($chars[$i]);
					for ($j = $mediaPos; $j < $mediaPos + strlen($mediaTag); $j++) {
						unset($chars[$j]);
					}
					$html = implode($chars);
					break;
				}
			}
		}

		$pdf->generateFromHTML($html, $path);

		return new File($path);
	}

	/**
	 * {@inheritDoc}
	 */
	public function combine($path, array $files = null)
	{
		$files = is_array($files) ? $files : array_slice(func_get_args(), 1);

		if (! $files or 0 === count($files)) {
			return false;
		}

		if (1 === count($files)) {
			return $files[0];
		}

		$combined = new FPDI;

		foreach ($files as $file) {
			if (! $file instanceof File) {
				$file = new File($file);
			}

			// Get each page of each file
			$pageCount = $combined->setSourceFile($file->getRealPath());
			for ($i = 1; $i <= $pageCount; $i++) {
				$page = $combined->importPage($i);
				$size = $combined->getTemplateSize($page);
				$combined->AddPage($size['w'] > $size['h'] ? 'L' : 'P', array($size['w'], $size['h']));
				$combined->useTemplate($page);
			}
		}

		$dest = new File($path);

		$combined->Output($dest->getRealPath(), 'F');

		return $dest;
	}

}