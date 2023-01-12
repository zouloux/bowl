<?php

class BowlData {

	protected static array $__dataSets = [];

	static function registerDataSet ( string $name, callable $handler ) {
		if ( isset(self::$__dataSets[ $name ]) )
			throw new Exception("BowlData::registerDataSet // DataSet $name is already registered.");
		self::$__dataSets[ $name ] = $handler;
	}

	static function getData ( string $name ) {
		if ( !isset(self::$__dataSets[ $name ]) )
			throw new Exception("BowlData::getData // DataSet $name does not exists.");
		return self::$__dataSets[ $name ]();
	}

}