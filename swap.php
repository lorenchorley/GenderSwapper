<?php 
define("debug_mode", true); 
?>

<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
		<style>

h1 {
	text-align: center;
}

.central_column {
	margin: 0 auto;
	width: 80%;
	padding: 0 40px;
}

.dual_column .left {
	float: left;
	width: 47%;
}

.dual_column .right {
	float: right;
	width: 47%;
}

.relative {
	position: relative;
}

.pair {
	
}

.pair br {
	height: 2em;
}

.pair.symmetric {
	
}

.pair .left {
	position: absolute;
	right: 50%;
	padding-right: 20px;
}

.pair .right {
	position: absolute;
	left: 50%;
	padding-left: 20px;
}

.pair:before {
    position: absolute;
    font-size: 13px;
    left: 50%;
    transform: translateX(-50%);
}

.pair.symmetric:before {
    content: "<->";
}

.pair.forward:before {
    content: "->";
}

.pair.backward:before {
    content: '<-';
}

.clear {
   clear: both;
}


span.transformed {
    background-color: #DCDDFF;
}

span.transformed.ambiguous {
    background-color: #FFC7C7;
}

		</style>
		<script src="https://code.jquery.com/jquery-2.2.1.min.js"></script>
		<script>
			
(function ($) {
	$(document).ready(function () {
		
		var $thetext = $('#thetext');
		var $lefts = $('#thetext article.left');
		var $rights = $('#thetext article.right');
		
		if ($lefts.length !== $rights.length)
			throw "Not an equal number of chapters";
		
		$thetext.empty();
		for (var i = 0; i < $lefts.length; i++) {
			$thetext.append($lefts[i]);
			$thetext.append($rights[i]);
			$thetext.append('<div class="clear"></div>');
		}
		
		$lefts.prepend('<p>');
		$lefts.find('br').replaceWith(function () { return '</p><p>'; });
		$lefts.append('</p>');
		
		$rights.prepend('<p>');
		$rights.find('br').replaceWith(function () { return '</p><p>'; });
		$rights.append('</p>');
		
	})
})(jQuery);

		</script>
	</head>
	<body>

<?php

function GetPlaceholder($str) {
	return "j3kt7TC87Otlz..".str_replace(" ", "_", $str)."..j3kt7TC87Otlz";
}

$originaltextfilename = "text/original.txt";
$transformationsfilename = "text/transformations.json";

$transform_original_to_placeholder = array();
$transform_target_to_original = array();
$transform_placeholder_to_target = array();
$transform_original_to_target = array();

// Get original text
$originaltextfile = fopen($originaltextfilename, "r") or die("Unable to open file!");
$thetext = fread($originaltextfile, filesize($originaltextfilename));
fclose($originaltextfile);
$theoriginaltext = $thetext;

// Load transformation array
$transformationscontents = file_get_contents($transformationsfilename);
$transformationsjson = json_decode($transformationscontents, true);
$jsonIterator = new RecursiveIteratorIterator(
				new RecursiveArrayIterator($transformationsjson),
				RecursiveIteratorIterator::SELF_FIRST);

function PrintPair($original, $target, $type) { 
	if (debug_mode) {
	?>
	<div class="pair relative <?php echo $type; ?>">
		<div class="left"><?php echo ucfirst($original); ?></div>
		<div class="right"><?php echo ucfirst($target); ?></div>
		<br/>
	</div>
<?php }
}

$test = array(
	"one" => "oneval",
	"two" => "twoval"
);

function SetSafely($array, $key, $val, $suppress_subarrays = false) {
	if ($key == "" || $val == "")
		return $array;
		
	if (array_key_exists($key, $array)) {
		if (!$suppress_subarrays) {
			$ak = $array[$key];
			if (is_array($ak)) {
				if (!in_array($val, $ak))
					$array[$key][] = $val;
			} else {
				if ($ak != $val) {
					unset($array[$key]);
					$array[$key] = array(
						$ak,
						$val
					);
				}
			}
		}
	} else {
		$array[$key] = $val;
	}
	return $array;
}

