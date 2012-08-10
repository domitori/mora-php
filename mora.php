<?php
// PHP-MoRa 0.52
// Hereby I disclaim all the copyright to this code.
// You can use it for any purpose. If this is legally
// possible, I put this code into public domain.

if (!function_exists('mora_anchor_argument')) {
	function mora_anchor_argument($url) {
		if (strpos($url, ':') === false && strpos($url, '@') !== false)
			return 'mailto:' . $url;
		return $url;
	}
}

if (!isset($mora_blocks)) {
	$mora_blocks = array(
		false => array(null, null, ' ', 'noblocks' => false, 'nopara' => false),
		'>' => array("<blockquote>\n", "\n</blockquote>", ' ', 'noblocks' => false, 'nopara' => false),
		'@' => array("<pre>\n", "\n</pre>", "\n", 'noblocks' => true, 'nopara' => true),
		'~' => array("<pre class='ccode'>\n", "\n</pre>", "\n", 'noblocks' => true, 'nopara' => true),
		'C' => array("<pre class='ccode'>\n", "\n</pre>", "\n", 'noblocks' => true, 'nopara' => true),
		'S' => array("<pre class='shell'>\n", "\n</pre>", "\n", 'noblocks' => true, 'nopara' => true),
		'#' => array('<h2>', "</h2>\n", ' ', 'noblocks' => false, 'nopara' => true),
		'=' => array('<h3>', "</h3>\n", ' ', 'noblocks' => false, 'nopara' => true),
		'-' => array('<h4>', "</h4>\n", ' ', 'noblocks' => false, 'nopara' => true),
		')' => array("<div class='notice'>\n", "\n</div>", ' ', 'noblocks' => false, 'nopara' => false),
		'!' => array("<div class='important'>\n", "\n</div>", ' ', 'noblocks' => false, 'nopara' => false)
	);
}

if (!isset($mora_inlines)) {
	$mora_inlines = array(
		'*' => array('<strong>', '</strong>'),
		'/' => array('<em>', '</em>'),
		'`' => array('<code>', '</code>'),
		'#' => array('<span class="zh" lang="yue">', '</span>'),
		'~' => array('<span class="yale" lang="yue">', '</span>'),
		"'" => array('<span class="pinyin" lang="pinyin">', '</span>'),
		"_" => array('<a href="%ARG%">', '</a>', 'hasarg' => '%ARG%', 'argprefn' => 'mora_anchor_argument')
	);
}

$mora_in_para = false;
$mora_in_para_depth = 0;
$mora_in_para_tag = 0;
$mora_in_blocks = array();
$mora_in_markup = array();
$mora_in_markup_content = array();

function mora_line_markup($line) {
	global $mora_in_markup, $mora_inlines;
	$i = 0;
	$len = strlen($line);
	$r = '';
	$flag = true;
	while ($i <= $len && preg_match('/((.)\2)(\[[^]]+\])?/', $line, $m, PREG_OFFSET_CAPTURE, $i)) {
		$type = $m[2][0];
		$starts_at = $m[0][1];
		if (in_array($type, $mora_in_markup) && isset($mora_inlines[$type]) && ($starts_at > 0 && ord($line[$starts_at -1]) > 32)) { //closing
			$r .= substr($line, $i, $starts_at - $i);
			$to_reopen = array();
			do {
				$closed_type = array_pop($mora_in_markup);
				$to_reopen[] = $closed_type;
				$r .= $mora_inlines[$closed_type][1];
			} while ($closed_type != $type && count($mora_in_markup) > 0);
			array_pop($to_reopen); //don't reopen the type we are closing
			foreach ($to_reopen as $closed_type)
				$r .= $mora_inlines[$closed_type][0];
			$i = $starts_at + 2;
		}
		elseif (isset($mora_inlines[$type]) && ord($line[$starts_at + 2]) > 32) { //opening
			$mora_in_markup[] = $type;
			$r .= substr($line, $i, $starts_at - $i);
			$i = $starts_at + 2;
			if (isset($mora_inlines[$type]['hasarg']) && preg_match('/^\[([^\]]+)\]/', substr($line, $i), $a)) {
				$r .= str_replace($mora_inlines[$type]['hasarg'],
					isset($mora_inlines[$type]['argprefn']) ? $mora_inlines[$type]['argprefn']($a[1]) : $a[1],
					$mora_inlines[$type][0]);
				$i += strlen($a[0]);
				if (strlen($line) > $i + 2) {
					if ($line{$i} == $line{$i} && $line{$i} == $type) {
						$r .= htmlentities($a[1]);
					}
				}
			}
			else {
				$r .= $mora_inlines[$type][0];
			}
		}
		else {
			$r .= substr($line, $i, $starts_at + 2 - $i);
			$i = $starts_at + 2;
		}
	}
	return $r . substr($line, $i, $len - $i);
}

