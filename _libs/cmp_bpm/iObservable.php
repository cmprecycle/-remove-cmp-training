<?php
interface iObservable
{
	public function addEventListener( $eventname, iObserver $observer );
	public function notify( $eventname, $info );
}

