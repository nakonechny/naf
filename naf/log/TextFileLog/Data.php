<?php 
namespace naf\log;

class TextFileLog_Data extends TextFileLog
{
	const INDENT_PAD = '    '; // 4 spaces

	/**
	 * @param mixed $message
	 * @return bool
	 */
	public function write($data)
	{
		$this->appendLine($this->getTimestamp());
		$this->appendLine($data);
		$this->appendLine();
		return $this->flush();
	}

	protected function appendLine($string = '')
	{
		parent::appendLine(static::toString($string));
	}

	/**
	 * @param mixed $data
	 * return string
	 */
	static protected function toString($data, $indent_level = 0)
	{
		$lines = array();

		$padding= str_repeat(self::INDENT_PAD, $indent_level);

		if ($indent_level) {
			$lines[] = '';
		}

		if (is_array($data)) {
			foreach ($data as $key => $value) {
				$lines[] = $key . ': '
					. static::toString($value, $indent_level + 1);
			}
		} else if (is_object($data)) {
			$lines[] = get_class($data) . " -> "
				. static::toString(get_object_vars($data), $indent_level + 1);
		} else {
			return $data;
		}

		return $padding . implode("\n" . $padding, $lines);
	}
}