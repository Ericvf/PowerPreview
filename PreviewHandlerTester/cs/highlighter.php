<?PHP

/* // <--

	* This script is freeware and can be used without any restrictions.
	* Junioronline Inc. cannot be held responsible for any damages using this script.
	* It comes with no warranty.

        * (c) Junioronline Inc.     .:ijf:
                                  ,jGW###W,
                                ;D#####KGt.
                               tW###WGt:
                              f####f,
                       :::. .j####j
                     ,G###G.tK###L.
                     iE##Wf.G###K,
                     ,ijjt.:K###L
                    :K###D.t###W;
                    t####L.G###E:
                   .D####i:K###f .,,,.
                   t####E:;W##W;.G###i
                  ,K####t :jLLt  ifLf:
                 .G####D.
                ;D####D:    
            :iLE####Kt:     * Current script: 	"highlighter.php"
          :GW#####WG;
          ,W##WKGt;         * Written:		February 21 2004 @ 04:32 AM
           ;;,.		    	* By:		Fex

			    * Version:		1.2

			    * Description:	Use the "AS_HIGHLIGHTER" class to syntax highlight your
						ActionScript files. Please note that the "HIGHLIGHTER"
						class itself doesn't do anything. It needs a language
						definition with keywords etc...

			    * Changelog:
						Version 1.2:
						=================================================================
						 - Full Flash MX Proffesional 2004 Syntax support (extracted from ColorSyntax.xml)
						 - Implementation for JCMS
						 - Added HIGHLIGHT_FILE() function
						 - First public release!

						Version 1.1:
						=================================================================
						 - Added support for multiple indent characters!
						 - Fixed escaping quotes bug!
						 - Added support for ActionScript 2.0 syntax

						Version 1.03:
						=================================================================
						 - Added support for multiple indent characters!
						 - Gained 2% in parsing speed ($output_array ajustment)
						 - Added support for Flash MX syntax!

						Version 1.0:
						=================================================================
						Initial release, 72% faster parser than current JCMS highlighter!
					

*/ // -->


class HIGHLIGHTER
{
    ### CLASS CONSTRUCTOR
    #
	function __construct()
	{
	    ### SPECIAL CHARACTERS
	    # 
                $this -> delimiters        	= Array("~", "!", "@", "%", "^", "&", "*", "(", ")", "-", "+", "=", "|", "\\", "/", "{", "}", "<", ">", "[", "]", ":", ";", "\"", "\'", " ", ",", "	", ".", "?");
                $this -> quote_chars       	= Array("\"", "'");
		$this -> esc_char		= "\\";

		$this -> indent_chars		= "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
		$this -> indent            	= Array("{");
                $this -> unindent          	= Array("}");
                $this -> comment_line_on     	= Array("//", "#");
                $this -> comment_block_on  	= Array("/*");
                $this -> comment_block_off  	= Array("*/");
		$this -> line_break		= "\n";


	    ### VARIOUS OPTIONS
	    #
		$this -> trimlines		= 1;
		$this -> pre			= 0;


	    ### THE REST
	    #
		$this -> cssclasses		= Array("HL_bcomment", "HL_comment", "HL_quote");
		$this -> keywords		= Array("");
	}


