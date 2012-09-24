<?php


function render_page($subtitle, $headline, $view, $view_data) {
	$dir = dirname(__FILE__);
	require($dir . "/views/layout.php");
}


function render_page_and_exit($subtitle, $headline, $view, $view_data) {
	render_page($subtitle, $headline, $view, $view_data);
	exit;
}


function render_view($view, $data) {
	extract($data);
	require(dirname(__FILE__) . "/views/" . $view . ".php");
}


function request_method() {
	return $_SERVER['REQUEST_METHOD'];
}


function redirect_path($path) {
	$url = $SETTINGS["baseUrl"] . $path;
	header("Location: $url");
	exit;
}


function option($value, $content) {
	?>
	<option value="<?php echo $value ?>"><?php echo $content ?></option>
	<?php
}


function checkbox($name_and_id) {
	?>
	<input type="hidden" name="<?php echo $name_and_id ?>" value="off" />
	<input type="checkbox" name="<?php echo $name_and_id ?>" value="on" id="<?php echo $name_and_id ?>" />
	<?php
}


function multi_checkbox($name, $value) {
	?>
	<input type="checkbox" name="<?php echo $name ?>[]" value="<?php echo $value ?>" id="<?php echo $name . "-" . $value ?>" />
	<?php
}


function multi_checkbox_label($name, $value, $content) {
	?>
	<label for="<?php echo $name . "-" . $value ?>"><?php echo $content ?></label>
	<?php
}


function check_get_multi_checkbox_array($map, $param, &$valid_values) {
	if (!isset($map[$param])) {
		// No checkboxes are checked
		return array();
	}
	$param = $map[$param];
	if (!is_array($param)) {
		// Error - not an array
		return NULL;
	}
	foreach ($param as $value) {
		if (!isset($valid_values[$value])) {
			// Invalid value
			return NULL;
		}
	}
	return $param;
}


function check_get_enum($map, $param, &$valid_values, $allow_blank) {
	if (!isset($map[$param])) {
		// Missing input
		return NULL;
	}
	$param = $map[$param];
	if ($allow_blank && $param === '') {
		return $param;
	}
	if (!isset($valid_values[$param])) {
		// Invalid value
		return NULL;
	}
	return $param;
}


function check_get_string($map, $param) {
	if (!isset($map[$param])) {
		// Missing input
		return NULL;
	}
	$param = $map[$param];
	if (!is_string($param)) {
		// Error - not a string
		return NULL;
	}
	return $param;
}


function check_get_indexed_array($map, $param, $length = NULL, $value_validator = NULL) {
	if (!isset($map[$param])) {
		// Missing input
		return NULL;
	}
	$param = $map[$param];
	if (!is_array($param)) {
		// Error - not a string
		return NULL;
	}
	if ($length != NULL && count($param) != $length) {
		// Invalid length
		return NULL;
	}
	$expected_key = 0;
	foreach ($param as $key => $value) {
		if (!is_int($key) || $key !== $expected_key) {
			return NULL;
		}
		if ($value_validator && !$value_validator($value)) {
			return NULL;
		}
		$expected_key++;
	}
	return $param;
}


function check_get_uint($map, $param, $allow_blank = FALSE) {
	$param = check_get_string($map, $param);
	if ($param === NULL) {
		return NULL;
	}
	if ($param === '') {
		if ($allow_blank) {
			return $param;
		} else {
			return NULL;
		}
	}
	if (!ctype_digit($param)) {
		return NULL;
	}
	return (int) $param;
}


function check_input() {
	$params = func_get_args();
	foreach ($params as $param) {
		if (is_null($param)) {
			render_unexpected_input_page_and_exit("Missing parameter or invalid type/value!");
		}
	}
}


function render_unexpected_input_page_and_exit($message = NULL) {
	$data = array('message' => $message);
	render_page_and_exit("Unexpected input", "Unexpected input", "unexpected_input", $data);
}


function array_filter_entries($array, $source_key_prefix, $keys) {
	$sub_array = array();
	foreach ($keys as $key) {
		$sub_array[$key] = $array[$source_key_prefix . $key];
	}
	return $sub_array;
}


function array_map_nulls($array, $null_replacement) {
	$out = array();
	foreach ($array as $entry) {
		if ($entry === NULL) {
			$out[] = $null_replacement;
		} else {
			$out[] = $entry;
		}
	}
	return $out;
//	$f = function($e) {
//				if ($e === NULL) {
//					return $null_replacement;
//				} else {
//					return $e;
//				}
//			};
//	return array_map($f, $array);
}


function nonnull_index($array, $key) {
	return isset($array[$key]) && $array[$key] !== NULL;
}