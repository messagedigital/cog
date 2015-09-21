<?php

namespace Message\Cog\Test\Security;

use Message\Cog\Security\StringGenerator;

use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamWrapper;
use org\bovigo\vfs\vfsStreamDirectory;
use org\bovigo\vfs\visitor\vfsStreamStructureVisitor;

class StringGeneratorTest extends \PHPUnit_Framework_TestCase
{
	/**
	 * @var StringGenerator
	 */
	protected $_stringGenerator;

	protected $_badHash;
	protected $_length;

	public function setUp()
	{
		$this->_stringGenerator = new StringGenerator;
		$this->_length          = StringGenerator::DEFAULT_LENGTH;
	}

	public function testAllGenerateMethodsRespectLength()
	{
		for ($i = 1; $i < 200; ++$i) {
			$string1 = $this->_stringGenerator->generate(10);
			$string2 = $this->_stringGenerator->generate(10);
			$this->assertSame(10, strlen($string1));
			$this->assertNotEquals($string1, $string2);
		}
	}

	public function testDefaultLengthUsed()
	{
		for ($i = 1; $i < 200; ++$i) {
			$string1 = $this->_stringGenerator->generate();
			$string2 = $this->_stringGenerator->generate();
			$this->assertSame($this->_length, strlen($string1));
			$this->assertNotEquals($string1, $string2);
		}
	}

	public function testGenerateReturnValuesFormat()
	{
		for ($i = 1; $i < 200; ++$i) {
			// for each, check the results are strings and match the regex [./0-9A-Za-z]
			$this->assertRegExp("/[A-Za-z0-9\/\\.']/", $this->_stringGenerator->generate($this->_length));
		}
	}

	/**
	 * @expectedException        \RuntimeException
	 * @expectedExceptionMessage Unable to read
	 */
	public function testGenerateUnixRandomThrowsExceptionWhenRandomNotFound()
	{
		vfsStream::setup('root');
		vfsStream::newDirectory('dev')
			->at(vfsStreamWrapper::getRoot());

		$this->_stringGenerator->generateFromUnixRandom(10, vfsStream::url('root') . '/dev/urandom');
	}

	/**
	 * @expectedException        \RuntimeException
	 * @expectedExceptionMessage Unable to read
	 */
	public function testGenerateUnixRandomThrowsExceptionWhenRandomNotReadable()
	{
		vfsStream::setup('root');
		vfsStream::newDirectory('dev')
			->at(vfsStreamWrapper::getRoot());
		vfsStream::newFile('urandom', 0000)
			->at(vfsStreamWrapper::getRoot()->getChild('dev'));

		$this->_stringGenerator->generateFromUnixRandom(10, vfsStream::url('root') . '/dev/urandom');
	}

	/**
	 * @expectedException        \RuntimeException
	 * @expectedExceptionMessage returned an empty value
	 */
	public function testGenerateUnixRandomThrowsExceptionWhenRandomEmpty()
	{
		vfsStream::setup('root');
		vfsStream::newDirectory('dev')
			->at(vfsStreamWrapper::getRoot());
		vfsStream::newFile('urandom')
			->at(vfsStreamWrapper::getRoot()->getChild('dev'));

		$this->_stringGenerator->generateFromUnixRandom(10, vfsStream::url('root') . '/dev/urandom');
	}

	public function testGenerateOpenSSLThrowsExceptionWhenFunctionDoesNotExist()
	{
		if (function_exists('openssl_random_pseudo_bytes')) {
			$this->assertTrue(true);
		} else {
			try {
				$this->_stringGenerator->generateFromOpenSSL();
			} catch (\RuntimeException $e) {
				$this->assertTrue(true);
			}

			$this->fail('RuntimeException not thrown');
		}
	}

	public function testGenerateFromUnixRandomNoLengthSet()
	{
		for ($i = 1; $i < 200; ++$i) {
			$string1 = $this->_stringGenerator->generateFromUnixRandom();
			$string2 = $this->_stringGenerator->generateFromUnixRandom();
			$this->assertSame($this->_length, strlen($string1));
			$this->assertSame($this->_length, strlen($string2));

			$this->assertNotEquals($string1, $string2);
			$this->assertRegExp('/^[A-Za-z0-9\.\/]{' . $this->_length . '}$/', $string1);
			$this->assertRegExp('/^[A-Za-z0-9\.\/]{' . $this->_length . '}$/', $string2);
		}
	}

	public function testGenerateFromUnixRandomShortLength()
	{
		for ($i = 1; $i < 200; ++$i) {
			$string1 = $this->_stringGenerator->generateFromUnixRandom(5);
			$string2 = $this->_stringGenerator->generateFromUnixRandom(5);
			$this->assertSame(5, strlen($string1));

			$this->assertNotEquals($string1, $string2);
			$this->assertRegExp('/^[A-Za-z0-9\.\/]{5}$/', $string1);
		}
	}

