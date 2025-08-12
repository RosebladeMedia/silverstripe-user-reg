<?php

namespace Roseblade\UserReg\DataExtension;

use Roseblade\UserReg\Class\Users;
use Roseblade\UserReg\Page\RegisterPage;
use SilverStripe\Control\Controller;
use SilverStripe\Control\Email\Email;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\ORM\DataExtension;
use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\Security\Group;
use SilverStripe\Security\IdentityStore;
use SilverStripe\Security\Member;
use SilverStripe\Security\Security;
use SilverStripe\SiteConfig\SiteConfig;
use SilverStripe\View\ArrayData;

/**
 * Extends member object
 * 
 * @package 	UserReg
 * @author 		Roseblade Media Ltd <hello@roseblade.media>
 */
class MemberExtension extends DataExtension
{
	/**
	 * Additional database fields
	 * 
	 * @var array
	 */
	private static $db 	= [
		'VerificationCode'		=> 'Varchar(64)',
		'VerificationExpiry'	=> 'Datetime',
		'VerifiedTimestamp'		=> 'Datetime'
	];

	/**
	 * Checks if there's a verified timestamp and returns
	 * true if the account is verified, false if not
	 *
	 * @return bool
	 */
	public function isVerified(): bool
	{
		return (!empty($this->owner->VerifiedTimestamp));
	}

	/**
	 * Registers a new user based on the supplied data array.
	 *
	 * @param array $data
	 * 
	 * @return Member New member object
	 */
	public function Register(array $data): Member
	{
		/** Firstly - we need to remove any duplicate passwords which may have been passed */
		if ((isset($data["Password"])) && (is_array($data["Password"])) && (isset($data["Password"]["_Password"])))
		{
			$data["Password"] = $data["Password"]["_Password"];
		}

		/** Passes through all the data from the array to the object */
		$this->owner->update($data);

		if (Users::config()->enforce_verification)
		{
			$this->setAndSendVerificationEmail();
		}
		else
		{
			/** We write here as the set and send function already does this - saves double writing */
			$this->owner->write();
		}

		/** As required, add user to any groups */
		if (count(Users::config()->new_user_groups))
		{
			$groups = Group::get()->filter(
				[
					"Code" 	=> Users::config()->new_user_groups
				]
			);

			foreach ($groups as $group)
			{
				$group->Members()->add($this->owner);
				$group->write();
			}
		}

		/** Are we automatically logging the user in? */
		if (Users::config()->login_after_register)
		{
			$this->owner->loginUser();
		}

		return $this->owner;
	}

	/**
	 * Checks if a user can login
	 *
	 * @param mixed $result
	 */
	public function canLogIn($result)
	{
		if ((count(Users::config()->new_user_groups)) && ($this->owner->inGroups(Users::config()->new_user_groups)))
		{
			if ((Users::config()->enforce_verification) && (!$this->owner->isVerified()))
			{
				$result->addError(
					_t(
						'Roseblade\UserReg.FAILEDLOGINNOTVERIFIED',
						'You need to verify your email address before you are able to login.'
					)
				);
			}
		}
	}

	//--------------------------------------------------------------------------

	public function setAndSendVerificationEmail(): bool
	{
		/** Set a verification code */
		$this->owner->VerificationCode	= $this->generateVerificationCode();

		/** If we have an exiry set, sort it here */
		if (Users::config()->verification_code_life)
		{
			$currentDT 		= new \DateTime();
			$expiryDT 		= $currentDT->add(new \DateInterval(Users::config()->verification_code_life));
			$this->owner->VerificationExpiry	= $expiryDT->format('Y-m-d H:i:s');
		}

		/** Save the record */
		$this->owner->write();

		/** Do we need to send a verification email? */
		if (Users::config()->send_verification_email)
		{
			/** Handled in separate function */
			return $this->owner->sendVerificationEmail();
		}

		return true;
	}

	/**
	 * Send a verification email to this user account
	 *
	 * @return boolean
	 */
	public function sendVerificationEmail(): bool
	{
		if ($this->owner->exists())
		{
			$registerController = RegisterPage::get()->first();
			$siteConfig 		= SiteConfig::current_site_config();

			$subject 			= _t("Roseblade\UserReg.PLEASEVERIFY", $siteConfig->Title . " - Please verify your account");

			/** If there's no defined email, we'll use the global admin email */
			if (Users::config()->send_email_from)
			{
				$from 	= Users::config()->send_email_from;
			}
			else
			{
				$from 	= Email::config()->admin_email;
			}

			/** Set up the verification email to be sent */
			$email = Email::create();
			$email
				->setFrom($from)
				->setTo($this->owner->Email)
				->setSubject($subject)
				->setHTMLTemplate("Roseblade\\UserReg\\Email\\Verification")
				->setData(ArrayData::create([
					"Link" 		=> Controller::join_links(
						$registerController->AbsoluteLink("verify"),
						$this->owner->ID,
						$this->owner->VerificationCode
					),
					"Expiry" 		=> DBField::create_field('Datetime', $this->owner->VerificationExpiry),
					"Member"		=> $this,
					"SiteConfig"	=> $siteConfig
				]));

			/** Send email */
			if ($email->send())
			{
				return true;
			}
		}

		return false;
	}

	/**
	 * Generates a random 64 character verification code for new users
	 *
	 * @return string
	 */
	public function generateVerificationCode(): string
	{
		$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$charactersLength = strlen($characters);
		$randomString = '';

		for ($i = 0; $i < 64; $i++)
		{
			$randomString .= $characters[rand(0, $charactersLength - 1)];
		}

		return $randomString;
	}

	/**
	 * Login the user
	 *
	 * @return void
	 */
	public function loginUser()
	{
		/** IdentityStore */
		$request 		= Injector::inst()->get(HTTPRequest::class);
		$rememberMe 	= (isset($data['Remember']) && Security::config()->get('autologin_enabled'));

		/** @var IdentityStore $identityStore */
		$identityStore 	= Injector::inst()->get(IdentityStore::class);
		$identityStore->logIn($this->owner, $rememberMe, $request);
	}
}
