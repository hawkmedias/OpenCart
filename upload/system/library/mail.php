<?php
/**
 * @package		OpenCart
 * @author		Daniel Kerr
 * @copyright	Copyright (c) 2005 - 2022, OpenCart, Ltd. (https://www.opencart.com/)
 * @license		https://opensource.org/licenses/GPL-3.0
 * @link		https://www.opencart.com
*/

/**
* Mail class
*/
class Mail {
	private object $adaptor;
	protected string $to = '';
	protected string $from = '';
	protected string $sender = '';
	protected string $reply_to = '';
	protected string $subject = '';
	protected string $text = '';
	protected string $html = '';
	protected array $attachments = array();

	/**
	 * Constructor
	 *
	 * @param	string	$adaptor
	 *
 	*/
	public function __construct(string $adaptor = 'mail') {
		$class = 'Mail\\' . $adaptor;
		
		if (class_exists($class)) {
			$this->adaptor = new $class();
		} else {
			trigger_error('Error: Could not load mail adaptor ' . $adaptor . '!');
			exit();
		}	
	}
	
	/**
     * 
     *
     * @param	mixed	$to
     */
	public function setTo(string $to): void {
		$this->to = $to;
	}
	
	/**
     * 
     *
     * @param	string	$from
     */
	public function setFrom($from): void {
		$this->from = $from;
	}
	
	/**
     * 
     *
     * @param	string	$sender
     */
	public function setSender($sender): void {
		$this->sender = $sender;
	}
	
	/**
     * 
     *
     * @param	string	$reply_to
     */
	public function setReplyTo($reply_to): void {
		$this->reply_to = $reply_to;
	}
	
	/**
     * 
     *
     * @param	string	$subject
     */
	public function setSubject($subject): void {
		$this->subject = $subject;
	}
	
	/**
     * 
     *
     * @param	string	$text
     */
	public function setText($text): void {
		$this->text = $text;
	}
	
	/**
     * 
     *
     * @param	string	$html
     */
	public function setHtml($html): void {
		$this->html = $html;
	}
	
	/**
     * 
     *
     * @param	string	$filename
     */
	public function addAttachment($filename): void {
		$this->attachments[] = $filename;
	}
	
	/**
     * 
     *
     */
	public function send(): bool {
		if (!$this->to) {
			throw new \Exception('Error: E-Mail to required!');
		}

		if (!$this->from) {
			throw new \Exception('Error: E-Mail from required!');
		}

		if (!$this->sender) {
			throw new \Exception('Error: E-Mail sender required!');
		}

		if (!$this->subject) {
			throw new \Exception('Error: E-Mail subject required!');
		}

		if ((!$this->text) && (!$this->html)) {
			throw new \Exception('Error: E-Mail message required!');
		}
		
		foreach (get_object_vars($this) as $key => $value) {
			$this->adaptor->$key = $value;
		}
		
		return $this->adaptor->send();
	}
}