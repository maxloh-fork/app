<?php

include_once dirname( __FILE__ ) . '/../../' . "providers/StarWarsDataProvider.class.php";

class StarWarsDataProviderTest extends WikiaBaseTest {

	/**
	 * @covers StarWarsDataProvider::canBeTitle
	 * @dataProvider testCanBeTitle_Provider
	 */
	public function testCanBeTitle( $text, $expectedResult ) {
		$dataProvider = new StarWarsDataProvider();
		$canBeTitle = self::getFn( $dataProvider, 'canBeTitle' );
		$this->assertEquals( $expectedResult, $canBeTitle( $text ) );
	}

	/**
	 * @covers StarWarsDataProvider::cleanDescription
	 * @dataProvider testCleanDescription_Provider
	 */
	public function testCleanDescription( $description, $cleanedDescription ) {
		$dataProvider = new StarWarsDataProvider();
		$cleanDescription = self::getFn( $dataProvider, 'cleanDescription' );
		$this->assertEquals( $cleanedDescription, $cleanDescription( $description ) );
	}

	public function testCanBeTitle_Provider() {
		return [
			[ 'Hello World', true ],
			[ 'hello world', false ],
			[ '123', false ],
			[ 'StarWars.com', false ],
			[ 'Read more...', false ],
			[ 'Read More...', false ],
			[ 'read more', false ],
		];
	}

	public function testCleanDescription_Provider() {
		return [
			[
				'In celebration of Star Wars Day, Lucasfilm releases the first full-length trailer for the upcoming animated series Star Wars Rebels. Watch it here…',
				'In celebration of Star Wars Day, Lucasfilm releases the first full-length trailer for the upcoming animated series Star Wars Rebels.'
			],
			[
				'StarWars.com announces the main cast for Episode VII, which includes six members of the original trilogy cast as well as seven actors new to Star Wars. Read more…',
				'StarWars.com announces the main cast for Episode VII, which includes six members of the original trilogy cast as well as seven actors new to Star Wars.'
			],
			[
				'In celebration of Star Wars Day, Lucasfilm releases the first full-length trailer for the upcoming animated series Star Wars Rebels. Watch here…',
				'In celebration of Star Wars Day, Lucasfilm releases the first full-length trailer for the upcoming animated series Star Wars Rebels.'
			],
			[
				'In celebration of Star Wars Day, Lucasfilm releases the first full-length trailer for the upcoming animated series Star Wars Rebels. Watch here...',
				'In celebration of Star Wars Day, Lucasfilm releases the first full-length trailer for the upcoming animated series Star Wars Rebels.'
			],
			[
				'StarWars.com announces the main cast for Episode VII, which includes six members of the original trilogy cast as well as seven actors new to Star Wars. Read More...',
				'StarWars.com announces the main cast for Episode VII, which includes six members of the original trilogy cast as well as seven actors new to Star Wars.'
			],
			[
				'Hello World',
				'Hello World'
			],
			[
				'Without dot read more',
				'Without dot read more'
			],
			[
				'Without dot Read more',
				'Without dot Read more'
			],
		];
	}

	protected static function getFn($obj, $name) {
		$class = new ReflectionClass(get_class($obj));
		$method = $class->getMethod($name);
		$method->setAccessible(true);

		return function () use ( $obj, $method ) {
			$args = func_get_args();
			return $method->invokeArgs( $obj, $args );
		};
	}

}