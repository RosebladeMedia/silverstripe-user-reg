<?php

namespace Roseblade\UserReg\Page;

use Roseblade\UserReg\Class\Users;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\TextField;
use SilverStripe\Forms\EmailField;
use SilverStripe\Forms\FormAction;
use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Forms\RequiredFields;
use SilverStripe\Forms\ConfirmedPasswordField;
use SilverStripe\ORM\ValidationResult;
use SilverStripe\Security\Member;
use SilverStripe\Security\Security;

/**
 * Controller class for user registration and account verification.
 * 
 * Accounts can also be verified through this controller using the unique
 * verification code generated when they signed up.
 * 
 * @package 	UserReg
 * @author 		Roseblade Media Ltd <hello@roseblade.media>
 */
class RegisterPageController extends \PageController
{
	/**
	 * Current actions available to this controller
	 * index 		Used for registration form, or redirected to control panel if they're logged in
	 * confirm 		Used to confirm the user has signed up, and if needed, send the verification email
	 * verify 		Used for the user to verify their email address
	 * RegisterForm Used to generate the registration form
	 *
	 * @var array
	 */
	private static $allowed_actions = [
		"index",
		"sendverification",
		"verify",
		"login",
		"RegisterForm",
		"LoginForm"
	];

	/**
	 * Setup default templates for this controller
	 * Based on the user reg module by i-lateral
	 *
	 * @var array
	 */
	// protected $templates = [
	// 	'index' => [
	// 		'RegisterController',
	// 		self::class,
	// 		'Page'
	// 	],
	// 	'sendverification' => [
	// 		'RegisterController_sendverification',
	// 		self::class . '_sendverification',
	// 		'RegisterController',
	// 		self::class,
	// 		'Page'
	// 	],
	// 	'verify' => [
	// 		'RegisterController_verify',
	// 		self::class . '_verify',
	// 		'RegisterController',
	// 		self::class,
	// 		'Page'
	// 	]
	// ];

	//--------------------------------------------------------------------------

	/**
	 * Perorm setup when this controller is initialised
	 *
	 * @return void
	 */
	public function init()
	{
		parent::init();
	}

	/**
	 * Get the link to this controller
	 * 
	 * @param string $action The URL endpoint for this controller
	 * 
	 * @return string
	 */
	// public function Link($action = null)
	// {
	// 	$
	// }

	// /**
	//  * Get an absolute link to this controller
	//  *
	//  * @param string $action The URL endpoint for this controller
	//  *
	//  * @return string
	//  */
	// public function AbsoluteLink($action = null)
	// {
	// 	return Director::absoluteURL($this->Link($action));
	// }

	/**
	 * Get a relative (to the root url of the site) link to this
	 * controller
	 *
	 * @param string $action The URL endpoint for this controller
	 *
	 * @return string
	 */
	// public function RelativeLink($action = null)
	// {
	// 	return Controller::join_links(
	// 		$this->Link($action)
	// 	);
	// }

	//--------------------------------------------------------------------------

	public function index()
	{
		/** Check if the user is logged in already */
		$user 	= Security::getCurrentUser();

		if ($user)
		{
			$redirect_url 	= $this->AccountPageLink();

			$this->redirect($redirect_url);
		}

		$this->customise([
			'Title'     => _t('Roseblade\UserReg.REGISTER', 'Register'),
			'MetaTitle' => _t('Roseblade\UserReg.REGISTER', 'Register'),
			'Form'      => $this->RegisterForm(),
		]);

		$this->extend("updateIndexAction");

		return $this->render();
	}

	// /**
	//  * If content controller exists, return it's menu function
	//  *
	//  * @param int $level Menu level to return.
	//  *
	//  * @return ArrayList
	//  */
	// public function getMenu($level = 1)
	// {
	// 	if (class_exists(ContentController::class))
	// 	{
	// 		$controller = Injector::inst()->get(ContentController::class);
	// 		return $controller->getMenu($level);
	// 	}
	// }

	// /**
	//  * Shortcut for getMenu
	//  * 
	//  * @param int $level Menu level to return.
	//  * 
	//  * @return ArrayList
	//  */
	// public function Menu($level)
	// {
	// 	return $this->getMenu();
	// }


	/**
	 * Send a verification email to the user provided (if verification
	 * emails are enabled and account is not already verified)
	 *
	 * @return HTMLText
	 */
	public function sendverification()
	{
		// If we don't allow verification emails, return an error
		if (!Users::config()->send_verification_email)
		{
			return $this->httpError(400);
		}

		$sent 	= false;
		$member = Security::getCurrentUser();

		if (($member->exists()) && (!$member->isVerified()))
		{
			$sent = $member->setAndSendVerificationEmail();
		}

		$this->customise([
			"Title" => _t('Roseblade\UserReg.ACCOUNTVERIFICATION', 'Account Verification'),
			"MetaTitle" => _t('Roseblade\UserReg.ACCOUNTVERIFICATION', 'Account Verification'),
			"Sent" => $sent
		]);

		$this->extend("updateSendVerificationAction");

		return $this->render();
	}

