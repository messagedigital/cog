<?php

namespace Message\Cog\Test\ValueObject;

use Message\Cog\ValueObject\Authorship;
use Message\Cog\ValueObject\DateTimeImmutable;

class AuthorshipTest extends \PHPUnit_Framework_TestCase
{
	public function testCreating()
	{
		$authorship = new Authorship;
		$timestamp  = new DateTimeImmutable('10 minutes ago');
		$author     = 5;

		$this->assertNull($authorship->createdAt());
		$this->assertNull($authorship->createdBy());

		$this->assertEquals($authorship, $authorship->create($timestamp, $author));

		$this->assertEquals($timestamp, $authorship->createdAt());
		$this->assertEquals($author, $authorship->createdBy());

		return $authorship;
	}

	/**
	 * @depends           testCreating
	 */
	public function testCreatingTwiceThrowsException($authorship)
	{
		$createdAt = $authorship->createdAt();
		$createdBy = $authorship->createdBy();

		try {
			$authorship->create(new DateTimeImmutable('now'), 65);
		}
		catch (\LogicException $e) {
			$this->assertEquals($createdAt, $authorship->createdAt());
			$this->assertEquals($createdBy, $authorship->createdBy());

			return;
		}

		$this->fail('No exception was thrown when trying to set create metadata twice');
	}

	/**
	 * @expectedException        \LogicException
	 * @expectedExceptionMessage updating is disabled
	 */
	public function testDisableUpdate()
	{
		$authorship = new Authorship;
		$authorship->disableUpdate();
		$authorship->update(new DateTimeImmutable('now'), 'Test McTester');
	}

	public function testEnableUpdate()
	{
		$authorship = new Authorship;
		$timestamp  = new DateTimeImmutable('1 day ago');
		$author     = 'Iris Schaffer';

		$authorship->disableUpdate();
		$authorship->enableUpdate();

		$authorship->update($timestamp, $author);

		$this->assertEquals($timestamp, $authorship->updatedAt());
		$this->assertEquals($author, $authorship->updatedBy());
	}

	public function testIsUpdatable()
	{
		$authorship = new Authorship;

		$this->assertTrue($authorship->isUpdatable());

		$authorship->disableUpdate();
		$this->assertFalse($authorship->isUpdatable());

		$authorship->enableUpdate();
		$this->assertTrue($authorship->isUpdatable());
	}

	public function testDeleteAndRestoreWhenUpdateDisabled()
	{
		$authorship = new Authorship;
		$authorship->disableUpdate();
		$authorship->delete(new DateTimeImmutable('now'), 'Iris Schaffer');
		$authorship->restore();
	}

	/**
	 * @expectedException        \LogicException
	 * @expectedExceptionMessage deleting is disabled
	 */
	public function testDisableDeleteInDelete()
	{
		$authorship = new Authorship;
		$authorship->disableDelete();
		$authorship->delete(new DateTimeImmutable('now'), 'Iris Schaffer');
	}

	public function testEnableDelete()
	{
		$authorship = new Authorship;
		$timestamp  = new DateTimeImmutable('1 day ago');
		$author     = 'Iris Schaffer';

		$authorship->disableDelete();
		$authorship->enableDelete();

		$authorship->delete($timestamp, $author);
		$this->assertTrue($authorship->isDeleted());

		$authorship->restore();
		$this->assertFalse($authorship->isDeleted());
	}

	/**
	 * @expectedException        \LogicException
	 * @expectedExceptionMessage deleting and restoring is disabled
	 */
	public function testDisableDeleteInRestore()
	{
		$authorship = new Authorship;

		// NOTE: an exception would get thrown here if 'delete' and 'restore' didn't work
		$authorship->delete(new DateTimeImmutable('now'), 'Iris Schaffer');
		$authorship->disableDelete();
		$authorship->restore();
	}

	public function testIsDeletable()
	{
		$authorship = new Authorship;

		$this->assertTrue($authorship->isDeletable());

		$authorship->disableDelete();
		$this->assertFalse($authorship->isDeletable());

		$authorship->enableDelete();
		$this->assertTrue($authorship->isDeletable());
	}

	public function testUpdating()
	{
		$authorship = new Authorship;
		$timestamp  = new DateTimeImmutable('1 day ago');
		$author     = 'Joe Holdcroft';

		$this->assertNull($authorship->updatedAt());
		$this->assertNull($authorship->updatedBy());

		$this->assertEquals($authorship, $authorship->update($timestamp, $author));

		$this->assertEquals($timestamp, $authorship->updatedAt());
		$this->assertEquals($author, $authorship->updatedBy());

		return $authorship;
	}