function mora_close_inlines() {
		global $mora_in_markup;
		do {
			$closed_type = array_pop($mora_in_markup);
			print $mora_inlines[$closed_type][1];
		} while (count($mora_in_markup) > 0);
}

function mora_close_paragraphs($until_depth = 0) {
	global $mora_in_para, $mora_in_para_depth, $mora_in_para_tag;
	
	mora_close_inlines();
	if ($mora_in_para == 'p') {
		print "</p>\n";
		$mora_in_para_depth = 0;
	}
	if ($mora_in_para == 'ol' || $mora_in_para == 'ul') {
	        print "</li>\n";
		
		while ($mora_in_para_depth > $until_depth) {
			print "</${mora_in_para}>\n";
			$mora_in_para_depth--;
		}
	}
	if ($mora_in_para == "html") {
		while ($mora_in_para_depth >= $until_depth) {
			print "</${mora_in_para_tag}>";
			$mora_in_para_depth--;
		}
	}
	if ($mora_in_para_depth == 0)
		$mora_in_para = false;
}

function mora_is_list_line(&$line) {
	$i = 0;

	while ($line{$i} == '*')
		$i++;
	if ($i > 0 && $line{$i} == ' ') {
		$line = substr($line, $i+1);
		return $i;
	}
	else
		return 0;
}

function mora_close_blocks() {
	global $mora_in_blocks, $mora_blocks;
	while (count($mora_in_blocks) > 0) {
		$block = array_pop($mora_in_blocks);
		print $mora_blocks[$block][1]; //print after-line string
	}
}