$not_to_pluralise = array("mr", "ms", "mrs", "miss", "his", "him");
function FormPlural($word) {
	global $not_to_pluralise;
	$lword = strtolower($word);
	if (in_array($lword, $not_to_pluralise)) { 
		return "";
	} else if (substr($lword, -1) == "s") {
		return $word."es"; // TODO other possibilities
	} else if (substr($lword, -3) == "man") {
		return substr($word, 0, strlen($word) - 3)."men";
	} else if (substr($lword, -4) == "trix") {
		return substr($word, 0, strlen($word) - 4)."trices";
	} else if (substr($lword, -4) == "self") {
		return substr($word, 0, strlen($word) - 4)."selves";
	} else if (substr($lword, -4) == "wife") {
		return substr($word, 0, strlen($word) - 4)."wives";
	} else if (substr($lword, -3) == "ady") {
		return substr($word, 0, strlen($word) - 3)."adies";
	} else {
		return $word."s";
	}
}

function SaveTransformPair($original, $target, $type, $suppress_capitalisation = false, $suppress_pluralisation = false) {
	global $transform_original_to_placeholder;
	global $transform_target_to_original;
	global $transform_placeholder_to_target;
	global $transform_original_to_target;
	
	// TODO set entries as arrays if already there (ambiguous transformations)
	
	$placeholder = GetPlaceholder($original);
	
	if (!$suppress_capitalisation) {
		$original_c = ucfirst($original);
		$target_c = ucfirst($target);
		$placeholder_c = GetPlaceholder($original_c);
	}
	
	if (!$suppress_pluralisation) {
		$original_s = FormPlural($original);
		$target_s = FormPlural($target);
		$placeholder_s = GetPlaceholder($original_s);
		
		if (!$suppress_capitalisation) {
			$original_cs = ucfirst($original_s);
			$target_cs = ucfirst($target_s);
			$placeholder_cs = GetPlaceholder($original_cs);
		}
	}
	
	if ("symmetric" == $type || "backward" == $type) {
		$transform_target_to_original = SetSafely($transform_target_to_original, $target, $original);
		
		// Add an entry for the capitalisations
		if (!$suppress_capitalisation)
			$transform_target_to_original = SetSafely($transform_target_to_original, $target_c, $original_c);
		
		// Add entries for plurals and capitalisations of plurals
		if (!$suppress_pluralisation) {
			$transform_target_to_original = SetSafely($transform_target_to_original, $target_s, $original_s);
			
			if (!$suppress_capitalisation)
				$transform_target_to_original = SetSafely($transform_target_to_original, $target_cs, $original_cs);
		}
		
	}
	
	if ("symmetric" == $type || "forward" == $type) {
		$transform_original_to_placeholder = SetSafely($transform_original_to_placeholder, $original, $placeholder, true);
		$transform_placeholder_to_target = SetSafely($transform_placeholder_to_target, $placeholder, $target);
		$transform_original_to_target = SetSafely($transform_original_to_target, $original, $target);
		
		// Add entries for the capitalisations
		if (!$suppress_capitalisation) {
			$transform_original_to_placeholder = SetSafely($transform_original_to_placeholder, $original_c, $placeholder_c, true);
			$transform_placeholder_to_target = SetSafely($transform_placeholder_to_target, $placeholder_c, $target_c);
			$transform_original_to_target = SetSafely($transform_original_to_target, $original_c, $target_c);
		}
		
		// Add entries for plurals and capitalisations of plurals
		if (!$suppress_pluralisation) {
			$transform_original_to_placeholder = SetSafely($transform_original_to_placeholder, $original_s, $placeholder_s, true);
			$transform_placeholder_to_target = SetSafely($transform_placeholder_to_target, $placeholder_s, $target_s);
			$transform_original_to_target = SetSafely($transform_original_to_target, $original_s, $target_s);
			
			if (!$suppress_capitalisation) {
				$transform_original_to_placeholder = SetSafely($transform_original_to_placeholder, $original_cs, $placeholder_cs, true);
				$transform_placeholder_to_target = SetSafely($transform_placeholder_to_target, $placeholder_cs, $target_cs);
				$transform_original_to_target = SetSafely($transform_original_to_target, $original_cs, $target_cs);
			}
		}
	}
	
}