    ### FUNCTION HIGHLIGHTS STRING
    #
	function HIGHLIGHT_STRING( $text )
	{
	    ### FILTER OUT ILLEGAL CHARACTERS
	    #
		$text 				= STR_replace ("<", "&lt;", $text);
		$text 				= STR_replace (">", "&gt;", $text);


	    ### BEHAVIOUR BOOLS
	    #
		$in_bcomment			= 0;
		$in_comment			= 0;
		$in_quote			= 0;
		$bcomm_char			= "";
		$indents				= 0;
	    ### READ LINES
	    #
		$line_array			= PREG_split ("/\n/", $text);

		foreach ($line_array AS $linenum => $line)
		{
		    ### INIT VARS
		    #
			$result_array		= Array ();
			$quote_char		= "";
			$curr_char		= "";
			$prev_char		= "";
			$lineout		= "";
			$startline		= 0;
			$in_comment		= 0;
			$in_quote 		= 0;


		    // Trim the line if we should
			if ($this -> trimlines) $line = trim ($line);


		    ### HANDLE INDENTS
		    #
//			if (strpos( $line, $this -> unindent ) > -1) $indents--;
//			$result_array[0] 	= STR_repeat( $this -> indent_chars, $indents);
//			if (strpos( $line, $this -> indent ) > -1) $indents++;

			foreach ($this -> unindent AS $unindent)
			{
				if (STRpos($line, $unindent) > -1) 
					$indents--;
			}

//			if ($indents < 0) $indents = 0;
			$result_array[0] 	= STR_repeat ($this -> indent_chars, $indents);

			foreach ($this -> indent AS $indent)
			{
				if (STRpos($line, $indent) > -1) 
					$indents++;
			}

		    ### READ CHARACTERS IN LINE
		    #
			$length			= STRlen ($line);

			for ($i = 0; $i < $length +1; $i++)
			{
			    // Save current and previous character
				$curr_char 	= $line[$i];
				$prev_char	= $line[$i-1];

			    ### CHECK FOR LINE-COMMENT
			    #
				if (!$in_bcomment && !$in_quote) 
				{
					foreach ($this -> comment_line_on AS $comment_line_on)
					{
						if (substr($line, $i, STRlen($comment_line_on)) == $comment_line_on)
						{
							$result_array[]	= substr($line, $startline, $i - $startline);
							$result_array[]	= "<SPAN CLASS=\"" . $this -> cssclasses[1] . "\">" . substr($line, $i, $length - $i) . "</SPAN>";

							$i		= $length;
							$break_it 	= 1; 
							break;
						}
					}

					if ($break_it)
					{
						$in_comment = 1;
						$break_it = 0; 
						break;
					}

			    ### CHECK FOR BLOCK-COMMENT-ON
			    #
					foreach ($this -> comment_block_on AS $num => $comment_block_on)
					{
						if (substr($line, $i, STRlen($comment_block_on)) == $comment_block_on)
						{
							$result_array[] = substr($line, $startline, $i - $startline);

							$startline 	= $i;
							$bcomm_char	= $this -> comment_block_off[$num];
							$in_bcomment	= 1;
						}
					}
				}


			    ### CHECK FOR BLOCK-COMMENT-OFF
			    #
				else if ($in_bcomment && !$in_quote)
				{

					foreach ($this -> comment_block_off AS $num => $comment_block_off)
					{
						if (substr($line, $i, STRlen($comment_block_off)) == $comment_block_off && $comment_block_off == $bcomm_char)
						{
							$result_array[] = "<SPAN CLASS=\"" . $this -> cssclasses[0] . "\">" . substr($line, $startline, $i - $startline + STRlen($comment_block_off)) . "</SPAN>";
							$startline 	= $i + STRlen($comment_block_off);

							$in_bcomment 	= 0;
						}

					}
				}

			    ### CHECK FOR QUOTES
			    #
				if (in_array($curr_char, $this -> quote_chars) && $prev_char != $this -> esc_char && !$in_bcomment)
				{
					// Are we in a quote?
					if (!$in_quote)
					{
						$result_array[] = substr($line, $startline, $i - $startline);

						$startline  = $i;
						$quote_char = $curr_char;
						$in_quote   = 1;
					}

					// If we are make sure we have the right quote
					else if ($curr_char == $quote_char)
					{
						$result_array[] = "<SPAN CLASS=\"" . $this -> cssclasses[2] . "\">" . substr($line, $startline, $i - $startline + 1) . "</SPAN>";

						$startline  = $i + 1;
						$quote_char = "";
						$in_quote   = 0;
					}
				}


			    ### WRITE REMAINDER TEXT
			    #
				if ($i == $length) 
				{
					$line = substr($line, $startline, $i - $startline);
					if ($in_bcomment) $line = "<SPAN CLASS=\"HL_bcomment\">" . $line . "</SPAN>";
					$result_array[] = $line;
				}
			}

		    ### LOOP PAST HIGHLIGHTED LINES
		    #
			foreach ($result_array AS $line) 
			{
			     // Quick fix to determen wheter this line should be highlighted!
				if (substr($line, 0, 1) != "<") 
					$line = $this -> FilterWords($this -> keywords, $line);

				$this -> PrintIt ($line);
			}

			$this -> PrintIt ($this -> line_break);
		}
	}

	function FilterWords($words, $line)
	{
	     // Total delimters array
		$delimiters	= Array_merge($this -> delimiters, $this -> quote_chars);
		$curr_word 	= "";
		$curr_char 	= "";
		$line_out 	= "";
		$in_word 	= 0;
		$l 		= STRlen($line);

	    // Loop through all words of sentence
		for ($i = 0; $i <= $l; $i++)
		{
			$curr_char 	= $line[$i];

		     // Check if our char is a delimiter
			if (in_array($curr_char, $delimiters) || $i == $l)
			{
			     // If we are in a word, this means it is the end of this word
				if ($in_word)
				{
					$in_word = 0;

				     // Check if we should highlight this word
					if (isset($this -> keywords[$curr_word]))
					{
						$class_nr  = $this -> keywords[$curr_word];
						$class     = $this -> cssclasses[$class_nr];
					
						$line_out   .= "<SPAN CLASS=\"$class\">$curr_word</SPAN>";
					}
					else $line_out .= $curr_word;
				}
				$line_out .= $curr_char;
			}
			else
			{
		      	     // We aren't a delimiter, and we aren't a word, so this must be the first letter
				if (!$in_word)
				{
					$in_word = 1;
					$curr_word = $curr_char;

				}
				else $curr_word .= $curr_char;
			}
		}

		return $line_out;
	}

