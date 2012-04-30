<?php

/**
 * Handles fetching:
 *  - output of Nirvana controllers (HTML / JSON)
 *  - AssetsManager packages
 *  - SASS files
 *  - JS messages
 *  in a single request as JSON object
 *
 * @author Macbre
 */

class AssetsManagerController extends WikiaController {
	const MEMCKEY_PREFIX = 'multitypepackage';
	const MEMC_TTL = 604800;

	/**
	 * Return different type of assets in a single request
	 *
	 * @requestParam string templates - JSON encoded array of controllerName / methodName and optional params used to render a template
	 * @requestParam string styles - comma-separated list of SASS files
	 * @requestParam string scripts - comma-separated list of AssetsManager groups
	 * @requestParam string messages - comma-separated list of JSMessages packages
	 * @requestParam integer ttl - cache period for varnish and browser (in seconds),
	 * no caching will be used if not specified or 0, this value is overridden by varnishTTL
	 * and browserTTL respectively for the Varnish part and the Browser part
	 * @requestParam integer varnishTTL - cache period for varnish (in seconds)
	 * @requestParam integer browserTTL - cache period for varnish (in seconds)
	 * 
	 * @responseParam array templates - rendered templates (either HTML or JSON encoded string)
	 * @responseParam array styles - minified styles
	 * @responseParam array scripts - minified AssetsManager  packages
	 * @responseParam array messages - JS messages
	 */
	public function getMultiTypePackage() {
		$this->wf->profileIn( __METHOD__ );

		$key = null;
		$data = null;
		$templates = $this->request->getVal( 'templates', null );
		$styles = $this->request->getVal( 'styles', null );
		$scripts = $this->request->getVal( 'scripts', null );
		$messages = $this->request->getVal( 'messages', null );
		$ttl = $this->request->getInt( 'ttl', 0 );
		$varnishTTL = $this->request->getInt( 'varnishTTL', $ttl );
		$browserTTL = $this->request->getInt( 'browserTTL', $ttl );

		// handle templates via sendRequest
		if ( !is_null( $templates ) ) {
			$profileId = __METHOD__ . "::templates::{$templates}";
			$this->wf->profileIn( $profileId );
			$templates = json_decode( $templates, true /* $assoc */ );
			$templatesOutput = array();

			foreach( $templates as $template ) {
				$params = !empty( $template['params'] ) ? $template['params'] : array();
				$res = $this->sendRequest( $template['controllerName'], $template['methodName'], $params );
				$templatesOutput["{$template['controllerName']}_{$template['methodName']}"] = $res->__toString();
			}

			$this->response->setVal( 'templates', $templatesOutput );
			$this->wf->profileOut( $profileId );
		}

		// handle SASS files
		
		if ( !is_null( $styles ) ) {
			$profileId = __METHOD__ . "::styles::{$styles}";
			$this->wf->profileIn( $profileId );

			$key = $this->getComponentMemcacheKey( $styles );
			$data = $this->wg->Memc->get( $key );

			if ( empty( $data ) ) {
				$styleFiles = explode( ',', $styles );
				$data = '';
	
				foreach( $styleFiles as $styleFile ) {
					$builder = $this->getBuilder( 'sass', $styleFile );
	
					if ( !is_null( $builder ) ) {
						 $data .= $builder->getContent();
					}
				}

				$this->wg->Memc->set( $key, $data, self::MEMC_TTL );
			}

			$this->response->setVal('styles', $data);
			$this->wf->profileOut( $profileId );
		}

		// handle assets manager JS packages

		if ( !is_null( $scripts ) ) {
			$profileId = __METHOD__ . "::scripts::{$scripts}";
			$this->wf->profileIn( $profileId );

			$key = $this->getComponentMemcacheKey( $scripts );
			$data = $this->wg->Memc->get( $key );

			if ( empty( $data ) ) {
				$scriptPackages = explode( ',', $scripts );
				$data = array();
	
				foreach( $scriptPackages as $package ) {
					$builder = $this->getBuilder( 'group', $package );
	
					if ( !is_null( $builder ) ) {
						 $data[] = $builder->getContent();
					}
				}

				$this->wg->Memc->set( $key, $data, self::MEMC_TTL );
			}

			$this->response->setVal( 'scripts', $data );
			$this->wf->profileOut( $profileId );
		}

		// handle JSMessages
		if ( !is_null( $messages ) ) {
			$profileId = __METHOD__ . "::messages::{$messages}";
			$this->wf->profileIn( $profileId );

			$messagePackages = explode( ',', $messages );

			$this->response->setVal( 'messages', F::getInstance( 'JSMessages' )->getPackages( $messagePackages ) );
			$this->wf->profileOut( $profileId );
		}

		// handle cache time
		if ( $varnishTTL > 0 ) {
			$this->response->setCacheValidity( $varnishTTL, $varnishTTL, array( WikiaResponse::CACHE_TARGET_VARNISH ) );
		}

		if ( $browserTTL > 0 ) {
			$this->response->setCacheValidity( $browserTTL, $browserTTL, array( WikiaResponse::CACHE_TARGET_BROWSER ) );
		}

		$this->response->setFormat( 'json' );
		$this->wf->profileOut( __METHOD__ );
	}

	private function getComponentMemcacheKey( $par ) {
		return self::MEMCKEY_PREFIX . '::' . md5( $par ) . '::' . $this->wg->StyleVersion;
	}

	/**
	 * Purges Varnish and Memcached data mapping to the specified set of paramenters
	 * 
	 * @param array $options @see getMultiTypePackage
	 */
	public function purgeMultiTypePackageCache( Array $options ) {
		SquidUpdate::purge( array ( F::build( 'AssetsManager', array(), 'getInstance' )->getMultiTypePackageURL( $options ) ) );
	}

	/**
	 * Returns instance of AssetsManager builder handling given type of assets
	 *
	 * @param string $type assets type ('one', 'group', 'groups', 'sass')
	 * @param string $oid assets / group name
	 * @return AssetsManagerBaseBuilder instance of builder
	 */
	private function getBuilder( $type, $oid ) {
		$type = ucfirst($type);

		// TODO: add a factory method to AssetsManager
		$className = "AssetsManager{$type}Builder";

		if (class_exists($className)) {
			$request = new WebRequest();
			$request->setVal('oid', $oid);

			$builder = F::build($className, array($request));
			return $builder;
		}
		else {
			return null;
		}
	}
}