	/**
	 * @depends testUpdating
	 */
	public function testUpdatingMultipleTimes($authorship)
	{
		$timestamp = new DateTimeImmutable('+2 minutes');
		$author    = 'Danny Hannah';

		$authorship->update($timestamp, $author);

		$this->assertEquals($timestamp, $authorship->updatedAt());
		$this->assertEquals($author, $authorship->updatedBy());
	}

	public function testDeleting()
	{
		$authorship = new Authorship;
		$timestamp  = new DateTimeImmutable('-639 minutes');
		$author     = new \stdClass;

		$this->assertNull($authorship->deletedAt());
		$this->assertNull($authorship->deletedBy());

		$this->assertFalse($authorship->isDeleted());

		$this->assertEquals($authorship, $authorship->delete($timestamp, $author));

		$this->assertEquals($timestamp, $authorship->deletedAt());
		$this->assertEquals($author, $authorship->deletedBy());

		$this->assertTrue($authorship->isDeleted());

		return $authorship;
	}

	/**
	 * @depends testDeleting
	 */
	public function testDeletingTwiceThrowsException($authorship)
	{
		$deletedAt = $authorship->deletedAt();
		$deletedBy = $authorship->deletedBy();

		try {
			$authorship->delete(new DateTimeImmutable('now'), 'Test McTester');
		}
		catch (\LogicException $e) {
			$this->assertEquals($deletedAt, $authorship->deletedAt());
			$this->assertEquals($deletedBy, $authorship->deletedBy());

			return;
		}

		$this->fail('No exception was thrown when trying to set delete metadata twice');
	}

	/**
	 * @depends testDeleting
	 */
	public function testRestoring($authorship)
	{
		$this->assertEquals($authorship, $authorship->restore());

		$this->assertNull($authorship->deletedAt());
		$this->assertNull($authorship->deletedBy());
	}

	/**
	 * @expectedException        \LogicException
	 * @expectedExceptionMessage has not been deleted
	 */
	public function testRestoringWhenNotDeleted()
	{
		$authorship = new Authorship;

		$authorship->restore();
	}

	public function testSettingNullTimestampDefaultsToNow()
	{
		$authorship = new Authorship;
		$time       = time();

		$authorship
			->create(null, 5)
			->update(null, 10)
			->delete(null, 'Joe');

		$this->assertEquals($time, $authorship->createdAt()->getTimestamp(), '', 5);
		$this->assertEquals($time, $authorship->updatedAt()->getTimestamp(), '', 5);
		$this->assertEquals($time, $authorship->deletedAt()->getTimestamp(), '', 5);
	}

	public function testToStringCreated()
	{
		$this->expectOutputString('Created by Joe on 3 April 2013 at 9:30am');

		$authorship = new Authorship;

		$authorship->create(new DateTimeImmutable('3 April 2013 09:30'), 'Joe');

		echo $authorship;
	}

	public function testToStringCreatedUpdated()
	{
		$this->expectOutputString(
			'Created by Jimbo on 24 January 1991 at 11:55pm' . "\n" .
			'Last updated by James on 13 May 2013 at 1:45pm'
		);

		$authorship = new Authorship;

		$authorship
			->create(new DateTimeImmutable('24 January 1991 23:55'), 'Jimbo')
			->update(new DateTimeImmutable('13 May 2013 13:45'), 'James');

		echo $authorship;
	}

	public function testToStringCreatedUpdatedDeleted()
	{
		$this->expectOutputString(
			'Created by Tester on 17 February 2000 at 6:00pm' . "\n" .
			'Last updated by Someone else on 26 March 2005 at 4:00pm' . "\n" .
			'Deleted by Danny on 4 September 2014 at 4:00am'
		);

		$authorship = new Authorship;

		$authorship
			->create(new DateTimeImmutable('17 February 2000 18:00'), 'Tester')
			->update(new DateTimeImmutable('26 March 2005 16:00'), 'Someone else')
			->delete(new DateTimeImmutable('4 September 2014 04:00'), 'Danny');

		echo $authorship;
	}

	public function testToStringUpdated()
	{
		$this->expectOutputString('Last updated by Jamie on 1 January 2010 at 12:30pm');

		$authorship = new Authorship;

		$authorship->update(new DateTimeImmutable('1 January 2010 12:30'), 'Jamie');

		echo $authorship;
	}
}