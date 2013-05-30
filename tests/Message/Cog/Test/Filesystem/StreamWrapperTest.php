<?php

namespace Message\Cog\Test\Filesystem;

use Message\Cog\Filesystem\StreamWrapperManager;
use Message\Cog\Filesystem\StreamWrapper;

use Message\Cog\ReferenceParser;
use Message\Cog\Test\Module;

use Message\Cog\Filesystem\Finder;


class StreamWrapperTest extends \PHPUnit_Framework_TestCase
{
	const DEFAULT_VENDOR = 'Message';
	const DEFAULT_MODULE = 'Cog';

	protected $_modulePaths = array(
		'Message\\Cog'                   => '/path/to/installation/vendor/message/cog/src',
		'Message\\CMS'                   => '/path/to/installation/vendor/message/cog-cms',
		'Commerce\\Core'                 => '/path/to/installation/vendor/message/commerce',
		'Commerce\\Epos'                 => '/path/to/installation/vendor/message/commerce',
	);

	public function setUp()
	{
		$this->_modulePaths['UniformWares\\CustomModuleName'] = __DIR__.'/fixtures/module/example';

		$fnsUtility = $this->getMockBuilder('Message\\Cog\\Functions\\Utility')
			->disableOriginalConstructor()
			->getMock();

		// Set the default/traced vendor and module
		$fnsUtility
			->expects($this->any())
			->method('traceCallingModuleName')
			->will($this->returnValue(self::DEFAULT_VENDOR . '\\' . self::DEFAULT_MODULE));

		$this->parser = new ReferenceParser(
			new Module\FauxLocator($this->_modulePaths),
			$fnsUtility
		);

		$this->manager = new StreamWrapperManager();
	}

	public function tearDown()
	{
		$this->manager->clear();
		@unlink(__DIR__.'/fixtures/tmp/poem.txt');
	}

	public function testMapping()
	{
		$this->manager->register('bob', function(){
			$wrapper = new StreamWrapper;
			$wrapper->setMapping(array(
				"/^\/tmp\/(.*)/us" => __DIR__.'/fixtures/tmp/$1',
			));

			return $wrapper;
		});

		$contents = file_get_contents('bob://tmp/hello.txt');
		$this->assertSame('world', $contents);

		$path = 'bob://tmp/poem.txt';
		file_put_contents($path, 'A short poem');
		$this->assertSame('A short poem', file_get_contents($path));
	
		$this->assertSame(unlink($path), true);
	}

	public function testReferenceParser()
	{
		$self = $this;
		$this->manager->register('test', function() use ($self) {
			$wrapper = new StreamWrapper;
			$wrapper->setReferenceParser($self->parser);

			return $wrapper;
		});
		
		$contents = file_get_contents('test://UniformWares:CustomModuleName::assets/example.pdf');

		$this->assertSame('I am a PDF', $contents);
	}
}