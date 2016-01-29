<?php


namespace Bixie\Framework\FieldType;

use Bixie\Framework\Field\FieldBase;
use Pagekit\Util\Arr;

abstract class FieldTypeBase implements FieldTypeInterface, \ArrayAccess, \JsonSerializable {
	/**
	 * @var string
	 */
	public $id;

	/**
	 * @var array
	 */
	protected $config;

	/**
	 * @var array
	 */
	protected $type;

	/**
	 * Type constructor.
	 * @param $type
	 */
	public function __construct ($type) {
		$this->id = $type['id'];
		$this->config = $type['config'];
		unset($type['config']);
		$this->type = $type;
		if (is_callable($this->type['main'])) {
			call_user_func($this->type['main']);
		}
	}

	/**
	 * @param \Pagekit\View\Asset\AssetManager $scripts
	 */
	public function registerScripts ($scripts) {
		$script = $this->type['resource'] . '/fieldtype-' . $this->id . '.js';
		$scripts->register('fieldtype-' . $this->id, $script, array_merge(['~bixie-fieldtypes'], $this->type['dependancies']));
	}

	/**
	 * @param \Pagekit\View\Asset\AssetManager $styles
	 */
	public function addStyles ($styles) {
		foreach ($this->type['styles'] as $name => $source) {
			$styles->add($name, $source);
		}
	}

	/**
	 * @return string
	 */
	public function getLabel () {
		return (isset($this->type['label']) ? $this->type['label'] : $this->id);
	}

	/**
	 * @return array
	 */
	public function getExtensions () {
		return $this->type['extensions'];
	}

	/**
	 * @param FieldBase $field
	 * @return array
	 */
	public function getOptions (FieldBase $field) {
		if (is_callable($this->type['getOptions'])) {

			return call_user_func($this->type['getOptions'], $field);

		}
		return $field->options ?: [];
	}

	/**
	 * Prepare default value before displaying form
	 * @param FieldBase $field
	 * @param array $value
	 * @return array
	 */
	public function prepareValue (FieldBase $field, $value) {
		if (is_callable($this->type['prepareValue'])) {

			return call_user_func($this->type['prepareValue'], $field, $value);

		}
		return $value;
	}

	/**
	 * @param FieldBase $field
	 * @param array|string $value
	 * @return array
	 */
	public function formatValue (FieldBase $field, $value) {
		if (is_callable($this->type['formatValue'])) {
			return call_user_func($this->type['formatValue'], $field, $value);
		}

		if (count($field->getOptions())) {
			//return from selectoptions
			$options = $field->getOptionsRef();
			if (is_array($value) && count($value)) {
				return array_map(function ($val) use ($options) {
					return isset($options[$val]) ? $options[$val] : $val;
				}, $value);
			} else {
				return $value ? isset($options[$value]) ? [$options[$value]] : [$value] : ['-'];
			}

		} else {
			//check for empty and return array
			return is_array($value) ? count($value) ? $value : ['-'] : [$value ?: '-'];
		}
	}

	/**
	 * @return mixed
	 */
	public function getConfig () {
		return $this->config ?: [];
	}

	/**
	 * Checks if a key exists.
	 * @param  string $key
	 * @return bool
	 */
	public function offsetExists ($key) {
		return Arr::has($this->getConfig(), $key);
	}

	/**
	 * Gets a value by key.
	 * @param  string $key
	 * @return mixed
	 */
	public function offsetGet ($key) {
		return Arr::get($this->getConfig(), $key);
	}

	/**
	 * Sets a value.
	 * @param string $key
	 * @param string $value
	 */
	public function offsetSet ($key, $value) {
		Arr::set($this->getConfig(), $key, $value);
	}

	/**
	 * Unset a value.
	 * @param string $key
	 */
	public function offsetUnset ($key) {
		Arr::remove($this->getConfig(), $key);
	}

	/**
	 * @return array
	 */
	public function toArray () {
		return array_merge([
			'id' => $this->id,
			'label' => $this->getLabel()
		], $this->getConfig());
	}

	/**
	 * Specify data which should be serialized to JSON
	 * @link  http://php.net/manual/en/jsonserializable.jsonserialize.php
	 * @return mixed data which can be serialized by <b>json_encode</b>,
	 * which is a value of any type other than a resource.
	 * @since 5.4.0
	 */
	function jsonSerialize () {
		return $this->toArray();
	}
}