<?php
/**
 * Renders edit buttons (with dropdown)
 *
 * @author Maciej Brencz
 */

class MenuButtonModule extends WikiaController {

	const ADD_ICON = 1;
	const EDIT_ICON = 2;
	const LOCK_ICON = 3;
	const BLOG_ICON = 4;
	const MESSAGE_ICON = 5;
	const CONTRIBUTE_ICON = 6;
	const FACEBOOK_ICON = 7;

	/*
	var $wgStylePath;
	var $wgBlankImgUrl;

	var $action;
	var $actionName;
	var $actionAccessKey;
	var $class;
	var $dropdown;
	var $icon;
	var $iconBefore;
	var $loginURL;
	var $loginToEditURL;
	var $loginTitle;
	*/

	public function init() {
		$this->action = null;
		$this->icon = null;
	}

	public function executeIndex($data) {
		global $wgTitle, $wgUser;

		wfProfileIn(__METHOD__);

		if (isset($data['action'])) {
			$this->action = $data['action'];
		}

		// action ID for tracking
		if (isset($data['name'])) {
			$this->actionName = $data['name'];
		}
		else {
			$this->actionName = 'edit';
		}

		$this->actionAccessKey = MenuButtonModule::accessKey($this->actionName);

		// default CSS class
		$this->class = 'wikia-button';
		$img_class = 'icon';
		// render icon
		if (isset($data['image'])) {
			switch($data['image']) {
				case self::ADD_ICON:
					$img_class = 'sprite message';
					$height = 16;
					$width = 21;
					break;

				case self::LOCK_ICON:
					$img_class = 'sprite lock';
					$height = 12;
					$width = 9;
					$this->class = 'view-source';
					break;

				case self::BLOG_ICON:
					$img_class = 'sprite blog';
					$height = 16;
					$width = 22;
					break;

				case self::MESSAGE_ICON:
					$img_class = 'sprite message';
					$height = 16;
					$width = 22;
					break;

				case self::CONTRIBUTE_ICON:
					$img_class = 'sprite contribute';
					$height = 16;
					$width = 22;
					break;

				case self::FACEBOOK_ICON:
					$img_class = 'sprite facebook';
					$height = 14;
					$width = 15;
					break;

				case self::EDIT_ICON:
				default:
					$img_class = 'sprite edit-pencil';
					$height = 16;
					$width = 22;
					break;
			}

			$image = Xml::element('img', array(
				'alt' => '',
				'class' => $img_class,
				'height' => $height,
				'src' => $this->wg->BlankImgUrl,
				'width' => $width,
			));

			$this->icon = $image;
		}

		if (!empty($data['dropdown'])) {

			// add accesskeys for dropdown items
			foreach($data['dropdown'] as $key => &$item) {
				$accesskey = MenuButtonModule::accessKey($key);

				if ($accesskey != false && !isset($item['accesskey'])) {
					$item['accesskey'] = $accesskey;
				}
			}

			$this->class = 'wikia-menu-button';
		}

		// modify edit URL if the action is edit
		if ( $this->actionName == 'edit' &&
			isset($data['action']['href']) /* BugId:12613 */ &&
			!$wgTitle->userCan( 'edit' ) ) {
				$signUpTitle = SpecialPage::getTitleFor('SignUp');
				$loginUrl = $this->createLoginURL(!empty($data['dropdown']) ? 'action=edit' : '');
				$data['action']['href'] = $signUpTitle->getLocalUrl($loginUrl);
				$this->action = $data['action'];
				$this->class .= ' loginToEditProtectedPage';
		}

		if(!empty($data['class'])) {
			$this->class .= ' '.$data['class'];
		}

		$this->id = !empty($data['id']) ? $data['id'] : '';
		$this->tooltip = !empty($data['tooltip']) ? (' data-tooltip="' . htmlspecialchars($data['tooltip']) . '"') : '';
		$this->dropdown = isset($data['dropdown']) ? $data['dropdown'] : false;

		wfProfileOut(__METHOD__);
	}

	/**
	 * @param extraReturntoquery is a string which will be urlencoded and appended to the returntoquery. eg: "action=edit".
	 */
	public function createLoginURL($extraReturntoquery='') {
		global $wgTitle;

		/** create login URL **/
		$returnto = wfGetReturntoParam(null, $extraReturntoquery);

		//$signUpHref = Skin::makeSpecialUrl('Signup', $returnto);
		$signUpHref = $returnto;
		$signUpHref .= "&type=login";
		//$this->loginTitle = Skin::makeSpecialUrl('Signup'); // the linker just expects a page-name here.
		return $signUpHref;
	}

	private static function accessKey($key) {
		$accesskey = false;
		switch($key) {
			case 'addtopic':
				$accesskey = 'a';
				break;
			case 'edit':
				$accesskey = 'e';
				break;
			case 'editprofile':
				$accesskey = 'e';
				break;
			case 'move':
				$accesskey = 'm';
				break;

			case 'protect':
			case 'unprotect':
				$accesskey = '=';
				break;

			case 'delete':
			case 'undelete':
				$accesskey = 'd';
				break;

			case 'history':
				$accesskey = 'h';
				break;

			default:
				$accesskey = false;
		}
		return $accesskey;
	}

}
