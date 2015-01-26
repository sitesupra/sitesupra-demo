<?php

/*
 * Copyright (C) SiteSupra SIA, Riga, Latvia, 2015
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 *
 */

namespace Supra\Package\CmsAuthentication\Encoder;

use Symfony\Component\Security\Core\Encoder\PasswordEncoderInterface;

class SupraBlowfishEncoder implements PasswordEncoderInterface
{
	/**
	 * Hashing method used
	 * @var string
	 */
	protected static $algorithm = '2a';

	/**
	 * Chosen algorithm strength
	 * @var int
	 */
	protected $strength = 12;

	/**
	 * @var int
	 */
	protected static $saltLength = 22;

	/**
	 * @var string
	 */
	protected static $saltPool = './0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';

	/**
	 * @var int
	 */
	protected static $expectedLength = 60;

	/**
	 * Encodes the raw password.
	 *
	 * @param string $raw The password to encode
	 * @param string $salt The salt
	 *
	 * @throws \RuntimeException
	 * @return string The encoded password
	 */
	public function encodePassword($raw, $salt)
	{
		// Not using user provided salt, generating
		$saltBase = $this->generateSalt();
		$strength = str_pad((string) $this->strength, 2, '0', STR_PAD_LEFT);
		$salt = '$' . self::$algorithm . '$' . $strength . '$' . $saltBase . '$';

		$hash = crypt((string) $raw, $salt);

		if (strlen($hash) != self::$expectedLength) {
			throw new \RuntimeException("Generated hash doesn't match expected size");
		}

		return $hash;
	}

	/**
	 * Checks a raw password against an encoded password.
	 *
	 * @param string $encoded An encoded password
	 * @param string $raw A raw password
	 * @param string $salt The salt
	 *
	 * @return Boolean true if the password is valid, false otherwise
	 */
	public function isPasswordValid($encoded, $raw, $salt)
	{
		$expectedHash = crypt((string) $raw, $encoded);
		$valid = ($expectedHash === $encoded);

		return $valid;
	}

	/**
	 * @throws \RuntimeException
	 * @internal param string $characters
	 * @internal param int $length
	 * @return string
	 */
	protected function generateSalt()
	{
		$pool = self::$saltPool;
		$length = self::$saltLength;

		if ( ! is_string($pool) || $pool == '') {
			throw new \RuntimeException("Salt allowed character list cannot be empty");
		}

		$poolLength = strlen($pool);

		if ( ! is_int($length) || $length <= 0) {
			throw new \RuntimeException("Salt length must be greater than zero");
		}

		$salt = '';
		for ($i = 0; $i < $length; $i++) {
			$salt .= substr($pool, mt_rand(0, $poolLength - 1), 1);
		}

		if (strlen($salt) != $length) {
			throw new \RuntimeException("Expected salt length doesn't match actual length");
		}

		return $salt;
	}

}
