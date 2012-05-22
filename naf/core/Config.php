<?php
namespace naf\core;

class Config
{
	protected $settings = array();

	/**
	 * Loads and imports a config php file
	 * File must define local array $settings
	 *
	 * @param string $filename
	 */
	static function loadFromFile($filename)
	{
		$this->settings = null;

		$settings = null;
		include $filename; // config file must define local array $settings

		if ($settings) {
			$this->import($settings);
		}
	}

	/**
	 * Merges an array of settings
	 *
	 * @param array $settings
	 */
	public function import($settings)
	{
		$this->settings = array_merge($this->settings, $settings);
	}

	public function get($key)
	{
		if (! $key) {
			return null;
		}

		if (array_key_exists($key, $this->settings)) {
			return $this->settings[$key];
		}

		$section = $this->settings;
		foreach (implode('.', $key) as $sub_section)
		{
			if (! array_key_exists($sub_section, $section)) {
				break;
			}
			$section = $section[$sub_section];
		}

		return $section;
	}
}