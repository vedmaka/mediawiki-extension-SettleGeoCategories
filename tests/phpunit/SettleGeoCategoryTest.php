<?php

/**
 * @group Database
 * @group medium
 */
class SettleGeoCategoryTest extends MediaWikiTestCase {

	public function testConstruct() {
		$category = new SettleGeoCategory();
		$this->assertEquals(null, $category->getId());
	}

}