	public function testGenerateFromUnixRandomLongLength()
	{
		for ($i = 1; $i < 200; ++$i) {
			$string1 = $this->_stringGenerator->generateFromUnixRandom(100);
			$string2 = $this->_stringGenerator->generateFromUnixRandom(100);
			$this->assertSame(100, strlen($string1));

			$this->assertNotEquals($string1, $string2);
			$this->assertRegExp('/^[A-Za-z0-9\.\/]{100}$/', $string1);
		}
	}

//	public function testGenerateFromUnixRandomWithRegex()
//	{
//		for ($i = 1; $i < 200; ++$i) {
//			$this->_stringGenerator->setPattern('/^[A-Z]+$/');
//			$string = $this->_stringGenerator->generateFromUnixRandom();
//			$this->assertRegExp('/^[A-Z]{' . $this->_length . '}$/', $string);
//		}
//	}

	public function testGenerateFromOpenSSLNoLengthSet()
	{
		if (function_exists('openssl_random_pseudo_bytes')) {
			for ($i = 1; $i < 200; ++$i) {
				$string1 = $this->_stringGenerator->generateFromOpenSSL();
				$string2 = $this->_stringGenerator->generateFromOpenSSL();
				$this->assertSame($this->_length, strlen($string1));

				$this->assertNotEquals($string1, $string2);
				$this->assertRegExp('/^[A-Za-z0-9\.\/]{' . $this->_length . '}$/', $string1);
			}
		} else {
			$this->assertTrue(true);
		}
	}

	public function testGenerateFromOpenSSLShortLength()
	{
		if (function_exists('openssl_random_pseudo_bytes')) {
			for ($i = 1; $i < 200; ++$i) {
				$string1 = $this->_stringGenerator->generateFromOpenSSL(5);
				$string2 = $this->_stringGenerator->generateFromOpenSSL(5);
				$this->assertSame(5, strlen($string1));

				$this->assertNotEquals($string1, $string2);
				$this->assertRegExp('/^[A-Za-z0-9\.\/]{5}$/', $string1);
			}
		} else {
			$this->assertTrue(true);
		}
	}

	public function testGenerateFromOpenSSLLongLength()
	{
		if (function_exists('openssl_random_pseudo_bytes')) {
			for ($i = 1; $i < 200; ++$i) {
				$string1 = $this->_stringGenerator->generateFromOpenSSL(100);
				$string2 = $this->_stringGenerator->generateFromOpenSSL(100);
				$this->assertSame(100, strlen($string1));

				$this->assertNotEquals($string1, $string2);
				$this->assertRegExp('/^[A-Za-z0-9\.\/]{100}$/', $string1);
			}
		} else {
			$this->assertTrue(true);
		}
	}
//
//	public function testGenerateFromOpenSSLWithRegex()
//	{
//		if (function_exists('openssl_random_pseudo_bytes')) {
//			for ($i = 1; $i < 200; ++$i) {
//				$this->_stringGenerator->setPattern('/^[A-Z]+$/');
//				$string = $this->_stringGenerator->generateFromOpenSSL();
//				$this->assertRegExp('/^[A-Z]{' . $this->_length . '}$/', $string);
//			}
//		} else {
//			$this->assertTrue(true);
//		}
//	}

	public function testGenerateNativelyNoLengthSet()
	{
		for ($i = 1; $i < 200; ++$i) {
			$string1 = $this->_stringGenerator->generateNatively();
			$string2 = $this->_stringGenerator->generateNatively();
			$this->assertSame($this->_length, strlen($string1));

			$this->assertNotEquals($string1, $string2);
			$this->assertRegExp('/^[A-Za-z0-9\.\/]{' . $this->_length . '}$/', $string1);
		}
	}

	public function testGenerateNativelyShortLength()
	{
		for ($i = 1; $i < 200; ++$i) {
			$string1 = $this->_stringGenerator->generateNatively(5);
			$string2 = $this->_stringGenerator->generateNatively(5);
			$this->assertSame(5, strlen($string1));

			$this->assertNotEquals($string1, $string2);
			$this->assertRegExp('/^[A-Za-z0-9\.\/]{5}$/', $string1);
		}
	}

	public function testGenerateNativelyLongLength()
	{
		for ($i = 1; $i < 200; ++$i) {
			$string1 = $this->_stringGenerator->generateNatively(100);
			$string2 = $this->_stringGenerator->generateNatively(100);
			$this->assertSame(100, strlen($string1));

			$this->assertNotEquals($string1, $string2);
			$this->assertRegExp('/^[A-Za-z0-9\.\/]{100}$/', $string1);
		}
	}

	public function testGenerateNativelyWithRegex()
	{
		for ($i = 1; $i < 200; ++$i) {
			$this->_stringGenerator->setPattern('/^[A-Z]+$/');
			$string = $this->_stringGenerator->generateNatively();
			$this->assertRegExp('/^[A-Z]{' . $this->_length . '}$/', $string);
		}
	}

	public function getValidLengths()
	{
		return array(
			[1],
			[0],
			[100],
			[50],
			[32],
			[8],
		);
	}
}