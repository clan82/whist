<?php


class WhistException extends Exception {


	function __construct($message, $code = 0, $previous = null) {
		parent::__construct($message, $code, $previous);
	}


}

