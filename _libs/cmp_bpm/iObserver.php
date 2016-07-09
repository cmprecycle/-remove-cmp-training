<?php
interface iObserver
{
	public function onEvent( $eventname, $info );
}

