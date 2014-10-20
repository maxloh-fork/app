<?php

class ExactTargetUserHooks {

	/**
	 * Adds Task for updating user name to job queue
	 * @param int $iUserId
	 * @param string $sOldUsername
	 * @param string $sNewUsername
	 * @return bool
	 */
	public function onAfterAccountRename( $iUserId, $sOldUsername, $sNewUsername ) {
		/* Prepare params */
		$aUserData = [
			'user_id' => $iUserId,
			'user_name' => $sNewUsername
		];

		/* Get and run the task */
		$oTaskInstanceHelper = $this->getTaskInstanceHelper();
		$task = $oTaskInstanceHelper->getUpdateUserTask();
		$task->call( 'updateUserData', $aUserData );
		$task->queue();
		return true;
	}

	/**
	 * Adds Task for updating user editcount to job queue
	 * @param WikiPage $article
	 * @param User $user
	 * @return bool
	 */
	public function onArticleSaveComplete( WikiPage $article, User $user ) {
		/* Prepare params */
		$aUserData = [
			'user_id' => $user->getId(),
			'user_editcount' => $user->getEditCount()
		];

		/* Get and run the task */
		$oTaskInstanceHelper = $this->getTaskInstanceHelper();
		$task = $oTaskInstanceHelper->getUpdateUserTask();
		$task->call( 'updateUserData', $aUserData );
		$task->queue();
		return true;
	}

	/**
	 * Adds Task for removing user to job queue
	 * @param User $oUser
	 * @return bool
	 */
	public function onEditAccountClosed( User $oUser ) {
		/* Get and run the task */
		$oTaskInstanceHelper = $this->getTaskInstanceHelper();
		$task = $oTaskInstanceHelper->getDeleteUserTask();
		$task->call( 'deleteUserData', $oUser->getId() );
		$task->queue();
		return true;
	}

	/**
	 * Adds Task to job queue that updates a user or adds a user if one doesn't exist
	 * @param User $oUser
	 * @return bool
	 */
	public function onEditAccountEmailChanged( User $oUser ) {
		$this->addTheUpdateCreateUserTask( $oUser );
		return true;
	}


	/**
	 * Adds Task for updating user email
	 * @param User $user
	 * @return bool
	 */
	public function onEmailChangeConfirmed( User $user ) {
		/* Get and run the task */
		$oTaskInstanceHelper = $this->getTaskInstanceHelper();
		$task = $oTaskInstanceHelper->getUpdateUserTask();
		$task->call( 'updateUserEmail', $user->getId(), $user->getEmail() );
		$task->queue();
		return true;
	}

	/**
	 * Adds Task to job queue that updates a user or adds a user if one doesn't exist
	 * @param User $oUser
	 * @return bool
	 */
	public function onSignupConfirmEmailComplete( User $oUser ) {
		$this->addTheUpdateCreateUserTask( $oUser );
		return true;
	}

	/**
	 * Adds a task for updating user properties
	 * @param User $user
	 * @return bool
	 */
	public function onUserSaveSettings( User $user ) {
		/* Prepare params */
		$oParamsHelper = $this->getParamsHelper();
		$aUserData = $oParamsHelper->prepareUserParams( $user );
		$aUserProperties = $oParamsHelper->prepareUserPropertiesParams( $user );

		/* Get and run the task */
		$oTaskInstanceHelper = $this->getTaskInstanceHelper();
		$task = $oTaskInstanceHelper->getUpdateUserTask();
		$task->call( 'updateUserPropertiesData', $aUserData, $aUserProperties );
		$task->queue();
		return true;
	}

	/**
	 * Adds Task to job queue that updates a user or adds a user if one doesn't exist
	 * @param User $oUser
	 */
	private function addTheUpdateCreateUserTask( User $oUser ) {
		/* Prepare params */
		$oParamsHelper = $this->getParamsHelper();
		$aUserData = $oParamsHelper->prepareUserParams( $oUser );
		$aUserProperties = $oParamsHelper->prepareUserPropertiesParams( $oUser );

		/* Get and run the task */
		$oTaskInstanceHelper = $this->getTaskInstanceHelper();
		$task = $oTaskInstanceHelper->getCreateUserTask();
		$task->call( 'updateCreateUserData', $aUserData, $aUserProperties );
		$task->queue();
	}

	private function getParamsHelper() {
		return new ExactTargetUserHooksParamsHelper();
	}

	private function getTaskInstanceHelper() {
		return new ExactTargetUserHooksTaskInstanceHelper();
	}
}
