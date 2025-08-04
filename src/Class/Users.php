<?php

namespace Roseblade\UserReg\Class;

use SilverStripe\Core\Config\Configurable;

/**
 * Base class for users, used to store config and common methods used
 * for user registration.
 * 
 * Based on the abandoned package by i-lateral
 *
 * @package UserReg
 * @author  Roseblade Media Ltd. <hello@roseblade.media>
 */
class Users
{
	use Configurable;

	/**
	 * Minimum character length of the password required
	 * on registration/account editing
	 *
	 * @var    int
	 * @config
	 */
	private static $password_min_length = 6;

	/**
	 * Maximum character length of the password required
	 * on registration/account editing
	 *
	 * @var    int
	 * @config
	 */
	private static $password_max_length = 64;

	/**
	 * Enforces strong password
	 * TODO: Needs to be implemented, possibly through a third-party library
	 *
	 * @var    boolean
	 * @config
	 */
	private static $password_require_strong = false;

	/**
	 * Stipulate if a user requires verification. NOTE this does not
	 * actually deny the user the ability to login, it only alerts them
	 * that they need validiation
	 *
	 * @var    boolean
	 * @config
	 */
	private static $require_verification = true;

	/**
	 * Forces a user to verify their account before being able to login. 
	 * To use this, you need to allocate the user to at least one group (see new_user_groups)
	 * 
	 * @var 	boolean
	 * @config
	 */
	private static $enforce_verification = true;

	/**
	 * Stipulate whether to send a verification email to users after
	 * registration
	 *
	 * @var    boolean
	 * @config
	 */
	private static $send_verification_email = true;

	/**
	 * Duration of the verification code in DateTimeInterval format.
	 * Set to false or null to disable expiration
	 *
	 * @var string
	 * @config
	 */
	private static $verification_code_life = 'P1D';

	/**
	 * Stipulate the sender address for emails sent from this module. If
	 * not set, use the default @Email.admin_email instead.
	 *
	 * @var    string
	 * @config
	 */
	private static $send_email_from	= '';

	/**
	 * Auto login users after registration
	 *
	 * @var    boolean
	 * @config
	 */
	private static $login_after_register = true;

	/**
	 * Add new users to the following groups. This is a list of group codes.
	 * Adding a new code will add the user to this group
	 *
	 * @var    array
	 * @config
	 */
	private static $new_user_groups = [
		"users-public"
	];

	//--------------------------------------------------------------------------

	/**
	 * Remove a group from the list of groups a new user is added to on
	 * registering.
	 *
	 * @param string $code Group code that will be used
	 * 
	 * @return void
	 */
	public static function removeNewUserGroup($code)
	{
		if (isset(self::config()->new_user_groups[$code]))
		{
			unset(self::config()->new_user_groups[$code]);
		}
	}

	/**
	 * Convert a code for a security group to a name
	 *
	 * @param string $code
	 *
	 * @return string
	 */
	public static function convertCodeToName(string $code): string
	{
		$name = str_replace('-', ' ', $code);
		$name = str_replace('_', ' ', $name);
		$name = ucwords($name);

		return $name;
	}
}