    ### FUNCTION HIGHLIGHTS TEXT
    #
	function HIGHLIGHT_TEXT ($text)
	{
		$this -> _BUFFER = "";
		$this -> HIGHLIGHT_STRING($text);

		$result = "<SPAN CLASS=\"HL_text\">\n" . $this -> _BUFFER . "\n</SPAN>";
		if ($this -> pre) $result = "<PRE>\n" . $result . "\n</PRE>";

		return $result;
	}


    ### FUNCTION HIGHLIGHTS FILE
    #
	function HIGHLIGHT_FILE ($filename)
	{
		$Fhandle 	= Fopen($filename, "r");
		$Finput		= Fread($Fhandle, filesize($filename));
		Fclose($Fhandle);

		$Finput = $this -> HIGHLIGHT_TEXT($Finput);
		return $Finput;
	}  


    ### FUNCTION SAVES HIGHLIGHTED TEXT TO BUFFER
    #
	function PrintIt ($text)
	{
		$this -> _BUFFER .= $text;
	}



 
}

class AS_HIGHLIGHTER extends HIGHLIGHTER
{
	function AS_HIGHLIGHTER()
	{

	    ### Basic stuff
	    #
		$this -> HIGHLIGHTER();
		
	     // JCMS can use pre's, so I enabled them
		$this -> indent_chars	= "\t";
		$this -> line_break	= "\n";
		$this -> pre 		= 1;

		$this -> cssclasses 	= Array("HL_bcomment", "HL_comment", "HL_quote", "HL_keyword", "HL_identifier", "HL_property");
         	$this -> keywords	= Array(
				"if"			=> "3",
				"while"			=> "3",
				"with"			=> "3",
				"var"			=> "3",
				"void"			=> "3",
				"typeof"		=> "3",
				"new"			=> "3",
				"delete"		=> "3",
				"do"			=> "3",
				"continue"		=> "3",
				"else"			=> "3",
				"add"			=> "3",
				"and"			=> "3",
				"or"			=> "3",
				"not"			=> "3",
				"on"			=> "3",
				"onClipEvent"		=> "3",
				"for"			=> "3",
				"in"			=> "3",
				"function"		=> "3",
				"instanceof"		=> "3",
				"eq"			=> "3",
				"break"			=> "3",
				"#include"		=> "3",
				"ne"			=> "3",
				"return"		=> "3",
				"try"			=> "3",
				"catch"			=> "3",
				"finally"		=> "3",
				"throw"			=> "3",
				"_root"			=> "4",
				"_parent"		=> "4",
				"_level"		=> "4",
				"MovieClip"		=> "4",
				"appendChild"		=> "4",
				"attachMovie"		=> "4",
				"attachSound"		=> "4",
				"loadSound"		=> "4",
				"attributes"		=> "4",
				"arguments.caller"	=> "4",
				"arguments.callee"	=> "4",
				"charAt"		=> "4",
				"charCodeAt"		=> "4",
				"childNodes"		=> "4",
				"clear"			=> "4",
				"cloneNode"		=> "4",
				"close"			=> "4",
				"concat"		=> "4",
				"connect"		=> "4",
				"createElement"		=> "4",
				"createTextNode"	=> "4",
				"docTypeDecl"		=> "4",
				"status"		=> "4",
				"duplicateMovieClip"	=> "4",
				"firstChild"		=> "4",
				"getBounds"		=> "4",
				"getBytesLoaded"	=> "4",
				"getBytesTotal"		=> "4",
				"getDate"		=> "4",
				"getDay"		=> "4",
				"getDepth"		=> "4",
				"getInstanceAtDepth"	=> "4",
				"getNextHighestDepth"	=> "4",
				"getFullYear"		=> "4",
				"getHours"		=> "4",
				"getMilliseconds"	=> "4",
				"getMinutes"		=> "4",
				"getMonth"		=> "4",
				"getNextDepth"		=> "4",
				"getPan"		=> "4",
				"getRGB"		=> "4",
				"getSeconds"		=> "4",
				"getSWFVersion"		=> "4",
				"getTime"		=> "4",
				"getTimezoneOffset"	=> "4",
				"getTransform"		=> "4",
				"getURL"		=> "4",
				"getUTCDate"		=> "4",
				"getUTCDay"		=> "4",
				"getUTCFullYear"	=> "4",
				"getUTCHours"		=> "4",
				"getUTCMilliseconds"	=> "4",
				"getUTCMinutes"		=> "4",
				"getUTCMonth"		=> "4",
				"getUTCSeconds"		=> "4",
				"getVolume"		=> "4",
				"getYear"		=> "4",
				"globalToLocal"		=> "4",
				"gotoAndPlay"		=> "4",
				"gotoAndStop"		=> "4",
				"hasChildNodes"		=> "4",
				"hitTest"		=> "4",
				"indexOf"		=> "4",
				"insertBefore"		=> "4",
				"join"			=> "4",
				"lastChild"		=> "4",
				"lastIndexOf"		=> "4",
				"length"		=> "4",
				"load"			=> "4",
				"loadMovie"		=> "4",
				"loadVariables"		=> "4",
				"loaded"		=> "4",
				"localToGlobal"		=> "4",
				"maxscroll"		=> "4",
				"nextFrame"		=> "4",
				"nextSibling"		=> "4",
				"nodeName"		=> "4",
				"nodeType"		=> "4",
				"nodeValue"		=> "4",
				"onClose"		=> "4",
				"onConnect"		=> "4",
				"onLoad"		=> "4",
				"onXML"			=> "4",
				"parentNode"		=> "4",
				"parseXML"		=> "4",
				"play"			=> "4",
				"pop"			=> "4",
				"prevFrame"		=> "4",
				"previousSibling"	=> "4",
				"push"			=> "4",
				"removeMovieClip"	=> "4",
				"removeNode"		=> "4",
				"reverse"		=> "4",
				"scroll"		=> "4",
				"send"			=> "4",
				"sendAndLoad"		=> "4",
				"setDate"		=> "4",
				"setFullYear"		=> "4",
				"setHours"		=> "4",
				"setMilliseconds"	=> "4",
				"setMinutes"		=> "4",
				"setMonth"		=> "4",
				"setPan"		=> "4",
				"setRGB"		=> "4",
				"setSeconds"		=> "4",
				"setTime"		=> "4",
				"setTransform"		=> "4",
				"setUTCDate"		=> "4",
				"setUTCFullYear"	=> "4",
				"setUTCHours"		=> "4",
				"setUTCMilliseconds"	=> "4",
				"setUTCMinutes"		=> "4",
				"setUTCMonth"		=> "4",
				"setUTCSeconds"		=> "4",
				"setVolume"		=> "4",
				"setYear"		=> "4",
				"shift"			=> "4",
				"slice"			=> "4",
				"sort"			=> "4",
				"sortOn"		=> "4",
				"splice"		=> "4",
				"split"			=> "4",
				"start"			=> "4",
				"startDrag"		=> "4",
				"stop"			=> "4",
				"stopDrag"		=> "4",
				"substr"		=> "4",
				"substring"		=> "4",
				"swapDepths"		=> "4",
				"toLowerCase"		=> "4",
				"toString"		=> "4",
				"prototype"		=> "4",
				"__proto__"		=> "4",
				"toUpperCase"		=> "4",
				"unloadMovie"		=> "4",
				"getTextSnapshot"	=> "4",
				"unshift"		=> "4",
				"valueOf"		=> "4",
				"xmlDecl"		=> "4",
				"Array"			=> "4",
				"CASEINSENSITIVE"	=> "4",
				"DESCENDING"	=> "4",
				"UNIQUESORT"	=> "4",
				"RETURNINDEXEDARRAY" 	=> "4",
				"NUMERIC"			=> "4",
				"Boolean"			=> "4",
				"Color"				=> "4",
				"Date"				=> "4",
				"UTC"			=> "4",
				"Key"			=> "4",
				"ALT"			=> "4",
				"BACKSPACE"			=> "4",
				"CAPSLOCK"			=> "4",
				"CONTROL"			=> "4",
				"DELETEKEY"			=> "4",
				"DOWN"			=> "4",
				"END"			=> "4",
				"ENTER"			=> "4",
				"ESCAPE"			=> "4",
				"HOME"			=> "4",
				"INSERT"			=> "4",
				"LEFT"			=> "4",
				"PGDN"			=> "4",
				"PGUP"			=> "4",
				"RIGHT"			=> "4",
				"SHIFT"			=> "4",
				"SPACE"			=> "4",
				"TAB"			=> "4",
				"UP"			=> "4",
				"getAscii"			=> "4",
				"getCode"			=> "4",
				"isDown"			=> "4",
				"isToggled"			=> "4",
				"Math"			=> "4",
				"E"			=> "4",
				"LN10"			=> "4",
				"LN2"			=> "4",
				"LOG10E"			=> "4",
				"LOG2E"			=> "4",
				"PI"			=> "4",
				"SQRT1_2"			=> "4",
				"SQRT2"			=> "4",
				"abs"			=> "4",
				"acos"			=> "4",
				"asin"			=> "4",
				"atan"			=> "4",
				"atan2"			=> "4",
				"ceil"			=> "4",
				"cos"			=> "4",
				"exp"			=> "4",
				"floor"			=> "4",
				"log"			=> "4",
				"max"			=> "4",
				"min"			=> "4",
				"pow"			=> "4",
				"random"			=> "4",
				"round"			=> "4",
				"sin"			=> "4",
				"sqrt"			=> "4",
				"tan"			=> "4",
				"Mouse.hide"			=> "4",
				"Mouse.show"			=> "4",
				"Number"			=> "4",
				"MAX_VALUE"			=> "4",
				"MIN_VALUE"			=> "4",
				"NEGATIVE_INFINITY"		=> "4",
				"NaN"				=> "4",
				"POSITIVE_INFINITY"		=> "4",
				"Object"			=> "4",
				"getBeginIndex"	=> "4",
				"getCaretIndex"	=> "4",
				"getEndIndex"		=> "4",
				"getFocus"		=> "4",
				"setFocus"		=> "4",
				"setSelection"	=> "4",
				"Sound"				=> "4",
				"String"			=> "4",
				"String.fromCharCode"		=> "4",
				"XML"				=> "4",
				"XMLNode"			=> "4",
				"XMLSocket"			=> "4",
				"addRequestHeader"		=> "4",
				"attachMovie"			=> "4",
				"call"				=> "4",
				"chr"				=> "4",
				"duplicateMovieClip"		=> "4",
				"escape"			=> "4",
				"eval"				=> "4",
				"false"				=> "4",
				"fscommand"			=> "4",
				"getBounds"			=> "4",
				"getBytesLoaded"		=> "4",
				"getBytesTotal"			=> "4",
				"getDepth"			=> "4",
				"getProperty"			=> "4",
				"getTimer"			=> "4",
				"getURL"			=> "4",
				"getVersion"			=> "4",
				"globalToLocal"			=> "4",
				"gotoAndPlay"			=> "4",
				"gotoAndStop"			=> "4",
				"hitTest"			=> "4",
				"ifFrameLoaded"			=> "4",
				"-Infinity"			=> "4",
				"Infinity"			=> "4",
				"int"				=> "4",
				"isFinite"			=> "4",
				"isNaN"				=> "4",
				"keyPress"			=> "4",
				"length"			=> "4",
				"loadMovie"			=> "4",
				"loadMovieNum"			=> "4",
				"loadVariables"			=> "4",
				"loadVariablesNum"		=> "4",
				"localToGlobal"			=> "4",
				"mbchr"				=> "4",
				"mblength"			=> "4",
				"mbord"				=> "4",
				"mbsubstring"			=> "4",
				"newline"			=> "4",
				"nextFrame"			=> "4",
				"nextScene"			=> "4",
				"null"				=> "4",
				"ord"				=> "4",
				"parseFloat"			=> "4",
				"parseInt"			=> "4",
				"play"				=> "4",
				"prevFrame"			=> "4",
				"prevScene"			=> "4",
				"print"				=> "4",
				"printAsBitmap"			=> "4",
				"printNum"			=> "4",
				"printAsBitmapNum"		=> "4",
				"random"			=> "4",
				"removeMovieClip"		=> "4",
				"set"				=> "4",
				"setProperty"			=> "4",
				"startDrag"			=> "4",
				"stop"				=> "4",
				"stopAllSounds"			=> "4",
				"stopDrag"			=> "4",
				"substring"			=> "4",
				"targetPath"			=> "4",
				"tellTarget"			=> "4",
				"this"				=> "4",
				"toggleHighQuality"		=> "4",
				"trace"				=> "4",
				"MMExecute"			=> "4",
				"true"				=> "4",
				"undefined"			=> "4",
				"unescape"			=> "4",
				"unloadMovie"			=> "4",
				"unloadMovieNum"		=> "4",
				"updateAfterEvent"		=> "4",
				"press"				=> "4",
				"release"			=> "4",
				"releaseOutside"		=> "4",
				"rollOver"			=> "4",
				"rollOut"			=> "4",
				"dragOver"			=> "4",
				"dragOut"			=> "4",
				"load"				=> "4",
				"enterFrame"			=> "4",
				"unload"			=> "4",
				"mouseMove"			=> "4",
				"mouseDown"			=> "4",
				"mouseUp"			=> "4",
				"keyDown"			=> "4",
				"keyUp"				=> "4",
				"data"				=> "4",
				"switch"			=> "3",
				"default"			=> "3",
				"case"				=> "3",
				"setInterval"			=> "4",
				"clearInterval"			=> "4",
				"#initclip"			=> "3",
				"#endinitclip"			=> "3",
				"addProperty"			=> "4",
				"registerClass"		=> "4",
				"enabled"			=> "4",
				"useHandCursor"		=> "4",
				"unwatch"			=> "4",
				"watch"			=> "4",
				"Button"			=> "4",
				"TextField"			=> "4",
				"Function"			=> "4",
				"apply"			=> "4",
				"call"				=> "4",
				"_global"			=> "4",
				"super"				=> "4",
				"LoadVars"			=> "4",
				"TextFormat"			=> "4",
				"autoSize"			=> "4",
				"removeTextField"		=> "4",
				"restrict"			=> "4",
				"createTextField"		=> "4",
				"html"				=> "4",
				"variable"			=> "4",
				"hscroll"			=> "4",
				"maxhscroll"			=> "4",
				"border"			=> "4",
				"background"			=> "4",
				"wordWrap"			=> "4",
				"password"			=> "4",
				"maxChars"			=> "4",
				"multiline"			=> "4",
				"textWidth"			=> "4",
				"textHeight"			=> "4",
				"selectable"			=> "4",
				"htmlText"			=> "4",
				"bottomScroll"			=> "4",
				"text"				=> "4",
				"embedFonts"			=> "4",
				"borderColor"			=> "4",
				"backgroundColor"		=> "4",
				"textColor"			=> "4",
				"font"				=> "4",
				"size"				=> "4",
				"color"			=> "4",
				"url"				=> "4",
				"target"			=> "4",
				"bullet"			=> "4",
				"tabStops"			=> "4",
				"bold"				=> "4",
				"italic"			=> "4",
				"underline"			=> "4",
				"align"			=> "4",
				"leftMargin"			=> "4",
				"rightMargin"			=> "4",
				"indent"			=> "4",
				"leading"			=> "4",
				"replaceSel"			=> "4",
				"getTextFormat"		=> "4",
				"setTextFormat"		=> "4",
				"getNewTextFormat"		=> "4",
				"setNewTextFormat"		=> "4",
				"getTextExtent"		=> "4",
				"getFontList"			=> "4",
				"condenseWhite"		=> "4",
				"TextSnapshot"			=> "4",
				"getCount"			=> "4",
				"setSelected"			=> "4",
				"getSelected"			=> "4",
				"getText"			=> "4",
				"getSelectedText"		=> "4",
				"hitTestTextNearPos"		=> "4",
				"findText"			=> "4",
				"setSelectColor"		=> "4",
				"addListener"			=> "4",
				"removeListener"		=> "4",
				"focusEnabled"			=> "4",
				"onPress"			=> "4",
				"onRelease"			=> "4",
				"onReleaseOutside"		=> "4",
				"onRollOver"			=> "4",
				"onRollOut"			=> "4",
				"onDragOver"			=> "4",
				"onDragOut"			=> "4",
				"onLoad"			=> "4",
				"onUnload"			=> "4",
				"onMouseDown"			=> "4",
				"onMouseMove"			=> "4",
				"onMouseUp"			=> "4",
				"onMouseWheel"			=> "4",
				"onKeyDown"			=> "4",
				"onKeyUp"			=> "4",
				"onData"			=> "4",
				"onChanged"			=> "4",
				"tabIndex"			=> "4",
				"tabEnabled"			=> "4",
				"tabChildren"			=> "4",
				"hitArea"			=> "4",
				"onSetFocus"			=> "4",
				"onKillFocus"			=> "4",
				"setMask"			=> "4",
				"Accessibility.isActive"	=> "4",
				"Accessibility.updateProperties"=> "4",
				"createEmptyMovieClip"		=> "4",
				"beginFill"			=> "4",
				"beginGradientFill"		=> "4",
				"moveTo"			=> "4",
				"lineTo"			=> "4",
				"curveTo"			=> "4",
				"lineStyle"			=> "4",
				"endFill"			=> "4",
				"clear"				=> "4",
				"_alpha"			=> "5",
				"_currentframe"			=> "5",
				"_droptarget"			=> "5",
				"_focusrect"			=> "5",
				"_framesloaded"			=> "5",
				"_height"			=> "5",
				"_highquality"			=> "5",
				"_lockroot"			=> "5",
				"_name"				=> "5",
				"_quality"			=> "5",
				"_rotation"			=> "5",
				"_soundbuftime"			=> "5",
				"_target"			=> "5",
				"_totalframes"			=> "5",
				"_url"				=> "5",
				"_width"			=> "5",
				"_visible"			=> "5",
				"_x"				=> "5",
				"_xmouse"			=> "5",
				"_xscale"			=> "5",
				"_y"				=> "5",
				"_ymouse"			=> "5",
				"_yscale"			=> "5",
				"CustomActions"			=> "4",
				"install"			=> "4",
				"list"				=> "4",
				"uninstall"			=> "4",
				"Stage"				=> "4",
				"showmenu"			=> "4",
				"align"				=> "4",
				"width"				=> "4",
				"height"			=> "4",
				"onResize"			=> "4",
				"System.capabilities"		=> "4",
				"System.security"		=> "4",
				"System.useCodepage"		=> "4",
				"exactSettings"		=> "4",
				"clear"			=> "4",
				"contentType"			=> "4",
				"createEmptyMovieClip"		=> "4",
				"curveTo"			=> "4",
				"height"			=> "4",
				"ignoreWhite"			=> "4",
				"lineTo"			=> "4",
				"moveTo"			=> "4",
				"onEnterFrame"			=> "4",
				"onScroller"			=> "4",
				"trackAsMenu"			=> "4",
				"type"				=> "4",
				"width"			=> "4",
				"duration"			=> "4",
				"position"			=> "4",
				"onSoundComplete"		=> "4",
				"onID3"				=> "4",
				"id3"				=> "4",
				"artist"			=> "4",
				"album"			=> "4",
				"songtitle"			=> "4",
				"year"				=> "4",
				"genre"			=> "4",
				"track"			=> "4",
				"comment"			=> "4",
				"COMM"			=> "4",
				"TALB"			=> "4",
				"TBPM"			=> "4",
				"TCOM"			=> "4",
				"TCON"			=> "4",
				"TCOP"			=> "4",
				"TDAT"			=> "4",
				"TDLY"			=> "4",
				"TENC"			=> "4",
				"TEXT"			=> "4",
				"TFLT"			=> "4",
				"TIME"			=> "4",
				"TIT1"			=> "4",
				"TIT2"			=> "4",
				"TIT3"			=> "4",
				"TKEY"			=> "4",
				"TLAN"			=> "4",
				"TLEN"			=> "4",
				"TMED"			=> "4",
				"TOAL"			=> "4",
				"TOFN"			=> "4",
				"TOLY"			=> "4",
				"TOPE"			=> "4",
				"TORY"			=> "4",
				"TOWN"			=> "4",
				"TPE1"			=> "4",
				"TPE2"			=> "4",
				"TPE3"			=> "4",
				"TPE4"			=> "4",
				"TPOS"			=> "4",
				"TPUB"			=> "4",
				"TRCK"			=> "4",
				"TRDA"			=> "4",
				"TRSN"			=> "4",
				"TRSO"			=> "4",
				"TSIZ"			=> "4",
				"TSRX"			=> "4",
				"TSSE"			=> "4",
				"TYER"			=> "4",
				"WXXX"			=> "4",
				"System.capabilities"		=> "4",
				"hasAudio"			=> "4",
				"hasMP3"			=> "4",
				"hasAudioEncoder"		=> "4",
				"hasVideoEncoder"		=> "4",
				"hasEmbeddedVideo"		=> "4",
				"screenResolutionX"		=> "4",
				"screenResolutionY"		=> "4",
				"screenDPI"			=> "4",
				"screenColor"			=> "4",
				"pixelAspectRatio"		=> "4",
				"hasAccessibility"		=> "4",
				"input"			=> "4",
				"isDebugger"			=> "4",
				"language"			=> "4",
				"manufacturer"			=> "4",
				"os"				=> "4",
				"serverString"			=> "4",
				"version"			=> "4",
				"hasPrinting"			=> "4",
				"playerType"			=> "4",
				"hasStreamingAudio"		=> "4",
				"hasScreenBroadcast"		=> "4",
				"hasScreenPlayback"		=> "4",
				"hasStreamingVideo"		=> "4",
				"avHardwareDisable"		=> "4",
				"localFileReadDisable"		=> "4",
				"windowlessDisable"		=> "4",
				"Error"				=> "4",
				"name"				=> "4",
				"message"			=> "4",
				"class"				=> "3",
				"extends"			=> "3",
				"public"			=> "3",
				"private"			=> "3",
				"static"			=> "3",
				"interface"			=> "3",
				"implements"			=> "3",
				"import"			=> "3",
				"dynamic"			=> "3",
				"get"				=> "4",
				"Void"				=> "4",
				"ContextMenu"			=> "4",
				"ContextMenuItem"		=> "4",
				"copy"				=> "4",
				"hideBuiltInItems"		=> "4",
				"onSelect"			=> "4",
				"builtInItems"			=> "4",
				"save"				=> "4",
				"zoom"				=> "4",
				"quality"			=> "4",
				"loop"				=> "4",
				"rewind"			=> "4",
				"forward_back"			=> "4",
				"print"			=> "4",
				"customItems"			=> "4",
				"caption"			=> "4",
				"separatorBefore"		=> "4",
				"visible"			=> "4",
				"attachVideo"			=> "4",
				"bufferLength"			=> "4",
				"bufferTime"			=> "4",
				"clear"			=> "4",
				"close"			=> "4",
				"connect"			=> "4",
				"currentFps"			=> "4",
				"height"			=> "4",
				"onStatus"			=> "4",
				"onResult"			=> "4",
				"pause"			=> "4",
				"play"				=> "4",
				"seek"				=> "4",
				"setBufferTime"		=> "4",
				"smoothing"			=> "4",
				"time"				=> "4",
				"bytesLoaded"			=> "4",
				"bytesTotal"			=> "4",
				"NetConnection"			=> "4",
				"NetStream"			=> "4",
				"Video"				=> "4",
				"PrintJob"			=> "4",
				"start"			=> "4",
				"addPage"			=> "4",
				"send"				=> "4",
				"paperWidth"			=> "4",
				"paperHeight"			=> "4",
				"pageWidth"			=> "4",
				"pageHeight"			=> "4",
				"orientation"			=> "4",
				"MovieClipLoader"		=> "4",
				"loadClip"			=> "4",
				"unloadClip"			=> "4",
				"getProgress"			=> "4",
				"onLoadStart"			=> "4",
				"onLoadProgress"		=> "4",
				"onLoadComplete"		=> "4",
				"onLoadInit"			=> "4",
				"onLoadError"			=> "4",
				"styleSheet"			=> "4",
				"StyleSheet"			=> "4",
				"parse"			=> "4",
				"parseCSS"			=> "4",
				"getStyle"			=> "4",
				"setStyle"			=> "4",
				"getStyleNames"		=> "4",
				"transform"			=> "4",
				"activityLevel"		=> "4",
				"allowDomain"			=> "4",
				"allowInsecureDomain"		=> "4",
				"attachAudio"			=> "4",
				"attachVideo"			=> "4",
				"bandwidth"			=> "4",
				"bufferLength"			=> "4",
				"bufferTime"			=> "4",
				"call"				=> "4",
				"clear"			=> "4",
				"close"			=> "4",
				"connect"			=> "4",
				"currentFps"			=> "4",
				"data"				=> "4",
				"deblocking"			=> "4",
				"domain"			=> "4",
				"flush"			=> "4",
				"fps"				=> "4",
				"gain"				=> "4",
				"get"				=> "4",
				"getLocal"			=> "4",
				"getRemote"			=> "4",
				"getSize"			=> "4",
				"height"			=> "4",
				"index"			=> "4",
				"isConnected"			=> "4",
				"keyFrameInterval"		=> "4",
				"liveDelay"			=> "4",
				"loopback"			=> "4",
				"motionLevel"			=> "4",
				"motionTimeOut"		=> "4",
				"menu"				=> "4",
				"muted"			=> "4",
				"name"				=> "4",
				"names"			=> "4",
				"onActivity"			=> "4",
				"onStatus"			=> "4",
				"onSync"			=> "4",
				"pause"			=> "4",
				"play"				=> "4",
				"publish"			=> "4",
				"quality"			=> "4",
				"rate"				=> "4",
				"receiveAudio"			=> "4",
				"receiveVideo"			=> "4",
				"seek"				=> "4",
				"send"				=> "4",
				"setBufferTime"		=> "4",
				"setFps"			=> "4",
				"setGain"			=> "4",
				"setKeyFrameInterval"		=> "4",
				"setLoopback"			=> "4",
				"setMode"			=> "4",
				"setMotionLevel"		=> "4",
				"setQuality"			=> "4",
				"setRate"			=> "4",
				"setSilenceLevel"		=> "4",
				"setUseEchoSuppression"	=> "4",
				"showSettings"			=> "4",
				"setClipboard"			=> "4",
				"silenceLevel"			=> "4",
				"silenceTimeOut"		=> "4",
				"smoothing"			=> "4",
				"time"				=> "4",
				"uri"				=> "4",
				"useEchoSuppression"		=> "4",
				"width"			=> "4",
				"Camera"			=> "4",
				"LocalConnection"		=> "4",
				"Microphone"			=> "4",
				"SharedObject"			=> "4",
				"System"			=> "4"
		);
				
	}
}
