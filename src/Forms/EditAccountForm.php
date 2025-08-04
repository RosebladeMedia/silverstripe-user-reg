<?php

namespace Roseblade\UserReg\Forms;

use Exception;
use SilverStripe\i18n\i18n;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\TextField;
use SilverStripe\Security\Member;
use SilverStripe\Forms\EmailField;
use SilverStripe\Forms\FormAction;
use SilverStripe\Forms\HiddenField;
use SilverStripe\Security\Security;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\RequiredFields;
use SilverStripe\ORM\ValidationResult;
use SilverStripe\Core\Injector\Injector;

/**
 * Default form for editing Member details
 *
 * @package UserReg
 * @author  i-lateral <info@ilateral.co.uk>
 */
class EditAccountForm extends Form
{

	/**
	 * These fields will be ignored by the `Users_EditAccountForm`
	 * when generating fields
	 * 
	 * @var array
	 */
	private static $ignore_member_fields = [
		"LastVisited",
		"FailedLoginCount",
		"DateFormat",
		"TimeFormat",
		"VerificationCode",
		"Password",
		"HasConfiguredDashboard",
		"URLSegment",
		"BlogProfileSummary",
		"BlogProfileImage",
		"VerificationExpiry",
		"VerifiedTimestamp"
	];

	/**
	 * Setup this form
	 * 
	 * @param Controller $controller Current Controller
	 * @param string     $name       Name of this form
	 * 
	 * @return void
	 */
	public function __construct($controller, $name = "Users_EditAccountForm")
	{
		$member = Injector::inst()->get(Member::class);

		$fields = $member->getFrontEndFields();

		// Switch locale field
		$fields->replaceField(
			'Locale',
			DropdownField::create(
				"Locale",
				$member->fieldLabel("Locale"),
				i18n::getSources()->getKnownLocales()
			)
		);

		$this->extend("updateFormFields", $fields);

		$cancel_url = $controller->Link();

		$actions = FieldList::create(
			LiteralField::create(
				"cancelLink",
				'<a class="btn btn-red" href="' . $cancel_url . '">' . _t("Users.CANCEL", "Cancel") . '</a>'
			),
			FormAction::create("doUpdate", _t("CMSMain.SAVE", "Save"))
				->addExtraClass("btn")
				->addExtraClass("btn-green")
		);

		$hidden_fields = array_merge(
			$member->config()->hidden_fields,
			static::config()->ignore_member_fields
		);

		// Remove all "hidden fields"
		foreach ($hidden_fields as $field_name)
		{
			$fields->removeByName($field_name);
		}

		$this->extend("updateFormActions", $actions);

		$required = RequiredFields::create(
			$member->config()->required_fields
		);

		// // Add the current member ID
		// $fields->add(HiddenField::create("ID"));

		$this->extend("updateRequiredFields", $required);

		parent::__construct(
			$controller,
			$name,
			$fields,
			$actions,
			$required
		);

		$this->extend("updateForm", $this);
	}

	/**
	 * Register a new member
	 *
	 * @param array $data User submitted data
	 * 
	 * @return SS_HTTPResponse
	 */
	public function doUpdate($data)
	{
		$curr = Security::getCurrentUser();

		$this->extend("onBeforeUpdate", $data);

		/** Ensure that the current user is provided (for their login) */
		if (!empty($curr))
		{
			try
			{
				// Save member
				$this->saveInto($curr);
				$curr->write();

				$this->sessionMessage(
					_t("Users.DETAILSUPDATED", "Account details updated"),
					ValidationResult::TYPE_GOOD
				);
			}
			catch (Exception $e)
			{
				$this->sessionMessage(
					$e->getMessage(),
					ValidationResult::TYPE_ERROR
				);
			}
		}
		else
		{
			$this->sessionMessage(
				_t("Users.CANNOTEDIT", "You cannot edit this account"),
				ValidationResult::TYPE_ERROR
			);
		}

		$this->extend("onAfterUpdate", $data);

		return $this
			->getController()
			->redirectBack();
	}
}
