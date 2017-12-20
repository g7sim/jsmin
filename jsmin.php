<?php
	// ȥ�� js �հ׼�ע�� by peter 2013-5-5
	// ��Դ jsmin �汾 jsmin.c 2013-03-29 
	// �÷� jsmin::minify(script);
class jsmin {
	var $theA;
	var $theB;
	var $theLookahead;
	var $theX;
	var $theY;
	var $script;
	var $scriptlen;
	var $counter;
	var $out;

		// ���캯��
		// $script -- javascript ����
	function __construct($script) {
		$this->theA = '';
		$this->theB = '';
		$this->theLookahead = self::$EOF;
		$this->theX = self::$EOF;
		$this->theY = self::$EOF;
		$this->script = $script;
		$this->scriptlen = strlen($script);
		$this->counter = -1;
		$this->out = '';
	}

		// ��ȡһ���ַ�����������EOF
	function getc() {
		return $this->counter >= $this->scriptlen - 1 ? 
			self::$EOF : $this->script[++$this->counter];
	}

		// ���һ���ַ�
	function putc($c) {
		$this->out.= $c;
	}

		// ������
	function error($message) {
		echo($message);
		exit;
	}

		// Ӣ����ĸ�����֣��»��ߣ���Ԫ���ţ���б���򷵻�true
	function isAlphanum($c) {
		$a = ord($c);
		return (($a >= self::$ORD_a && $a <= self::$ORD_z) || 
			($a >= self::$ORD_0 && $a <= self::$ORD_9) ||
			($a >= self::$ORD_A && $a <= self::$ORD_Z) || 
			$c === '_' || 
			$c === '$' || 
			$c === '\\' || 
			$a > 126);
	}

		// ��ȡ��һ���ַ�
	function get() {
		$c = $this->theLookahead;
		$this->theLookahead = self::$EOF;
		
		if ($c === self::$EOF) $c = $this->getc();

		if (ord($c) >= self::$ORD_BLK || $c === "\n" || $c === self::$EOF) return $c;
		
		if ($c === "\r") return "\n";
		
		return ' ';
	}

		// �鿴��һ���ַ�
	function peek() {
		$this->theLookahead = $this->get();
		return $this->theLookahead;
	}

		// ��һ����Ч�ַ���������ע��
	function next() {
		$c = $this->get();

		if ($c === '/') {
			switch ($this->peek()) {
				case '/': // ����ע�Ϳ�ʼ
					while (true) {
						$c = $this->get();

						if ($c === self::$EOF || ord($c) <= self::$ORD_NL) break;
					}

					break;

				case '*': // ����ע�Ϳ�ʼ
					$this->get();

					while ($c !== ' ') {
						switch ($this->get()) {
							case '*':
								if ($this->peek() === '/') { // ����ע�ͽ���
									$this->get();
									$c = ' ';
								}

								break;

							case self::$EOF: // δ���Ķ���ע��
								$this->error('Unterminated comment.');
						}
					}

					break;
			}
		}

		$this->theY = $this->theX;
		$this->theX = $c;
		return $c;
	}

