<?php
namespace Template;
/**
 * Class Template
 *
 * @package System\Library\Template
 */
class Template {
	private array $data = [];

	/**
	 * addPath
	 *
	 * @param string $key
	 * @param string $value
	 *
	 * @return void
	 */
	public function set(string $key, string $value): void {
		$this->data[$key] = $value;
	}

	/**
	 * Render
	 *
	 * @param string $template
	 *
	 * @return string
	 */
	public function render(string $template): string {
		$file = DIR_TEMPLATE . $template . '.tpl';

		if (is_file($file)) {
			extract($this->data);

			ob_start();

			require($file);

			return ob_get_clean();
		}

		throw new \Exception('Error: Could not load template ' . $file . '!');
		exit();
	}
}
