<?php

class PortableInfoboxBuilderController extends WikiaController {

	const INFOBOX_BUILDER_PARAM = 'portableInfoboxBuilder';

	public function getAssets() {
		$response = $this->getResponse();
		$response->setFormat( WikiaResponse::FORMAT_JSON );
		$response->setVal( 'css', AssetsManager::getInstance()->getURL( 'portable_infobox_scss' ) );
	}

	public function publish() {
		$response = $this->getResponse();
		$response->setFormat( WikiaResponse::FORMAT_JSON );

		$status = $this->attemptSave( $this->getRequest()->getParams() );

		$response->setVal( 'success', $status->isOK() );
		$response->setVal( 'errors', $status->getErrorsArray() );
		$response->setVal( 'warnings', $status->getWarningsArray() );
	}

	private function attemptSave( $params ) {
		$status = new Status();
		// check for title
		if ( !$params[ 'title' ] ) {
			$status->fatal( 'no-title-provided' );
		}
		$title = $status->isGood() ? Title::newFromText( $params[ 'title' ], NS_TEMPLATE ) : false;
		if ( $status->isGood() && !$title ) {
			$status->fatal( 'no-title-object' );
		}
		if ( $status->isGood() && !$title->userCan( 'edit' ) ) {
			$status->fatal( 'user-cant-edit' );
		}

		return $status->isGood() ? $this->save( $title, $params[ 'data' ] ) : $status;
	}

	private function save( Title $title, $data ) {
		$article = new Article( $title );
		$editPage = new EditPage( $article );
		$editPage->initialiseForm();
		$editPage->edittime = $article->getTimestamp();
		$editPage->textbox1 = ( new PortableInfoboxBuilderService() )->translate( $data );
		$status = $editPage->internalAttemptSave( $result );
		return $status;
	}
}
