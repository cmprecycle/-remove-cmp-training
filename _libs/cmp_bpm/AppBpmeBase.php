<?php
abstract class AppBpmeBase
{
	//public function __construct();
	abstract public function enqueueFlowObject( $options );
	abstract public function queryLatestResultUntilTimeout( $param );
	abstract public function pulse();
}
