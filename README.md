Calendar+ App
=============

Maintainer:
===========
Sebastian Döll

Version Info:
============
1.0.2

Setup Info:
===========
This version of the calendar app is only compatible with upcoming ownCloud Version 8.1 or later!

The old sharees won't work if you export the database shema from an old installation of ownCloud!
You can use *calendarplus* parallel to your current calendar application: it works on its own tables, so you can thoroughly test it without the risk of messing up your existing calendars. If you want to use your old calendar events, you have to export them using a desktop calendar application or the web interface, and import them to *calendarplus.* It won't work to simply export the database from an older installation of ownCloud.


Installation:
=============
Download the zip file and rename folder from calendarplus-master to calendarplus! Upload the app to your apps directory and activate it on the apps settings page!

Import
======
- 1. Method
ICS File per Drag & Drop on the calendar+ app,  import dialog will prompt
- 2. Method
Upload the ICS File on the files app and then click on the uploaded ICS File and the import dialog will prompt

Important:
If the default calendar app is enabled, too, then you have to disable the calendar app for import from the files app!

Caldav Addresses:
==================
The syncing URL is shown up in the settings dialog (right sidebar top corner)

New Features:
=============
- Sharing calendar via public link 
- Sharing events via public link
- Sharing of subscribed calendars
- Customizing public sharings via link on share.config.js
- Exdate for repeating events (means you can delete a single event of a series)
- Calendar subscription
- Reminder support
- Repeat GUI changed
- New/ Edit event GUI changed
- Timezone support (daylight/ standard) for repeating events (if supported!)
- Working Search function with additional search options like: today, tomorrow
- activity log