	/**
	 * Verify the provided user (ID) using the verification code (Other
	 * ID) provided
	 *
	 * @return HTMLText
	 */
	public function verify()
	{
		$id 		= $this->getRequest()->param("ID");
		$code 		= $this->getRequest()->param("OtherID");
		$member 	= Member::get()->byID($id);
		$verify 	= false;

		/**
		 * All users have a field for "VerifiedTimestamp"
		 * If this field is empty (e.g. null), the account is unverified. 
		 * So, we need to firstly check that this field is empty, otherwise
		 * they're already verified.
		 */
		$this->extend("onBeforeVerify", $member);

		/** Do we have a code and member? Are they still unverified? */
		if (($member) && ($code) && (empty($member->VerifiedTimeStamp)))
		{
			if (!empty($member->VerificationExpiry))
			{
				/** There's an expiry timestamp - is it valid? */
				$currentDT 	= new \DateTime();
				$expiryDT 	= new \DateTime($member->VerificationExpiry);

				if ($expiryDT < $currentDT)
				{
					/** It's in the past, so a new one needs to be sent */
					$member->setAndSendVerificationEmail();
					return $this->httpError(403, _t('Roseblade\UserReg.VERIFICATIONEXPIRED', 'Verification code has expired. A new one has been issued.'));
				}

				/** And the codes match? */
				if ($code != $member->VerificationCode)
				{
					return $this->httpError(403, _t('Roseblade\UserReg.INCORRECTCODE', 'Incorrect verification code.'));
				}
			}
		}
		else
		{
			return $this->httpError(404, _t('Roseblade\UserReg.UNKNOWNORVERIFIED', 'An unverified account could not be found using the details provided.'));
		}

		/** All good. Update the account, and log them in */
		$member->VerifiedTimestamp 	= (new \DateTime())->format('Y-m-d H:i:s');

		$this->extend("onAfterVerify", $member);

		$member->write();
		$member->loginUser();

		$this->customise(
			[
				"Title" => _t('Roseblade\UserReg.VERIFYTITLE', 'Account Verification'),
				"MetaTitle" => _t('Roseblade\UserReg.VERIFYTITLE', 'Account Verification')
			]
		);

		return $this->render();
	}

	/**
	 * Registration form
	 * Based off the i-lateral User Registration module
	 *
	 * @return Form
	 */
	public function RegisterForm(): Form
	{
		$session = $this->getRequest()->getSession();

		/** If there's a back URL to redirect the user to after registration, we save it into the session here */
		if (isset($_REQUEST['BackURL']))
		{
			$session->set(
				'BackURL',
				$_REQUEST['BackURL']
			);
		}

		/** Load the config from our User class */
		$config = Users::config();

		/** Create the form */
		$form = Form::create(
			$this,
			'RegisterForm',
			FieldList::create(
				TextField::create(
					'FirstName',
					_t('SilverStripe\Security\Member.FIRSTNAME', 'First Name')
				),
				TextField::create(
					'Surname',
					_t('SilverStripe\Security\Member.SURNAME', 'Surname')
				),
				EmailField::create(
					'Email',
					_t('SilverStripe\Security\Member.EMAIL', 'Email')
				),
				$password_field = ConfirmedPasswordField::create('Password')
			),
			FieldList::create(
				FormAction::create(
					'doRegister',
					_t('Roseblade\UserReg.REGISTER', 'Register')
				)
			),
			RequiredFields::create([
				'FirstName',
				'Surname',
				'Email',
				'Password'
			])
		);

		/** Set any restrictions on the password field */
		$password_field->minLength				= $config->get('password_min_length');
		$password_field->maxLength				= $config->get('password_max_length');
		$password_field->requireStrongPassword	= $config->get('password_require_strong');

		$this->extend('updateRegisterForm', $form);

		/** The form data will be set in the session (e.g. if there's an error, we can repopulate the form) */
		$session_data = $session->get("Form.{$form->FormName()}.data");

		if (($session_data) && (is_array($session_data)))
		{
			/** Load it into the form and clear the session */
			$form->loadDataFrom($session_data);
			$session->clear("Form.{$form->FormName()}.data");
		}

		/** Return the form */
		return $form;
	}

	/**
	 * Register a new member. This action is deigned to be intercepted at 2
	 * points:
	 *
	 *  - Modify the initial member filter (so that you can perfom bespoke
	 *    member filtering
	 *
	 *  - Modify the member user before saving (so we can add extra permissions
	 *    etc)
	 * 
	 * Based on the i-lateral module
	 *
	 * @param array $data User submitted data
	 * @param Form  $form Registration form
	 * 
	 * @return SS_HTTPResponse
	 */
	public function doRegister($data, $form)
	{
		$filter = [];
		$session = $this->getRequest()->getSession();

		/** We'll add the email as a filter, if it's set */
		if (isset($data['Email']))
		{
			$filter['Email'] = $data['Email'];
		}

		/** Extension */
		$this->extend("updateMemberFilter", $filter);

		/** Check if a user already exists based on the filter */
		if ($member = Member::get()->filter($filter)->first())
		{
			if ($member)
			{
				$form->sessionMessage(
					_t('Roseblade\UserReg.ACCOUNTEXISTS', 'Sorry, an account already exists with those details.'),
					ValidationResult::TYPE_ERROR
				);

				/** Load errors into session and post back, minus the password (obviously) */
				unset($data["Password"]);

				$session->set("Form.{$form->FormName()}.data", $data);

				return $this->redirectBack();
			}
		}

		/** If we get this far, we can create the new user */
		$member = Member::create();
		$member->Register($data);

		$this->extend("updateNewMember", $member, $data);

		/** Get the back URL, if it's set */
		$session_url = $session->get("BackURL");
		$request_url = $this->getRequest()->requestVar("BackURL");

		/** If a back URL is used in session, we'll use that one */
		if (!empty($session_url))
		{
			$redirect_url = $session_url;
		}
		elseif (!empty($request_url))
		{
			$redirect_url = $request_url;
		}
		else
		{
			/** If there's no other redirect URL, we can use the default account manager one */
			$redirect_url = $this->AccountPageLink();
		}

		return $this->redirect($redirect_url);
	}

	public function AccountPageLink()
	{
		$accountPage 	= AccountPage::get()->first();

		if ($accountPage)
		{
			return $accountPage->Link();
		}
		else
		{
			return '/';
		}
	}
}
