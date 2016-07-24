mybb-ultimatecaptcha README
=======================
Michael Campbell
24th July 2016

mybb-ultimatecaptcha uses code from MyBB Captchapack for the plugin, but I had to write all of the rendering code myself

Dependencies
------------

* MyBB 1.6.*

* link:http://community.mybb.com/thread-87399.html[>= PluginLibrary 11]

Install
-------

Copy the files from the upload directory to the root directory of your MyBB installation.

Status
------

* mybb-ultimatecaptcha is only in its testing stages at the moment, but I have used it in production for testing. There are probably still plenty of bugs, so using it on productive sites is highly discouraged. For all possible outcomes of using the plugin (including but not limited to burning your CPU, taking down your webhost (believe it or not this can actually happen) or disabling your website) I'm not liable.

* This plugin has only been tested on MySQL. I do not know whether it works on other database engines.

* These CAPTCHAs are based on the rendering of GIFs or PNGs, so will use some resources on your server and will probably not work on shared hosting.

License
-------

GPL-2+? I copied some code from the original MyBB CAPTCHA Pack module, distributed under GPL-2, notably the MyBB plugin code.
AGPL3 for the extra code that I have added.