echo '<div class="central_column relative">';
foreach ($jsonIterator as $key => $val) {
	
	if (is_array($val)) {
		
		switch ($key) {
		case "symmetric_transformations":
			foreach ($val as $original => $target) {
				$target = strtolower($target); $original = strtolower($original);
				PrintPair($original, $target, "symmetric");
				SaveTransformPair($original, $target, "symmetric");
			}
			break;
		case "forward_transformations":
			foreach ($val as $original => $target) {
				$target = strtolower($target); $original = strtolower($original);
				PrintPair($original, $target, "forward");
				SaveTransformPair($target, $original, "forward");
			}
			break;
		case "backward_transformations":
			foreach ($val as $target => $original) {
				$target = strtolower($target); $original = strtolower($original);
				PrintPair($original, $target, "backward");
				SaveTransformPair($original, $target, "backward");
			}
			break;
		case "forward_exclusions":
		
			break;
		case "backward_exclusions":
			
			break;
		case "forward_unfiltered_inclusions":
			foreach ($val as $original => $target) {
				PrintPair($original, $target, "forward");
				SaveTransformPair($original, $target, "forward", true, true);
			}
			break;
		case "backward_unfiltered_inclusions":
			foreach ($val as $target => $original) {
				PrintPair($original, $target, "backward");
				SaveTransformPair($original, $target, "backward", true, true);
			}
			
			break;
		}
	}
}
echo '</div>';

echo "transform_original_to_target";
print_r($transform_original_to_target);
echo "transform_target_to_original";
print_r($transform_target_to_original);

// Define replacement functions
function Wrap($str, $tagname, $attr = " ") {
	if (" " !== $attr) {
		if (is_array($attr)) {
			$tmp = " ";
			foreach ($attr as $k => $v) {
				$tmp .= "$k='$v' ";
			}
			$attr = $tmp;
		}
	}
	return "<".$tagname.$tmp.">".$str."</".$tagname.">";
}

function ReplaceWord($word, $replacement, $str) {
	return preg_replace('/\b' . $word .  '\b/', $replacement, $str);
}

function ReplaceUsingTransformationArray($array, $suppress_tags = false) {
	global $thetext;
	global $array_bak, $suppress_tags_bak;
	$array_bak = $array; $suppress_tags_bak = $suppress_tags;
	$joined = "";
	foreach ($array as $key => $val) {
		if ($joined == "") {
			$joined .= $key;
		} else {
			$joined .= '\b|\b'.$key;
		}
	}
	return preg_replace_callback(
					'/\b' . $joined . '\b/', 
					function ($matches) {
						global $array_bak;
						
						$transformation = $array_bak[$matches[0]];
						if (is_array($transformation)) {
							if ($suppress_tags_bak) {
								return join(", ", $transformation);
							} else {
								return Wrap(join(", ", $transformation), "span", array("class" => "transformed ambiguous"));
							}
						} else {
							if ($suppress_tags_bak) {
								return $transformation;
							} else {
								return Wrap($transformation, 'span', array("class" => "transformed"));
							}
						}
					},
					$thetext);
}

// Make the primary substitutions
$thetext = ReplaceUsingTransformationArray($transform_original_to_placeholder, true);
$thetext = ReplaceUsingTransformationArray($transform_target_to_original);
$thetext = ReplaceUsingTransformationArray($transform_placeholder_to_target);

// Replace \n with <br/>
$thetext = nl2br($thetext);
$theoriginaltext = nl2br($theoriginaltext);

// Split into chapters and format headings
$thetext = ReplaceWord("Chapter ([0-9]*)", '</article><article class="left">'.Wrap("Chapter $1", "h1"), $thetext);
$theoriginaltext = ReplaceWord("Chapter ([0-9]*)", '</article><article class="right">'.Wrap("Chapter $1", "h1"), $theoriginaltext);

		?>
		<div id="thetext" class="central_column dual_column relative">
			<article><?php echo $thetext; ?></article>
			<article><?php echo $theoriginaltext; ?></article>
		</div>
	</body>
</html>