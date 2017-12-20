<?php
	// 去除 js 空白及注释 by peter 2013-5-5
	// 来源 jsmin 版本 jsmin.c 2013-03-29 
	// 用法 jsmin::minify(script);
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

		// 构造函数
		// $script -- javascript 代码
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

		// 获取一个字符，结束返回EOF
	function getc() {
		return $this->counter >= $this->scriptlen - 1 ? 
			self::$EOF : $this->script[++$this->counter];
	}

		// 输出一个字符
	function putc($c) {
		$this->out.= $c;
	}

		// 出错处理
	function error($message) {
		echo($message);
		exit;
	}

		// 英文字母，数字，下划线，美元符号，反斜线则返回true
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

		// 获取下一个字符
	function get() {
		$c = $this->theLookahead;
		$this->theLookahead = self::$EOF;
		
		if ($c === self::$EOF) $c = $this->getc();

		if (ord($c) >= self::$ORD_BLK || $c === "\n" || $c === self::$EOF) return $c;
		
		if ($c === "\r") return "\n";
		
		return ' ';
	}

		// 查看下一个字符
	function peek() {
		$this->theLookahead = $this->get();
		return $this->theLookahead;
	}

		// 下一个有效字符，会跳过注释
	function next() {
		$c = $this->get();

		if ($c === '/') {
			switch ($this->peek()) {
				case '/': // 单行注释开始
					while (true) {
						$c = $this->get();

						if ($c === self::$EOF || ord($c) <= self::$ORD_NL) break;
					}

					break;

				case '*': // 多行注释开始
					$this->get();

					while ($c !== ' ') {
						switch ($this->get()) {
							case '*':
								if ($this->peek() === '/') { // 多行注释结束
									$this->get();
									$c = ' ';
								}

								break;

							case self::$EOF: // 未完结的多行注释
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
			case 1: // 处理字符
				$this->putc($this->theA);

				if (($this->theY === "\n" || $this->theY === ' ') &&
					($this->theA === '+' || $this->theA === '-' || $this->theA === '*' || $this->theA === '/') &&
					($this->theB === '+' || $this->theB === '-' || $this->theB === '*' || $this->theB === '/')) {
					$this->putc($this->theY);
				} // 特殊操作符加入前导空白

			case 2: // 处理字符串
				$this->theA = $this->theB;

				if ($this->theA === '\'' || $this->theA === '"' || $this->theA === '`') {
					while (true) {
						$this->putc($this->theA);
						$this->theA = $this->get();

						if ($this->theA === $this->theB) break; // 字符串结束
						
						if ($this->theA === '\\') { // 反斜线，跳过下一个字符
							$this->putc($this->theA);
							$this->theA = $this->get();
						}
						
						if ($this->theA === self::$EOF) $this->error('Unterminated string literal.');
					}
				}

			case 3: // 处理正则表达式
				$this->theB = $this->next();

				if ($this->theB === '/' && (
					$this->theA === '(' || $this->theA === ',' || $this->theA === '=' || $this->theA === ':' ||
					$this->theA === '[' || $this->theA === '!' || $this->theA === '&' || $this->theA === '|' ||
					$this->theA === '?' || $this->theA === '+' || $this->theA === '-' || $this->theA === '~' ||
					$this->theA === '*' || $this->theA === '/' || $this->theA === '{' || $this->theA === "\n")) { // 正则表达式开始
					$this->putc($this->theA);

					if ($this->theA === '/' || $this->theA === '*') $this->putc(' '); // 乘除后带正则必须加空格
					
					$this->putc($this->theB);
					
					while (true) {
						$this->theA = $this->get();

						if ($this->theA === '[') { // 中符号配对
							while (true) {
								$this->putc($this->theA);
								$this->theA = $this->get();
								
								if ($this->theA === ']') break; // 配对结束
								
								if ($this->theA === '\\') {
									$this->putc($this->theA);
									$this->theA = $this->get();
								}
								
								if ($this->theA === self::$EOF) $this->error('Unterminated set in Regular Expression literal.');
							}
						} else if ($this->theA === '/') { // 正则结束
							switch ($this->peek()) {
								case '/':
								case '*':
								$this->error('Unterminated set in Regular Expression literal.');
							}

							break;
						} else if ($this->theA === '\\') { // 转义符，后跟任何字符都跳过
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

		// 压缩JS代码
	function _minify() {
		if (0 == strncmp($this->peek(), "\xef", 1)) {
			$this->get();
			$this->get();
			$this->get();
		} 

			// 开始处理
		$this->theA = ""; // 当前字符
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
	 * 静态变量
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

		// 压缩js代码
		// $script -- js代码
	static function minify($script) {
		$jsmin = new self($script);
		return $jsmin->_minify();
	}
}

	// 设置静态常量
jsmin::$EOF = '$$';
jsmin::$ORD_NL = ord("\n");
jsmin::$ORD_BLK = ord(' ');
jsmin::$ORD_A = ord('A');
jsmin::$ORD_Z = ord('Z');
jsmin::$ORD_a = ord('a');
jsmin::$ORD_z = ord('z');
jsmin::$ORD_0 = ord('0');
jsmin::$ORD_9 = ord('9');