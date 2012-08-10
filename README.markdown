MoRa
====

MoRa is a lightweight markup language.

Actually, I doubt it would be useful to anyone except me, but you may try.


Philosophy
----------

Its main goals are:

* Be easy to read for a human as a plain text,
* Be easy to parse for computer.

The format should require no look-ahead processing: the formatting of the
text should be clear from the text **before** it (i.e. no Markdown-style
underlined headings).


Format
------

Text is broken down in blocks, which can be nested. Blocks are marked with
``C|``, where C is an arbitrary ASCII character that identifies the block
type. If the following line continues the block, it should contain the same
``C|`` mark. If it doesn’t, the block is closed.

Blocks contain paragraphs. Paragraphs can be:

* indented (marked with two or more spaces in the beginning of the line);
* unindented (marked by an empty line before);
* list items (marked by an arbitrary number of asterisk characters).

Paragraphs can contain inline markup. Inline markup starts with CC and ends
with CC, where CC is an arbitrary ASCII character that identifies the block.

Example:

	#| Main heading
	=| Subheading
	  This paragraph contains **bold** and //italic// text, as well as
	--strikethrough-- text.
	  This is yet another paragraph.
	
	This paragraphs has no indent.
	
	=| Subheading
	Yet another paragraph without indent (since header is closed, we need
	no 

Inline markup can have an argument string; it is written in square brackets
and cannot contain a closing square bracket.


Convertor details
-----------------

The convertor is written in PHP and produces a sane XHTML output.

C convertor is currently being developed, I'll put the sources once I'm
more-or-less content with it.


Room for improvement
--------------------

Current shortcomings:
* It's hard to define new paragraph types (ideally, headers should be
paragraph types, since they can’t contain other paragraphs);
* There are no numbered lists (I haven’t found a way to write them that is
both simple to parse and easy);
* The PHP code is messy;
* The PHP code should be encapsulated in a class.
