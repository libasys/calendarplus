<?php

 namespace OCA\CalendarPlus;
 
class Jobs{
	
	static public function checkAlarm() {
		\OCP\Util::writeLog(App::$appname,'Cron Done:'.time() ,\OCP\Util::DEBUG);
	}
	public static function run() {
		\OCP\Util::writeLog(App::$appname,'Cron run Done:'.time() ,\OCP\Util::DEBUG);
	}
}