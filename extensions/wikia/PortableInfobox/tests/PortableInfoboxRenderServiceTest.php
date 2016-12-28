<?php

class PortableInfoboxRenderServiceTest extends WikiaBaseTest {
	//todo: https://wikia-inc.atlassian.net/browse/DAT-3076
	//todo: we are testing a lot of functionality and have issues with mocking
	//todo: we should move all render service test to API tests

	protected function setUp() {
		$this->setupFile = dirname( __FILE__ ) . '/../PortableInfobox.setup.php';
		parent::setUp();
	}

	/**
	 * @param $input to check presence of some additional config fields. Possible fields:
	 * 'isInvalidImage' - bool - if getThumbnail should return false
	 * 'smallImageDimensions' - integer - size of small image (both width and height)
	 *
	 * @return PHPUnit_Framework_MockObject_MockObject
	 */
	private function mockInfoboxRenderServiceHelper( $input ) {
		$isValidHeroDataItem = isset( $input[ 'isValidHeroDataItem' ] ) && $input[ 'isValidHeroDataItem' ];

		$createHorizontalGroupData = isset( $input[ 'createHorizontalGroupData' ] ) ?
			$input[ 'createHorizontalGroupData' ] : null;
		$extendImageData = isset( $input[ 'extendImageData' ] ) ? $input[ 'extendImageData' ] : null;

		$mock = $this->getMockBuilder( 'Wikia\PortableInfobox\Helpers\PortableInfoboxRenderServiceHelper' )
			->setMethods( [ 'isValidHeroDataItem', 'validateType', 'createHorizontalGroupData', 'extendImageData' ] )
			->getMock();
		$mock->expects( $this->any() )
			->method( 'isValidHeroDataItem' )
			->will( $this->returnValue( $isValidHeroDataItem ) );
		$mock->expects( $this->any() )
			->method( 'validateType' )
			->will( $this->returnValue( true ) );
		$mock->expects( $this->any() )
			->method( 'createHorizontalGroupData' )
			->will( $this->returnValue( $createHorizontalGroupData ) );
		$mock->expects( $this->any() )
			->method( 'extendImageData' )
			->will( $this->returnValue( $extendImageData ) );

		$this->mockClass( 'Wikia\PortableInfobox\Helpers\PortableInfoboxRenderServiceHelper', $mock );
	}

	/**
	 * @param $html
	 * @return string
	 */
	private function normalizeHTML( $html ) {
		$DOM = new DOMDocument( '1.0' );
		$DOM->formatOutput = true;
		$DOM->preserveWhiteSpace = false;
		$DOM->loadXML( $html );

		return $DOM->saveXML();
	}

