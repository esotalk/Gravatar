<?php
// Copyright 2013 Toby Zerner, Simon Zerner
// This file is part of esoTalk. Please see the included license file for usage information.

if (!defined("IN_ESOTALK")) exit;

ET::$pluginInfo["Gravatar"] = array(
	"name" => T("Gravatar"),
	"description" => T("Allows users to choose to use their Gravatar."),
	"version" => ESOTALK_VERSION,
	"author" => "Toby Zerner",
	"authorEmail" => "support@esotalk.org",
	"authorURL" => "http://esotalk.org",
	"license" => "GPLv2",
	"dependencies" => array(
		"esoTalk" => "1.0.0g4"
	)
);

class ETPlugin_Gravatar extends ETPlugin {

	function init()
	{
		// Override the avatar function.

		/**
		 * Return an image tag containing a member's avatar.
		 *
		 * @param array $member An array of the member's details. (email is required in this implementation.)
		 * @param string $avatarFormat The format of the member's avatar (as stored in the database - jpg|gif|png.)
		 * @param string $className CSS class names to apply to the avatar.
		 */
		function avatar($member = array(), $className = "")
		{
			$default = C("plugin.Gravatar.default") ? C("plugin.Gravatar.default") : "mm";
			$forceGravatar = C("plugin.Gravatar.forceGravatar") ? C("plugin.Gravatar.forceGravatar") : 0;
			$useGravatar = isset($member["preferences"]["gravatar.useGravatar"]) ? $member["preferences"]["gravatar.useGravatar"] : 0;
			$protocol = C("esoTalk.https") ? "https" : "http";
			
			if ($useGravatar || $forceGravatar || empty($member["avatarFormat"])) {
				$url = "$protocol://www.gravatar.com/avatar/".md5(strtolower(trim($member["email"])))."?d=".urlencode($default)."&s=64";
				return "<img src='$url' alt='' class='avatar $className'/>";
			} else {
				// FIXME: This is copy/pasted from the original avatar() function. A
				// better way to override this is needed, in case avatar display mechanism
				// changes at some point.
				// Construct the avatar path from the provided information.
				if (!empty($member["memberId"]) and !empty($member["avatarFormat"])) {
					$file = "uploads/avatars/{$member["memberId"]}.{$member["avatarFormat"]}";
					$url = getWebPath($file);
					return "<img src='$url' alt='' class='avatar $className'/>";
				}
				// Default to an avatar with the first letter of the member's name.
				return "<span class='avatar $className'>".(!empty($member["username"]) ? mb_strtoupper(mb_substr($member["username"], 0, 1, "UTF-8"), "UTF-8") : "&nbsp;")."</span>";
			}
		}
	}
	
	/**
	 * Initializes the settings controller
	 * 
	 * @param type $controller
	 */
	public function handler_settingsController_init($controller) {
		$controller->addJSFile( $this->resource("gravatar.js") );
	}

	/**
	 * Adds Gravatar option to preferences page, possibly removes the avatar
	 * upload field.
	 * 
	 * @param type $sender
	 * @param ETForm $form The form object.
	 */
	public function handler_settingsController_initGeneral($sender, $form)
	{
		$forceGravatar = C("plugin.Gravatar.forceGravatar") ? C("plugin.Gravatar.forceGravatar") : 0;
		
		if ($forceGravatar == 1 ) {
			// Don't confuse members with the default avatar when they can't
			// do anything with it anyway.
			$form->removeField("avatar", "avatar");
		} else {
			$form->setValue("useGravatar", ET::$session->preference("gravatar.useGravatar"));
			$form->addField("avatar", "useGravatar", array($this, "fieldUseGravatar"), array($this, "saveGravatarPreference"));						
		}
		
		$form->addField("avatar", "gravatarLink", array($this, "fieldGravatar"));
	}

	/**
	 * Generates a link (and accompanying text) pointing to gravatar.com
	 * 
	 * @param ETForm $form The form object.
	 * @return string
	 */
	public function fieldGravatar($form)
	{
		return "<div class='gravatarLink' id='gravatarLink'>".
			sprintf(T("Change your avatar on %s."), "<a href='http://gravatar.com' target='_blank'>Gravatar.com</a>") .
		"</div>";
	}
	
	/**
	 * Generates a checkbox for Gravatar preference.
	 * 
	 * @param ETForm $form The form object.
	 * @return string
	 */
	public function fieldUseGravatar($form)
	{
		// We fetch the URL of the user-defined avatar here. This is then used
		// in gravatar.js to display the original image when a member unchecks
		// the "Use Gravatar..." option. Because it was damn confusing when it
		// showed the Gravatar. "Wait, do I need to re-upload my picture?"
		$user = ET::$session->user;
		$avatarFormat = isset($user["avatarFormat"]) ? $user["avatarFormat"] : "";
		$memberId = isset($user["memberId"]) ? $user["memberId"] : "";
		$dataAttr = ($avatarFormat && $memberId) ? $this->getAvatarSrc($memberId, $avatarFormat) : "" ;
		
		return "<label class='checkbox'>" .
		        $form->checkbox("useGravatar", array("id" => "gravatarToggle", "data-gravatar-orig" => $dataAttr))." ".
		        T("Use Gravatar instead of your own image")."</label> " .
			      "<div class='gravatarNotice'><small>(" .
			      T("Note: This setting has no effect if you haven't uploaded your own image.") .
		        ")</small></div>";
	}
	
	/**
	 * Saves the user's preference of using Gravatar or an image of their own.
	 * Couldn't get the saveBoolPreference() method to work so added this.
	 * 
	 * @param ETForm $form The form object.
	 * @param string $key The name of the field that was submitted.
	 * @param array $preferences An array of preferences to write to the database.
	 * @return void
	 */
	public function saveGravatarPreference($form, $key, &$preferences) {
		$preferences['gravatar.useGravatar'] = (bool)$form->getValue($key);
	}

	/**
	 * Construct and process the settings form for this skin, and return the path to the view that should be
	 * rendered.
	 *
	 * @param ETController $sender The page controller.
	 * @return string The path to the settings view to render.
	 */
	public function settings($sender)
	{
		// Set up the settings form.
		$form = ETFactory::make("form");
		$form->action = URL("admin/plugins/settings/Gravatar");
		$form->setValue("default", C("plugin.Gravatar.default", "mm"));
		$form->setValue("forceGravatar", C("plugin.Gravatar.forceGravatar", 0));

		// If the form was submitted...
		if ($form->validPostBack("save")) {

			// Construct an array of config options to write.
			$config = array();
			$config["plugin.Gravatar.default"] = $form->getValue("default");
			$config["plugin.Gravatar.forceGravatar"] = $form->getValue("forceGravatar");

			if (!$form->errorCount()) {

				// Write the config file.
				ET::writeConfig($config);

				$sender->message(T("message.changesSaved"), "success autoDismiss");
				$sender->redirect(URL("admin/plugins"));

			}
		}

		$sender->data("gravatarSettingsForm", $form);
		return $this->view("settings");
	}
	
	/**
	 * Generates the URL of an avatar image for given member ID and avatar
	 * format.
	 * 
	 * @param integer $memberId    The ID of current member
	 * @param string $avatarFormat The avatar image format of current member
	 * @return string              URL to the avatar image of current member
	 */
	protected function getAvatarSrc($memberId, $avatarFormat) {
		return getWebPath("uploads/avatars/$memberId.$avatarFormat");
	}
	
	
}
