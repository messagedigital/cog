<?php

namespace Message\Cog\Security\Hash;

use Message\Cog\Security\Salt;

/**
 * A Bcrypt implementation for the hashing component.
 *
 * One of the most secure hash algorithms around at the moment. Much slower than
 * md5 and sha1, it makes brute force attacks difficult. Additionally, as CPUs
 * get faster you can increase the `self::WORK_FACTOR` constant to keep bcrypt
 * hashing at the same speed.
 *
 * @author James Moss <james@message.co.uk>
 * @author Joe Holdcroft <joe@message.co.uk>
 */
class Bcrypt implements HashInterface
{
	const WORK_FACTOR = 8; // Value between 4 and 31

	protected $_saltGenerator;

	/**
	 * Constructor.
	 *
	 * @param Salt $saltGenerator The pseudorandom string generator class
	 */
	public function __construct(Salt $saltGenerator)
	{
		$this->_saltGenerator = $saltGenerator;
	}

	/**
	 * Hash a string using bcrypt.
	 *
	 * The salt passed must be 22 bytes or more. Only the first 22 bytes will be
	 * used as the salt.
	 *
	 * Generates *0 if invalid characters are used. Exception thrown if
  	 * *0 is returned.
	 *
	 * @param  string      $string String to hash
	 * @param  string|null $salt   Salt to use
	 *
	 * @return string              The hashed value
	 *
	 * @throws \InvalidArgumentException If the salt is less than 22 bytes long
	 * @throws \InvalidArgumentException If the sale contains invalid characters
	 */
	public function encrypt($string, $salt = null)
	{
		if (is_null($salt)) {
			$salt = $this->_saltGenerator->generate(22);
		}

		if (strlen($salt) < 22) {
			throw new \InvalidArgumentException(sprintf(
				'Salt `%s` must be at least 22 bytes when using Bcrypt.',
				$salt
			));
		}

		// Using a salt formatted in this way tells crypt() to use bcrypt
		$bcryptSalt = '$2a$' . str_pad(self::WORK_FACTOR, 2, '0', STR_PAD_LEFT) . '$'
					 . substr($salt, 0, 22);

		$crypto = crypt($string, $bcryptSalt);

		if ('*0' === $crypto) {
			throw new \InvalidArgumentException(sprintf(
				'Salt `%s` contains invalid characters.',
				$salt
			));
		}

		return $crypto;
	}

	/**
	 * Check if a string matches a bcrypt hash.
	 *
	 * @param  string $string Plain text string to check
	 * @param  string $hash   Full bcrypt hashed string
	 *
	 * @return boolean        Result of match check
	 */
	public function check($string, $hash)
	{
		return $hash === crypt($string, $hash);
	}
}