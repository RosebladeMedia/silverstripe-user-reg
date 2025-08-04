<?php

namespace Roseblade\UserReg\Page;

use Roseblade\UserReg\Class\Users;
use Roseblade\UserReg\Forms\EditAccountForm;
use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\LiteralField;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\ValidationResult;
use SilverStripe\Security\Member;
use SilverStripe\Security\MemberAuthenticator\ChangePasswordHandler;
use SilverStripe\Security\MemberAuthenticator\MemberAuthenticator;
use SilverStripe\Security\Security;
use SilverStripe\View\ArrayData;

/**
 * Controller that is used to allow users to manage their accounts via
 * the front end of the site. Based on the i-lateral package
 * 
 * @package UserReg
 * @author  Roseblade Media Ltd (hello@roseblade.media)
 */
class AccountPageController extends \PageController
{
	/**
	 * Allowed sub-URL's on this controller
	 * 
	 * @var    array
	 * @config
	 */
	private static $allowed_actions = [
		"index",
		"edit",
		"changepassword",
		"EditAccountForm",
		"ChangePasswordForm",
	];

	/**
	 * User account associated with this controller
	 *
	 * @var Member
	 */
	protected $member;

	/**
	 * Getter for member
	 *
	 * @return Member
	 */
	public function getMember()
	{
		return $this->member;
	}

	/**
	 * Setter for member
	 *
	 * @param Member $member A member to associate
	 * 
	 * @return self
	 */
	public function setMember(Member $member)
	{
		$this->member = $member;
		return $this;
	}

	/**
	 * Determine if current user requires verification (based on their
	 * account and Users verification setting).
	 *
	 * @return boolean
	 */
	public function RequireVerification()
	{
		return ((!$this->member->isVerified()) && (Users::config()->require_verification));
	}

	/**
	 * Perorm setup when this controller is initialised
	 *
	 * @return void
	 */
	public function init()
	{
		parent::init();

		// Set our member object
		$member = Security::getCurrentUser();

		if (empty($member))
		{
			$loginPage 	= LoginPage::get()->first();
			$this->redirect($loginPage->Link());
		}

		if ($member instanceof Member)
		{
			$this->member = $member;
		}
	}

	// /**
	//  * Get the link to this controller
	//  * 
	//  * @param string $action The URL endpoint for this controller
	//  * 
	//  * @return string
	//  */
	// public function Link($action = null)
	// {
	// 	return Controller::join_links(
	// 		$this->config()->url_segment,
	// 		$action
	// 	);
	// }

	/**
	 * Get an absolute link to this controller
	 *
	 * @param string $action The URL endpoint for this controller
	 * 
	 * @return string
	 */
	public function AbsoluteLink($action = null)
	{
		return Director::absoluteURL($this->Link($action));
	}

	/**
	 * Get a relative (to the root url of the site) link to this
	 * controller
	 *
	 * @param string $action The URL endpoint for this controller 
	 * 
	 * @return string
	 */
	public function RelativeLink($action = null)
	{
		return Controller::join_links(
			$this->Link($action)
		);
	}

	/**
	 * Display the basic summary of this user and any additional
	 * "content sections" that have been added
	 *
	 * @return HTMLText
	 */
	public function index()
	{
		/** Setup default profile summary sections */

		$this->customise(
			[
				"Title" => _t('Roseblade\UserReg.PROFILESUMMARY', 'My Profile'),
				"MetaTitle" => _t('Roseblade\UserReg.PROFILESUMMARY', 'My Profile')
			]
		);

		$this->extend("onBeforeIndex");

		return $this->render();
	}

	/**
	 * Setup the ability for this user to edit their account details
	 *
	 * @return HTMLText
	 */
	public function edit()
	{
		$member = Security::getCurrentUser();
		$form 	= $this->EditAccountForm();

		if ($member instanceof Member)
		{
			$form->loadDataFrom($member);
		}

		$this->customise(
			[
				"Title" => _t(
					"Roseblade\UserReg\Users.EDITACCOUNTDETAILS",
					"Edit account details"
				),
				"MetaTitle" => _t(
					"Roseblade\UserReg\Users.EDITACCOUNTDETAILS",
					"Edit account details"
				),
				"Form"  => $form
			]
		);

		$this->extend("onBeforeEdit");

		return $this->render();
	}

