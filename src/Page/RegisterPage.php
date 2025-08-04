<?php

namespace Roseblade\UserReg\Page;

class RegisterPage extends \Page
{
	/**
	 * @var string
	 */
	private static $table_name = 'RegisterPage';

	/**
	 * @var string
	 */
	private static $singular_name = 'Registration Page';

	/**
	 * @var string
	 */
	private static $plural_name = 'Registration Pages';

	/**
	 * @var string
	 */
	private static $description = 'User registration page';

	/**
	 * @var string
	 */
	private static $icon_class = 'fa-solid fa-user-plus';

	/**
	 * @var bool
	 */
	private static $allowed_children = false;
}
