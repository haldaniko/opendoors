<?php

class TimberSettings {

	protected static $_instance;

	public function __construct() {
		add_filter('timber/context', array($this, '_configure_timber'));
	}


	public static function init() {
		static::$_instance = new static();
	}

	public static function get_instance() {
		if (is_null(static::$_instance)) {
			throw new \RuntimeException('You must initialize this instance before gaining access to it');
		}

		return static::$_instance;
	}


	public static function _configure_timber($context) {
		$context['theme'] = [
			'link' => get_stylesheet_directory_uri(),
		];

		return $context;
	}
}

TimberSettings::init();
