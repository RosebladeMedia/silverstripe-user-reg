<?php

namespace Roseblade\UserReg\Page;

class LoginPage extends \Page
{
	/**
	 * @var string
	 */
	private static $table_name = 'LoginPage';

	/**
	 * @var string
	 */
	private static $singular_name = 'Login Page';

	/**
	 * @var string
	 */
	private static $plural_name = 'Login Pages';

	/**
	 * @var string
	 */
	private static $description = 'User login page';

	/**
	 * @var string
	 */
	private static $icon_class = 'fa-solid fa-sign-in-alt';

	/**
	 * @var bool
	 */
	private static $allowed_children = false;
}
