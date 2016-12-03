<?php

/**
 * Represents single category that contains geographical restrictions or not (default).
 * Implements iterator interface.
 */
class SettleGeoCategory {

	static $table = 'settlegeocategories';
	//static $table_links = 'settlegeocategories_links';

	/** @var int */
	private $id = null;

	/** @var string */
	private $title_key = null;

	/** @var int */
	private $geo_scope = null;

	/** @var string */
	private $description = null;

	/** @var string */
	private $image = null;

	/** @var SettleGeoCategory[] */
	private $children = null;

	/** @var int */
	private $parent_id = null;

	public function __construct($id = null) {
		$this->geo_scope = SettleGeoCategories::GEO_SCOPE_DEFAULT;
		$this->children = array();
		// Only load from DB if we ID was passed to the constructor
		if( $id !== null ) {
			$this->loadFromDatabase( $id );
		}
	}
	
	/**
	 * @param string $title_key
	 * @return null|SettleGeoCategory
	 */
	public static function newFromTitleKey( $title_key ) {
		$dbr = wfGetDB(DB_SLAVE);
		$row = $dbr->selectRow( self::$table, 'id', array('title_key' => trim($title_key)) );
		if( $row ) {
			return new self($row->id);
		}
		return null;
	}

	private function loadFromDatabase($id) {
		$dbr = wfGetDB(DB_SLAVE);
		// Load this category information
		$row = $dbr->selectRow( self::$table, '*', array('id' => $id) );
		if( !$row ) {
			throw new Exception('There is no category with provided ID');
		}
		$this->id = $row->id;
		$this->title_key = $row->title_key;
		$this->geo_scope = $row->geo_scope;
		$this->description = $row->description;
		$this->image = $row->image;
		// Load self parent category
		if( $row->parent_id && $row->parent_id !== null ) {
			$this->parent_id = $row->parent_id;
		}
		// Load child-categories information
		$result = $dbr->select( self::$table, 'id', array('parent_id' => $id) );
		while( $row = $result->fetchRow() ) {
			$this->children[] = new self($row['id']);
		}
	}

	public function delete() {
		$this->internalDelete();
	}

	private function internalDelete() {
		if( $this->id === null ) {
			return false;
		}

		$dbw = wfGetDB(DB_MASTER);

		if( count($this->children) ) {
			foreach ($this->children as $child) {
				$child->delete();
			}
		}

		// Delete self
		$dbw->delete(
			self::$table,
			array( 'id' => $this->id )
		);

		return true;
	}

	public function save() {
		$this->internalSave();
	}

	private function internalSave() {
		$dbw = wfGetDB(DB_MASTER);
		// Insert this category into database if id is not set
		if( $this->id === null ) {
			$dbw->insert( self::$table, array(
				'title_key'   => $this->title_key,
				'geo_scope'   => $this->geo_scope,
				'description' => $this->description,
				'image'       => $this->image,
				'parent_id'   => ( $this->parent_id !== null) ? $this->parent_id : null
			) );
			$id       = $dbw->insertId();
			$this->id = $id;
		}else{
			$dbw->update( self::$table, array(
				'title_key'   => $this->title_key,
				'geo_scope'   => $this->geo_scope,
				'description' => $this->description,
				'image'       => $this->image,
				'parent_id'   => ( $this->parent_id !== null) ? $this->parent_id : null
			), array(
				'id' => $this->id
			));
		}
		if( count($this->children) ) {
			foreach ($this->children as $child) {
				$child->setParentId( $this->getId() );
				$child->save();
			}
		}
	}

	/**
	 * @return SettleGeoCategory[]
	 */
	public function getChildren() {
		return $this->children;
	}

	/**
	 * @return int
	 */
	public function getId() {
		return $this->id;
	}

	/**
	 * @return string
	 */
	public function getTitleKey() {
		return $this->title_key;
	}

	/**
	 * @return int
	 */
	public function getGeoScope() {
		return $this->geo_scope;
	}

	/**
	 * @return string
	 */
	public function getDescription() {
		return $this->description;
	}

	/**
	 * @return string
	 */
	public function getImage() {
		return $this->image;
	}

	/**
	 * @param string $title_key
	 */
	public function setTitleKey( $title_key ) {
		$this->title_key = $title_key;
	}

	/**
	 * @param int $geo_scope
	 */
	public function setGeoScope( $geo_scope ) {
		$this->geo_scope = $geo_scope;
	}

	/**
	 * @param string $description
	 */
	public function setDescription( $description ) {
		$this->description = $description;
	}

	/**
	 * @param string $image
	 */
	public function setImage( $image ) {
		$this->image = $image;
	}

	/**
	 * @param SettleGeoCategory[] $children
	 */
	public function setChildren( $children ) {
		$this->children = $children;
	}

	/**
	 * @param SettleGeoCategory $child
	 */
	public function addChild( $child ) {
		$this->children[] = $child;
	}

	/**
	 * @return int
	 */
	public function getParentId() {
		return $this->parent_id;
	}

	/**
	 * @param int $parent_id
	 */
	public function setParentId( $parent_id ) {
		$this->parent_id = $parent_id;
	}

}