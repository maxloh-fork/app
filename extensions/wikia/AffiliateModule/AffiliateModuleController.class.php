<?php

class AffiliateModuleController extends WikiaController {

	const DEFAULT_TEMPLATE_ENGINE = WikiaResponse::TEMPLATE_ENGINE_MUSTACHE;

	/**
	 * AffiliateModule
	 * Returns products to populate the affiliate module.
	 * @requestParam string option [rail/bottom/bottomAds]
	 * @responseParam string title
	 * @responseParam array products - list of products
	 */
	public function index() {
		wfProfileIn( __METHOD__ );

		$this->response->addAsset( 'affiliate_module_css' );
		$this->response->addAsset( 'affiliate_module_js' );

		$products = [
			[
				'name' => 'The Lord of the Rings: The Motion Picture Trilogy (The Fellowship of the Ring / The Two Towers / The Return of the King Extended Editions) [Blu-ray] (2012)',
				'url' => 'http://www.amazon.com/gp/product/B007ZQAKHU/ref=as_li_tl?ie=UTF8&amp;camp=1789&amp;creative=390957&amp;creativeASIN=B007ZQAKHU&amp;linkCode=as2&amp;tag=lotrwikia-mcb-reg-f-20&amp;linkId=NIQYAQCQESVNRNMN',
				'thumbUrl' => 'http://ecx.images-amazon.com/images/I/51wp1eHVwdL._AA160_.jpg',
				'price' => '$68.99',
			],
			[
				'name' => 'The Silmarillion (Pre-Lord of the Rings)',
				'url' => 'http://www.amazon.com/gp/product/0345325818/ref=as_li_tl?ie=UTF8&camp=1789&creative=390957&creativeASIN=0345325818&linkCode=as2&tag=lotrwikia-mcb-reg-f-20&linkId=P6FRCSZGHQ2PIA35',
				'thumbUrl' => 'http://ecx.images-amazon.com/images/I/51tDzXVWy4L._AA160_.jpg',
				'price' => '$5.44',

			],
			[
				'name' => 'J.R.R. Tolkien 4-Book Boxed Set: The Hobbit and The Lord of the Rings (Movie Tie-in): The Hobbit, The Fellowship of the Ring, The Two Towers, The Return of the King',
				'url' => 'http://www.amazon.com/gp/product/0345538374/ref=as_li_tl?ie=UTF8&camp=1789&creative=390957&creativeASIN=0345538374&linkCode=as2&tag=lotrwikia-mcb-reg-f-20&linkId=KRY56KEPJ5XYKDIO',
				'thumbUrl' => 'http://ecx.images-amazon.com/images/I/51GJzbM5vTL._AA160_.jpg',
				'price' => '$21.76',
			],
		];

		$this->title = wfMessage( 'affiliate-module-title' )->escaped();
		$this->products = $products;
		$this->buttonLabel = wfMessage( 'affiliate-module-button-label' )->plain();
		$this->className = ( $this->request->getVal( 'option' ) == 'rail' ) ? 'module' : '';

		wfProfileOut( __METHOD__ );
	}

	/**
	 * Show affiliate module at the bottom of the page
	 */
	public function showModule() {
		if ( AffiliateModuleHelper::canShowModule( 'bottomAds' ) ) {
			$this->wg->HideBottomAds = true;
		}

		if ( !AffiliateModuleHelper::canShowModule() && empty( $this->wg->HideBottomAds ) ) {
			$this->skipRendering();
			return true;
		}

		$this->forward( 'AffiliateModule', 'index' );
	}

}