function mora_do_line($line) {
	global $mora_in_blocks, $mora_in_para, $mora_in_para_depth, $mora_in_para_tag, $mora_inlines, $mora_blocks;
	
	$l = $line;
	$i = 0;
	$line_curr = 0;
	//Checking if all mora_blocks are continues
	while ($i < count($mora_in_blocks)) {
		$b = $mora_in_blocks[$i];
		if ($line{$line_curr} == $b && $line{$line_curr +1} == '|') {
			$line_curr += 2;
			if ($line{$line_curr} == ' ')
				$line_curr++;
			$i++;
			print $mora_blocks[$b][2]; //print after-line string
		}
		else
			break;
	}
	
	//Closing mora_blocks that are not continues on this line
	if ($i < count($mora_in_blocks)) {
		mora_close_paragraphs();
		while ($i < count($mora_in_blocks)) {
			$b = array_pop($mora_in_blocks);
			print $mora_blocks[$b][1];
		}
	}

	if ((count($mora_in_blocks) == 0 || !$mora_blocks[$mora_in_blocks[count($mora_in_blocks) -1]]['noblocks'])) {
		//Checking if any mora_blocks are opened on this line
		while (strlen($line) > $line_curr && $line{$line_curr +1} == '|' && isset($mora_blocks[$line{$line_curr}])) {
			if ($mora_in_para)
				mora_close_paragraphs();
			$b = $line{$line_curr};
			if (isset($mora_blocks[$b])) {
				$line_curr += 2;
				if ($line{$line_curr} == ' ')
					$line_curr++;
				print $mora_blocks[$b][0];
				array_push($mora_in_blocks, $b);
				if ($mora_blocks[$b]['noblocks'])
					break;
			}
		}
	}
	
	$rest = substr($line, $line_curr);
	
	$b = $mora_in_blocks[count($mora_in_blocks) -1];
	if ($mora_blocks[$b]['nopara']) {
		print htmlspecialchars($rest) . $mora_blocks[count($mora_in_blocks) -1][2];
		return;
	}
	
	
	//Is it HTML paragraph?
	if (!$mora_in_para && preg_match('/^\s*(<([A-Za-z0-9]+)( [^>]+)?>)(.*)$/', $rest, $m)) {
		mora_close_paragraphs();
		$mora_in_para = 'html';
		$mora_in_para_tag = $m[2];
		print $m[1];
		$rest = $m[4];
		$mora_in_para_depth = 1;
	}
  
  	//Processing HTML paragraphs
	if ($mora_in_para == 'html') {
		$flag = true;
		while ($flag) {
			$eb = "</${mora_in_para_tag}>";
			$ending_brace = strpos($rest, $eb);
			$opening_brace = strpos($rest, "<${mora_in_para_tag}");
			if ($opening_brace !== False && ($opening_brace < $ending_brace || $ending_brace === False)) {
				$opening_brace_ends = strpos($rest, '>', $opening_brace);
				$before_opening_brace_ends = $opening_brace_ends -1;
				$lastchar = $rest{$opening_brace_ends -1};
				if ($lastchar != '/') //avoid <tag />
					$mora_in_para_depth++;
				else
					$mora_in_para_depth--;
				print substr($rest, 0, $opening_brace_ends +1);
				$rest = substr($rest, $opening_brace_ends +1);
			}
			elseif ($ending_brace !== False) {
				print substr($rest, 0, $ending_brace + strlen($eb));
				$rest = substr($rest, $ending_brace + strlen($eb));
				$mora_in_para_depth--;
			}
			else
				$flag = false;
		}
		
		if ($mora_in_para_depth <= 0)
			$mora_in_para = false;
	}
	
	$trimmed = trim($rest);

	if ($mora_in_para == 'html') {
		print $trimmed;	
		return;
	}

	//Processing regular (non-HTML paragraphs)
	if (strlen($trimmed) == 0) {
			mora_close_paragraphs();
	}
	else {
		//This function deletes changes rest, deleting "*{*} ", if it returns >0
		$list_indent = mora_is_list_line($rest);
		if ($list_indent) {
			$trimmed = trim($rest);
			if ($mora_in_para == 'ul') {
				if ($list_indent < $mora_in_para_depth) {
					mora_close_paragraphs($list_indent);
				}
				print "</li>\n";
			}
			else {
				mora_close_paragraphs();
			}
			
			while ($mora_in_para_depth < $list_indent) {
				print "<ul>";
				$mora_in_para_depth++;
			}
          	print "<li>";
			$mora_in_para = 'ul';
		}
		elseif ($rest{0} == '\t' || substr($rest, 0, 2) == "  ") {
			mora_close_paragraphs();
			//Start an indented paragraph
			print "<p>";
			$mora_in_para = "p";
		}
		elseif (!$mora_in_para) {
			print '<p class="noindent">';
			$mora_in_para = "p";
		}
		print mora_line_markup(htmlspecialchars($trimmed) . $mora_blocks[count($mora_in_blocks) ? $mora_in_blocks[count($mora_in_blocks)] : false][2]);
	}
}

function mora($text) {
	$lines = explode("\n", $text);
	foreach($lines as $line) {
		mora_do_line(rtrim($line));
	}
	mora_close_paragraphs();
	mora_close_blocks();
}
