<?php
html_header('Contact');
menu();
baseline("contact");
?>
<div id="containerText" >

<p>I am <a href="https://tcrouzet.com/">Thierry Crouzet</a>, a French writer, bikepacker, and developer.
I organize, among other things, <a href="https://727bikepacking.fr/">the 727 series, bikepacking adventures in the south of France</a>.
I got tired of tracking solutions that involve the use of expensive trackers.
So, I decided to develop Geogram, a free solution that relies on the Telegram messaging app.
I am improving the application as requests come in. Don't hesitate to contact me.</p>

<form
action="https://formspree.io/f/mgebvkwn"
method="POST">
<label>Email:<input type="email" name="email" style="width: 100%"></label><br>
<label>Message:<br><textarea name="message" style="width: 100%;height:10rem"></textarea><br></label><br>
<button type="submit">Post</button><br></form>

</div>