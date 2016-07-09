<?php
interface IAppBpme
{
	//public function __construct();
  public function onEvent( $eventName, $info );
	public function buildFlowObjectID();
	public function enqueueFlowObject( $options );
	public function queryLatestResultUntilTimeout( $param );
	public function pulse();
}