	/**
	 * Generate a form to allow the user to change their password
	 *
	 * @return HTMLText
	 */
	public function changepassword()
	{
		// Set the back URL for this form
		$request 		= $this->getRequest();
		$session 		= $request->getSession();
		$password_set 	= $request->getVar("s");
		$back_url 		= Controller::join_links(
			$this->Link("changepassword"),
			"?s=1"
		);
		$session->set("BackURL", $back_url);

		$form 			= $this->ChangePasswordForm();

		// Is password changed, set a session message.
		if ($password_set && $password_set == 1)
		{
			$form->sessionMessage(
				_t(
					"Roseblade\UserReg\Users.PASSWORDCHANGEDSUCCESSFULLY",
					"Password Changed Successfully"
				),
				ValidationResult::TYPE_GOOD
			);
		}

		$this->customise(
			[
				"Title" 	=> _t(
					"Security.ChangeYourPassword",
					"Change your password"
				),
				"MetaTitle" => _t(
					"Security.ChangeYourPassword",
					"Change your password"
				),
				"Form"  => $form
			]
		);

		$this->extend("onBeforeChangePassword");

		return $this->render();
	}

	/**
	 * Factory for generating a profile form. The form can be expanded using an
	 * extension class and calling the updateEditProfileForm method.
	 *
	 * @return Form
	 */
	public function EditAccountForm()
	{
		$form = EditAccountForm::create($this, "EditAccountForm");

		$this->extend("updateEditAccountForm", $form);

		return $form;
	}

	/**
	 * Factory for generating a change password form. The form can be expanded
	 * using an extension class and calling the updateChangePasswordForm method.
	 *
	 * @return Form
	 */
	public function ChangePasswordForm()
	{
		$handler = ChangePasswordHandler::create(
			$this->Link(),
			new MemberAuthenticator()
		);
		$handler->setRequest($this->getRequest());

		$form = $handler->changePasswordForm();

		$form
			->Actions()
			->find("name", "action_doChangePassword")
			->addExtraClass("btn btn-green btn-primary");

		$cancel_btn = LiteralField::create(
			"CancelLink",
			'<a href="' . $this->Link() . '" class="btn btn-red">' . _t("Roseblade\UserReg\Users.CANCEL", "Cancel") . '</a>'
		);

		$form
			->Actions()
			->insertBefore("action_doChangePassword", $cancel_btn);

		$this->extend("updateChangePasswordForm", $form);

		return $form;
	}

	/**
	 * Return a list of nav items for managing a users profile. You can add new
	 * items to this menu using the "updateAccountMenu" extension
	 *
	 * @return ArrayList
	 */
	public function getAccountMenu()
	{
		$menu = ArrayList::create();
		$curr_action = $this->request->param("Action");

		$menu->add(ArrayData::create([
			"ID"    => 0,
			"Title" => _t('Roseblade\UserReg.PROFILESUMMARY', "Profile Summary"),
			"Link"  => $this->Link(),
			"LinkingMode" => (!$curr_action) ? "current" : "link"
		]));

		$menu->add(ArrayData::create([
			"ID"    => 10,
			"Title" => _t('Roseblade\UserReg.EDITDETAILS', "Edit account details"),
			"Link"  => $this->Link("edit"),
			"LinkingMode" => ($curr_action == "edit") ? "current" : "link"
		]));

		$menu->add(ArrayData::create([
			"ID"    => 30,
			"Title" => _t('Roseblade\UserReg.CHANGEPASSWORD', "Change password"),
			"Link"  => $this->Link("changepassword"),
			"LinkingMode" => ($curr_action == "changepassword") ? "current" : "link"
		]));

		$this->extend("updateAccountMenu", $menu, $curr_action);

		return $menu->sort("ID", "ASC");
	}
}