	function action($d) {
		switch ($d) {
			case 1: // �����ַ�
				$this->putc($this->theA);

				if (($this->theY === "\n" || $this->theY === ' ') &&
					($this->theA === '+' || $this->theA === '-' || $this->theA === '*' || $this->theA === '/') &&
					($this->theB === '+' || $this->theB === '-' || $this->theB === '*' || $this->theB === '/')) {
					$this->putc($this->theY);
				} // �������������ǰ���հ�

			case 2: // �����ַ���
				$this->theA = $this->theB;

				if ($this->theA === '\'' || $this->theA === '"' || $this->theA === '`') {
					while (true) {
						$this->putc($this->theA);
						$this->theA = $this->get();

						if ($this->theA === $this->theB) break; // �ַ�������
						
						if ($this->theA === '\\') { // ��б�ߣ�������һ���ַ�
							$this->putc($this->theA);
							$this->theA = $this->get();
						}
						
						if ($this->theA === self::$EOF) $this->error('Unterminated string literal.');
					}
				}

			case 3: // ����������ʽ
				$this->theB = $this->next();

				if ($this->theB === '/' && (
					$this->theA === '(' || $this->theA === ',' || $this->theA === '=' || $this->theA === ':' ||
					$this->theA === '[' || $this->theA === '!' || $this->theA === '&' || $this->theA === '|' ||
					$this->theA === '?' || $this->theA === '+' || $this->theA === '-' || $this->theA === '~' ||
					$this->theA === '*' || $this->theA === '/' || $this->theA === '{' || $this->theA === "\n")) { // ������ʽ��ʼ
					$this->putc($this->theA);

					if ($this->theA === '/' || $this->theA === '*') $this->putc(' '); // �˳�����������ӿո�
					
					$this->putc($this->theB);
					
					while (true) {
						$this->theA = $this->get();

						if ($this->theA === '[') { // �з������
							while (true) {
								$this->putc($this->theA);
								$this->theA = $this->get();
								
								if ($this->theA === ']') break; // ��Խ���
								
								if ($this->theA === '\\') {
									$this->putc($this->theA);
									$this->theA = $this->get();
								}
								
								if ($this->theA === self::$EOF) $this->error('Unterminated set in Regular Expression literal.');
							}
						} else if ($this->theA === '/') { // �������
							switch ($this->peek()) {
								case '/':
								case '*':
								$this->error('Unterminated set in Regular Expression literal.');
							}

							break;
						} else if ($this->theA === '\\') { // ת���������κ��ַ�������
							$this->putc($this->theA);
							$this->theA = $this->get();
						}

						if ($this->theA === self::$EOF) $this->error('Unterminated Regular Expression literal.');
						
						$this->putc($this->theA);
					}

					$this->theB = $this->next();
				}
		}
	}

		// ѹ��JS����
	function _minify() {
		if (0 == strncmp($this->peek(), "\xef", 1)) {
			$this->get();
			$this->get();
			$this->get();
		} 

			// ��ʼ����
		$this->theA = ""; // ��ǰ�ַ�
		$this->action(3);

		while ($this->theA !== self::$EOF) {
			switch ($this->theA) {
				case ' ':
					$this->action($this->isAlphanum($this->theB) ? 1 : 2);
					break;

				case "\n":
					switch ($this->theB) {
						case '{':
						case '[':
						case '(':
						case '+':
						case '-':
						case '!':
						case '~':
							$this->action(1);
							break;

						case ' ':
							$this->action(3);
							break;

						default:
							$this->action($this->isAlphanum($this->theB) ? 1 : 2);
					}

					break;

				default:
					switch ($this->theB) {
						case ' ':
							$this->action($this->isAlphanum($this->theA) ? 1 : 3);
							break;

						case "\n":
							switch ($this->theA) {
								case '}':
								case ']':
								case ')':
								case '+':
								case '-':
								case '"':
								case '\'':
								case '`':
									$this->action(1);
									break;

								default:
									$this->action($this->isAlphanum($this->theA) ? 1 : 3);
							}

							break;

						default:
							$this->action(1);
							break;
					}
			}
		}

		return preg_replace('/;+\s*([};])/s', '\\1', 
			preg_replace('/^\s*<!--\s*/s', '', 
			trim($this->out)));
	}

	/*****************************************************************
	 *
	 * ��̬����
	 *
	 *****************************************************************/
	static $EOF;
	static $ORD_NL;
	static $ORD_BLK;
	static $ORD_A;
	static $ORD_Z;
	static $ORD_a;
	static $ORD_z;
	static $ORD_0;
	static $ORD_9;

		// ѹ��js����
		// $script -- js����
	static function minify($script) {
		$jsmin = new self($script);
		return $jsmin->_minify();
	}
}

	// ���þ�̬����
jsmin::$EOF = '$$';
jsmin::$ORD_NL = ord("\n");
jsmin::$ORD_BLK = ord(' ');
jsmin::$ORD_A = ord('A');
jsmin::$ORD_Z = ord('Z');
jsmin::$ORD_a = ord('a');
jsmin::$ORD_z = ord('z');
jsmin::$ORD_0 = ord('0');
jsmin::$ORD_9 = ord('9');