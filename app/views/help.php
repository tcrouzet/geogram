
<?php include 'header.php'; ?>

<main>
<div id="splash">

<p style="text-align:center"><a href="https://geogram-tcrouzet-com.translate.goog/help?_x_tr_sl=de&_x_tr_tl=fr&_x_tr_hl=fr&_x_tr_pto=wapp">Version française (traduction automatique)</a></p>

<p>Geogram is a free service to share and document adventures in semi-real-time.
It helps adventurers to locate themselves in relation to each other.
It enables friends and fans to experience the adventure through map tracking, photos, and comments posted by adventurers.</p>

<h2 id="why">Why choose Telegram over WhatsApp</h2>
<p><b>Open Source</b>: <a href="https://github.com/telegramdesktop/tdesktop">Telegram's client code</a> is open source, allowing for transparency and community scrutiny. This ensures that potential vulnerabilities are identified and addressed promptly.</p>
<p><b>Open API</b>: <a href="https://telegram.org/apps">Telegram</a> provides an open API, enabling developers to create custom apps and integrations like Geogram. This fosters innovation and expands Telegram’s functionality beyond its core features.</p>
<p><b>No Better Security</b>: <a href="https://tsf.telegram.org/manuals/e2ee-simple">Telegram does not offer robust security with end-to-end encryption in groups</a>.</p>
<p>In summary, Telegram's open-source nature and open API features make it a preferable choice for users seeking a messaging platform for sharing geolocation (no secrets).</p>

<h2>Follow an adventure</h2>
<ol>
<li><a href="/">Geogram's homepage</a> lists ongoing adventures (<a href="archives">Archives</a> past adventures).</li>
<li>Once an adventure is selected, <a href="727bikepacking">for example 727</a>, a map indicates the adventurers' last known position, identified by their username if defined, otherwise by their name/first name.</li>
<li>Before the adventure starts, the adventurers may be scattered across the country. Zoom out to find them.</li>
<li>When clicking on a tag, the adventurer's name is highlighted in the list (and vice versa).</li>
<li>For each adventurer, the distance traveled since the start and the elevation climbed are displayed
    (calculated data based on the displayed route on the map).</li>
<li>If an adventurer has posted a photo or a message following their last geolocation, icons indicate this in the list.</li>
<li>In the list, clicking on the + to the left of the adventurer's name takes you to their history (<a href="/727bikepacking/user/6254152278">example</a>).
    You can discover their geolocations, messages, and photos in reverse chronological order. Clicking on an icon has the same effect.</li>
<li>An adventure story is generated from the published information (<a href="727bikepacking/story">example</a>),
    supplemented with automatically downloaded data (name of paths, streets, cities, weather, etc.).</li>
<li>On iPhone, <a href="https://www.icloud.com/shortcuts/783e3c4306e04626976b5c59da2e9987">this link creates a quick access shortcut</a> to Geogram.
Once the shortcut is created, it can be edited by clicking on [...]. Clicking on Geogram adds the shortcut to the home screen.
On Android, open <a href="/">Geogram</a> in Chrome, then click on the 3 dots and select Add to Home Screen.</li>

</ol>

<h2 id="join">Join an adventure</h2>
<ol>
<li>Install <a href="https://telegram.org/apps">Telegram Messenger</a> on your mobile phone.</li>
<li>Create a Telegram account and associate it with a mobile number. To remain anonymous, use a pseudonym as your username.</li>
<li>On Telegram, joint the adventure group (if it's private, the organizer provides a link that should not be shared).</li>
<li>Mute the group to avoid being alerted for every message posted (open the group and click on its name at the top to access the option).</li>
<li>Pin the group to keep it at the top of your contact list.</li>
<li>In the group, occasionally post your geolocation.
Click on the Paperclip on the side of the input field.</li>
<img src="/images/help-paperclip.png" />
<li>Then click on the Location icon and choose Send my current location.
Note: it takes a few seconds for Telegram to locate the phone. Do not publish the geolocation too quickly, otherwise it will be inaccurate.</li>
<img src="/images/help-location.png" />
<li>You can post even if your phone is not connected. Telegram will send messages when the phone will find a network.</li>
<li>You can geolocate once every 10 minutes (if you geolocate more often, posts will be deleted).
Warning: if the admin autorize that, you can geolocate in real time for up to 8 hours, but it will drain your phone battery.</li>
<li>The photos and texts posted are associated with the last geolocation.
You can post multiple photos at once, preferably in landscape format.
Videos are not supported.</li>
<li>Depending of admin paramaremers, Geolocation messages can be automatically deleted to avoid cluttering the group.
Other messages can remain visible and allow adventurers to exchange messages.</li>
<li>Manage your options with /menu command. If you set a profile picture on Telegram, you can transfert it to Geogram with "Avatar".
The avatar serves as a cursor on the map, otherwise a simple colored dot is used</li>
</ol>

<h2 id="help_admin" id="add">Add your own adventure</h2>
<ol>
<li>First you must create a <a href="https://telegram.org/apps">Telegram account</a>.</li>
<li>On <a href="https://telegram.org/apps">Telegram</a>, create a group for the adventure, adding <a href="https://t.me/GeoBikepacking_bot" target="_blank">GeoBikepacking_bot</a> bot as the first member. Name the group explicitly.</li>
<li>Make <a href="https://t.me/GeoBikepacking_bot" target="_blank">GeoBikepacking bot</a> a group administrator (open the group, click on the group name, select Edit Group, then select Administrators, and add GeoBikepacking_bot).</li>
<li>Define groups permissions like that.</li>

<img src="/assets/img/help-perms.jpg" />

<li>Post an initial geolocation. The new group will appear on the <a href="/">homepage</a>.</li>
<li>Associate an image with the group. It will identify the group on the <a href="/">homepage</a>.</li>
<li>Optional, but far better. Post the GPX track of the adventure in the group. It will be displayed on the map.
    Repost the GPX to update the route. No more than 30,000 points in the track after optimisation (more than 3 000km).</li>
<li>The command "/menu" displays the admin and user dashbords.</li>
<li>To create en open and persistent adventure, like <a href="727itt">727itt</a>, share publicly the Telegram group invite link.
    The adventurers will disappear after one week of inactivity.
    This adventure will never be archived.
</li>
<li>Unit: choose the units (metric or imperial) and adjust timezone if needded.</li>
<li>Start: will start the timer of the adventure.
    Without this step, it is impossible to calculate the time spent on the route.</li>
<li>Archive: stops the adventure. No more messages will be considered. The GPX database for geolocalisation is deleted.</li>
<li>Description: describes the adventure on the <a href="">homepage</a>.</li>
<li>Mode: define when the messages are deleted or not.</li>
<li>Purge: delete all messages (usefull before the start of the adventure, and after a testing period).</li>
<li>Delete: delete every thing. The same result occur when you unsubscribe GeoBikepacking_bot.</li>
<li>An inactive adventure for more than one month is automatically archived.</li>

</ol>

</div>
</main>
