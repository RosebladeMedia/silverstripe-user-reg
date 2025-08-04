<?php

namespace Roseblade\UserReg\Page;

class AccountPage extends \Page
{
	/**
	 * @var string
	 */
	private static $table_name = 'AccountPage';

	/**
	 * @var string
	 */
	private static $singular_name = 'Account Management Page';

	/**
	 * @var string
	 */
	private static $plural_name = 'Accounts';

	/**
	 * @var string
	 */
	private static $description = 'User account management page';

	/**
	 * @var string
	 */
	private static $icon_class = 'fa-solid fa-user-tag';

	/**
	 * @var bool
	 */
	private static $allowed_children = false;

	//--------------------------------------------------------------------------

	public function getRegistrationPage()
	{
		return RegisterPage::get()->first();
	}
}