	public function testEuropaThemeEnabled() {
		$wrapper = new \Wikia\Util\GlobalStateWrapper( [ 'wgEnablePortableInfoboxEuropaTheme' => true ] );

		$infoboxRenderService = new PortableInfoboxRenderService();
		$output = $wrapper->wrap( function () use ( $infoboxRenderService ) {
			return $infoboxRenderService->renderInfobox(
				[ [ 'type' => 'title', 'data' => [ 'value' => 'Test' ] ] ], '', '', '', '' );
		} );

		$expected = $this->normalizeHTML( '<aside class="portable-infobox pi-background pi-europa">
								<h2 class="pi-item pi-item-spacing pi-title">Test</h2>
							</aside>' );
		$result = $this->normalizeHTML( $output );
		$this->assertEquals( $expected, $result );
	}

	/**
	 * @param $input
	 * @param $expectedOutput
	 * @param $description
	 * @param $mockParams
	 * @dataProvider testRenderInfoboxDataProvider
	 */
	public function testRenderInfobox( $input, $expectedOutput, $description, $mockParams, $accentColor, $accentColorText ) {
		$this->mockInfoboxRenderServiceHelper( $mockParams );

		$infoboxRenderService = new PortableInfoboxRenderService();
		$actualOutput = $infoboxRenderService->renderInfobox( $input, '', '', $accentColor, $accentColorText );
		$expectedHtml = $this->normalizeHTML( $expectedOutput );
		$actualHtml = $this->normalizeHTML( $actualOutput );

		$this->assertEquals( $expectedHtml, $actualHtml, $description );
	}

	public function testRenderInfoboxDataProvider() {
		return [
			[
				'input' => [ ],
				'output' => '',
				'description' => 'Empty data should yield no infobox markup'
			],
			[
				'input' => [
					[
						'type' => 'title',
						'data' => [
							'value' => 'Test Title'
						]
					]
				],
				'output' => '<aside class="portable-infobox pi-background">
								<h2 class="pi-item pi-item-spacing pi-title">Test Title</h2>
							</aside>',
				'description' => 'Only title'
			],
			[
				'input' => [
					[
						'type' => 'title',
						'data' => [
							'value' => 'Test Title'
						]
					]
				],
				'output' => '<aside class="portable-infobox pi-background">
								<h2 class="pi-item pi-item-spacing pi-title" style="background-color:#FFF;color:#000;">Test Title</h2>
							</aside>',
				'description' => 'Only title with custom colors',
				'mockParams' => [],
				'accentColor' => '#FFF',
				'accentColorText' => '#000'
			],
			[
				'input' => [
					[
						'type' => 'image',
						'data' => [
							[
								'alt' => 'image alt',
								'url' => 'http://image.jpg',
								'name' => 'image',
								'key' => 'image',
								'caption' => 'Lorem ipsum dolor',
								'isVideo' => false
							]
						]
					]
				],
				'output' => '<aside class="portable-infobox pi-background">
								<figure class="pi-item pi-image">
									<a href="http://image.jpg" class="image image-thumbnail" title="image alt">
										<img src="http://thumbnail.jpg" srcset="http://thumbnail.jpg 1x, http://thumbnail2x.jpg 2x" class="pi-image-thumbnail" alt="image alt"
										width="400" height="200" data-image-key="image" data-image-name="image"/>
									</a>
									<figcaption class="pi-item-spacing pi-caption">Lorem ipsum dolor</figcaption>
								</figure>
							</aside>',
				'description' => 'Only image',
				'mockParams' => [
					'extendImageData' => [
						'alt' => 'image alt',
						'url' => 'http://image.jpg',
						'caption' => 'Lorem ipsum dolor',
						'name' => 'image',
						'key' => 'image',
						'width' => '400',
						'height' => '200',
						'thumbnail' => 'http://thumbnail.jpg',
						'thumbnail2x' => 'http://thumbnail2x.jpg',
						'media-type' => 'image',
						'isVideo' => false
					]
				]
			],
			[
				'input' => [
					[
						'type' => 'image',
						'data' => [
							[
								'alt' => 'image alt',
								'url' => 'http://image.jpg',
								'caption' => 'Lorem ipsum dolor',
								'isVideo' => true,
								'duration' => '1:20',
								'name' => 'test',
								'key' => 'test'
							]
						]
					]
				],
				'output' => '<aside class="portable-infobox pi-background">
								<figure class="pi-item pi-image">
									<a href="http://image.jpg"
									class="image image-thumbnail video video-thumbnail small"
									title="image alt">
										<img src="http://thumbnail.jpg" srcset="http://thumbnail.jpg 1x, http://thumbnail2x.jpg 2x" class="pi-image-thumbnail"
										alt="image alt" width="400" height="200" data-video-key="image"
										data-video-name="image"/>
										<span class="duration" itemprop="duration">1:20</span>
										<span class="play-circle"></span>
									</a>
									<figcaption class="pi-item-spacing pi-caption">Lorem ipsum dolor</figcaption>
								</figure>
							</aside>',
				'description' => 'Only video',
				'mockParams' => [
					'extendImageData' => [
						'alt' => 'image alt',
						'url' => 'http://image.jpg',
						'caption' => 'Lorem ipsum dolor',
						'name' => 'image',
						'key' => 'image',
						'width' => '400',
						'height' => '200',
						'thumbnail' => 'http://thumbnail.jpg',
						'thumbnail2x' => 'http://thumbnail2x.jpg',
						'media-type' => 'video',
						'isVideo' => true,
						'duration' => '1:20'
					]
				]
			],
			[
				'input' => [
					[
						'type' => 'navigation',
						'data' => [
							'value' => 'navigation value',
						]
					]
				],
				'output' => '<aside class="portable-infobox pi-background">
								<nav class="pi-navigation pi-item-spacing pi-secondary-background pi-secondary-font">navigation value</nav>
							</aside>',
				'description' => 'navigation only'
			],
			[
				'input' => [
					[
						'type' => 'data',
						'data' => [
							'label' => 'test label',
							'value' => 'test value'
						]
					]
				],
				'output' => '<aside class="portable-infobox pi-background">
								<div class="pi-item pi-data pi-item-spacing pi-border-color">
									<h3 class="pi-data-label pi-secondary-font">test label</h3>
									<div class="pi-data-value pi-font">test value</div>
								</div>
							</aside>',
				'description' => 'Only pair'
			],
			[
				'input' => [
					[
						'type' => 'title',
						'data' => [
							'value' => 'Test Title'
						]
					],
					[
						'type' => 'image',
						'data' => [
							[
								'alt' => 'image alt',
								'url' => 'http://image.jpg',
								'name' => 'image',
								'key' => 'image',
								'isVideo' => false
							]
						]
					],
					[
						'type' => 'data',
						'data' => [
							'label' => 'test label',
							'value' => 'test value'
						]
					]
				],
				'output' => '<aside class="portable-infobox pi-background">
								<h2 class="pi-item pi-item-spacing pi-title">Test Title</h2>
								<figure class="pi-item pi-image">
									<a href="http://image.jpg" class="image image-thumbnail" title="image alt">
										<img src="http://thumbnail.jpg" srcset="http://thumbnail.jpg 1x, http://thumbnail2x.jpg 2x" class="pi-image-thumbnail" alt="image alt"
										width="400" height="200" data-image-key="image" data-image-name="image"/>
									</a>
								</figure>
								<div class="pi-item pi-data pi-item-spacing pi-border-color">
									<h3 class="pi-data-label pi-secondary-font">test label</h3>
									<div class="pi-data-value pi-font">test value</div>
									</div>
							</aside>',
				'description' => 'Simple infobox with title, image and key-value pair',
				'mockParams' => [
					'extendImageData' => [
						'alt' => 'image alt',
						'url' => 'http://image.jpg',
						'name' => 'image',
						'key' => 'image',
						'width' => '400',
						'height' => '200',
						'thumbnail' => 'http://thumbnail.jpg',
						'thumbnail2x' => 'http://thumbnail2x.jpg',
						'media-type' => 'image',
						'isVideo' => false
					]
				]
			],
			[
				'input' => [
					[
						'type' => 'title',
						'data' => [
							'value' => 'Test Title'
						]
					],
					[
						'type' => 'image',
						'data' => [ ]
					],
					[
						'type' => 'data',
						'data' => [
							'label' => 'test label',
							'value' => 'test value'
						]
					]
				],
				'output' => '<aside class="portable-infobox pi-background">
								<h2 class="pi-item pi-item-spacing pi-title">Test Title</h2>
								<div class="pi-item pi-data pi-item-spacing pi-border-color">
									<h3 class="pi-data-label pi-secondary-font">test label</h3>
									<div class="pi-data-value pi-font">test value</div>
									</div>
							</aside>',
				'description' => 'Simple infobox with title, INVALID image and key-value pair'
			],
			[
				'input' => [
					[
						'type' => 'title',
						'data' => [
							'value' => 'Test Title'
						]
					],
					[
						'type' => 'data',
						'data' => [
							'label' => 'test label',
							'value' => 'test value'
						]
					]
				],
				'output' => '<aside class="portable-infobox pi-background">
								<h2 class="pi-item pi-item-spacing pi-title">Test Title</h2>
								<div class="pi-item pi-data pi-item-spacing pi-border-color">
									<h3 class="pi-data-label pi-secondary-font">test label</h3>
									<div class="pi-data-value pi-font">test value</div>
								</div>
							</aside>',
				'description' => 'Simple infobox with title, empty image and key-value pair'
			],
			[
				'input' => [
					[
						'type' => 'title',
						'data' => [
							'value' => 'Test Title'
						]
					],
					[
						'type' => 'group',
						'data' => [
							'value' => [
								[
									'type' => 'header',
									'data' => [
										'value' => 'Test Header'
									]
								],
								[
									'type' => 'data',
									'data' => [
										'label' => 'test label',
										'value' => 'test value'
									]
								],
								[
									'type' => 'data',
									'data' => [
										'label' => 'test label',
										'value' => 'test value'
									]
								]
							]
						]
					]
				],
				'output' => '<aside class="portable-infobox pi-background">
								<h2 class="pi-item pi-item-spacing pi-title">Test Title</h2>
								<section class="pi-item pi-group pi-border-color">
									<h2 class="pi-item pi-header pi-secondary-font pi-item-spacing pi-secondary-background">Test Header</h2>
									<div class="pi-item pi-data pi-item-spacing pi-border-color">
										<h3 class="pi-data-label pi-secondary-font">test label</h3>
										<div class="pi-data-value pi-font">test value</div>
									</div>
									<div class="pi-item pi-data pi-item-spacing pi-border-color">
										<h3 class="pi-data-label pi-secondary-font">test label</h3>
										<div class="pi-data-value pi-font">test value</div>
									</div>
								</section>
							</aside>',
				'description' => 'Infobox with title, group with header and two key-value pairs'
			],
			[
				'input' => [
					[
						'type' => 'title',
						'data' => [
							'value' => 'Test Title'
						]
					],
					[
						'type' => 'group',
						'data' => [
							'value' => [
								[
									'type' => 'header',
									'data' => [
										'value' => 'Test Header'
									]
								],
								[
									'type' => 'data',
									'data' => [
										'label' => 'test label',
										'value' => 'test value'
									]
								],
								[
									'type' => 'data',
									'data' => [
										'label' => 'test label',
										'value' => 'test value'
									]
								]
							]
						]
					]
				],
				'output' => '<aside class="portable-infobox pi-background">
								<h2 class="pi-item pi-item-spacing pi-title" style="background-color:#FFF;color:#000;">Test Title</h2>
								<section class="pi-item pi-group pi-border-color">
									<h2 class="pi-item pi-header pi-secondary-font pi-item-spacing pi-secondary-background" style="background-color:#FFF;color:#000;">Test Header</h2>
									<div class="pi-item pi-data pi-item-spacing pi-border-color">
										<h3 class="pi-data-label pi-secondary-font">test label</h3>
										<div class="pi-data-value pi-font">test value</div>
									</div>
									<div class="pi-item pi-data pi-item-spacing pi-border-color">
										<h3 class="pi-data-label pi-secondary-font">test label</h3>
										<div class="pi-data-value pi-font">test value</div>
									</div>
								</section>
							</aside>',
				'description' => 'Infobox with title, group with header and two key-value pairs, custom accent color and accent text color',
				'mockParams' => [],
				'accentColor' => '#FFF',
				'accentColorText' => '#000'
			],
			[
				'input' => [
					[
						'type' => 'group',
						'data' => [
							'value' => [
								[
									'type' => 'header',
									'data' => [
										'value' => 'Test header'
									]
								],
								[
									'type' => 'data',
									'data' => [
										'label' => 'test label',
										'value' => 'test value'
									]
								],
								[
									'type' => 'data',
									'data' => [
										'label' => 'test label',
										'value' => 'test value'
									]
								]
							],
							'layout' => 'horizontal'
						]
					]
				],
				'output' => '<aside class="portable-infobox pi-background">
								<section class="pi-item pi-group pi-border-color">
									<table class="pi-horizontal-group">
										<caption
										class="pi-header pi-secondary-font pi-secondary-background pi-item-spacing">Test header</caption>
										<thead>
											<tr>
												<th
												class="pi-horizontal-group-item pi-data-label pi-secondary-font pi-border-color pi-item-spacing">test label</th>
												<th
												class="pi-horizontal-group-item pi-data-label pi-secondary-font pi-border-color pi-item-spacing">test label</th>
											</tr>
										</thead>
										<tbody>
											<tr>
												<td
												class="pi-horizontal-group-item pi-data-value pi-font pi-border-color pi-item-spacing">test value</td>
												<td
												class="pi-horizontal-group-item pi-data-value pi-font pi-border-color pi-item-spacing">test value</td>
											</tr>
										</tbody>
									</table>
								</section>
							</aside>',
				'description' => 'Infobox with horizontal group',
				'mockParams' => [
					'createHorizontalGroupData' => [
						'header' => 'Test header',
						'labels' => [ 'test label', 'test label' ],
						'values' => [ 'test value', 'test value' ],
						'renderLabels' => true
					]
				]
			],
			[
				'input' => [
					[
						'type' => 'group',
						'data' => [
							'value' => [
								[
									'type' => 'data',
									'data' => [
										'label' => '',
										'value' => 'test value'
									]
								],
								[
									'type' => 'data',
									'data' => [
										'label' => '',
										'value' => 'test value'
									]
								]
							],
							'layout' => 'horizontal'
						]
					]
				],
				'output' => '<aside class="portable-infobox pi-background">
								<section class="pi-item pi-group pi-border-color">
									<table class="pi-horizontal-group pi-horizontal-group-no-labels">
										<tbody>
											<tr>
												<td
												class="pi-horizontal-group-item pi-data-value pi-font pi-border-color pi-item-spacing">test value</td>
												<td
												class="pi-horizontal-group-item pi-data-value pi-font pi-border-color pi-item-spacing">test value</td>
											</tr>
										</tbody>
									</table>
								</section>
							</aside>',
				'description' => 'Infobox with horizontal group without header and labels',
				'mockParams' => [
					'createHorizontalGroupData' => [
						'labels' => [ '', '' ],
						'values' => [ 'test value', 'test value' ],
						'renderLabels' => false
					]
				]
			],
			[
				'input' => [
					[
						'type' => 'navigation',
						'data' => [
							'value' => '<p>Links</p>'
						]
					]
				],
				'output' => '<aside class="portable-infobox pi-background">
								<nav class="pi-navigation pi-item-spacing pi-secondary-background pi-secondary-font">
									<p>Links</p>
								</nav>
							</aside>',
				'description' => 'Infobox with navigation'
			]
		];
	}
}
