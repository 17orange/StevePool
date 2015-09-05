<?php

	class SqlException extends Exception{
		public function __construct( $sqlError ){
			$this->message = $sqlError; //"<h3><i>Something Went Wrong...</i></h3>\n";
			$this->sqlError = $sqlError;
		}
	}

?>