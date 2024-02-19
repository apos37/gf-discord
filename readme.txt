=== Add-On for Discord and Gravity Forms ===
Contributors: apos37
Donate link: https://paypal.com/donate/?business=3XHJUEHGTMK3N
Tags: discord, server, gravity, forms, chat, webhook
Requires at least: 5.9.0
Tested up to: 6.4.3
Stable tag: 1.0.5
License: GPL v2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.txt

Automatically send Gravity Form entries to a Discord channel.

== Description ==
Automatically send Gravity Form entries to a Discord channel using an Incoming Webhook.

== Installation ==
1. Install the plugin from your website's plugin directory, or upload the plugin to your plugins folder. 
2. Activate it.
3. Go to Gravity Forms > Settings > Discord.

= Where can I request features and get further support? =
Join my [WordPress Support Discord server](https://discord.gg/3HnzNEJVnR)

= How can I mention a user or tag a channel in my messages? =
From the form's Discord feed, you can mention a user with {{@user_id}} and tag a channel with {{#channel_id}}. If you're unfamiliar with where to find these IDs, check out [this article](https://support.discord.com/hc/en-us/articles/206346498-Where-can-I-find-my-User-Server-Message-ID) on Discord.

== Screenshots ==
1. Plugin settings page
2. Form feed settings page
3. Entry page
4. Discord channel post

== Changelog ==
= 1.0.5 =
* Fix: & symbol displayed as &amp;
* Fix: Deprecation notice passing # in hexdec()
* Update: Added support for pinging someone via the feed message box using {{{@user_id_number}}}
* Fix: URL back to form entry not working properly

= 1.0.4 =
* Tweak: Removed some comments

= 1.0.3 =
* Tweak: Updated Discord link

= 1.0.2 =
* Update: Added option for removing footer altogether on feeds
* Update: Added field to form settings for customizing the footer (props enes#4893)
* Fix: Removed "Test 3" from footer

= 1.0.1 =
* Created plugin on March 16, 2023