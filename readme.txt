=== Add-On for Discord and Gravity Forms ===
Contributors: apos37
Tags: discord, server, gravity, forms, webhook
Requires at least: 5.9
Requires PHP: 7.4
Tested up to: 6.8
Stable tag: 1.2.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.txt

Automatically send Gravity Form entries to a Discord channel.

== Description ==
The "Add-On for Discord and Gravity Forms" WordPress plugin is a fantastic tool that bridges the gap between your website's forms and your Discord community! It seamlessly integrates Gravity Forms, a popular form builder plugin, with Discord, a leading communication platform.

With this add-on, you can:

* Automatically send form submissions to a designated Discord channel
* Map form fields to Discord message embeds, making it easy to display user-submitted data
* Trigger custom notifications and messages based on form responses
* Enhance user engagement and community interaction

This plugin is perfect for:

* Community managers who want to centralize form submissions and discussions
* Developers who need to streamline form data and notifications
* Site owners who want to foster a more interactive and responsive community
* Those that have unreliable email systems

By connecting Gravity Forms and Discord, this add-on simplifies communication, enhances user experience, and boosts community engagement! It's a win-win for anyone looking to supercharge their online interactions!

== Installation ==
1. Install the plugin from your website's plugin directory, or upload the plugin to your plugins folder. 
2. Activate it.
3. Go to Gravity Forms > Settings > Discord.

== Frequently Asked Questions == 
= Where can I request features and get further support? =
Join my [Discord support server](https://discord.gg/3HnzNEJVnR)

= How can I mention a user or tag a channel in my messages? =
From the form's Discord feed, you can mention a user with `{{@user_id}}` or a role with `{{@&role_id}}`, and tag a channel with `{{#channel_id}}`. If you're unfamiliar with where to find these IDs, check out [this article](https://support.discord.com/hc/en-us/articles/206346498-Where-can-I-find-my-User-Server-Message-ID) on Discord.

= How can I further customize the message sent to Discord? =
With version 1.0.6, you can now use the following hook:

`<?php
add_filter( 'gf_discord_embeds', 'my_gf_discord_embeds', 10, 3 );
function my_gf_discord_embeds( $embeds, $form, $entry ) {
	// Filter the message
	$embeds[0][ 'description' ] = str_replace( '{{my_own_merge_tag}}', 'New Value', $embeds[0][ 'description' ] );

	// Add a new field
	$user_id = $entry[ 'created_by' ];
	$user = get_user_by( 'ID', $user_id );
	$display_name = $user->display_name;
	
	$embeds[0][ 'fields' ][] = [
		'name'  => 'Completed By:',
		'value' => $display_name
	];

	// Always return embeds
	return $embeds;
} // End my_gf_discord_embeds()
?>`

== Demo ==
https://youtu.be/KkT-wd6l7bI

== Screenshots ==
1. Plugin settings page
2. Form feed settings page
3. Entry page
4. Discord channel post

== Changelog ==
= 1.2.1 =
* Update: Updated author name and website again per WordPress trademark policy

= 1.2.0 =
* Update: Changed author name from Apos37 to WordPress Enhanced, new Author URI
* Tweak: Optimization

= 1.1.3 =
* Update: Added My Feeds section to the plugin settings so you can quickly see where you have set them up

= 1.1.2 =
* Update: Added support for file uploads; now shows link to files in Discord embed
* Update: Added a notice on plugins page if GF is not activated

= 1.1.1 =
* Tweak: Verify compatibility with WP 6.6.2
* Tweak: Update Gravity Forms logo

= 1.1.0 =
* Fix: Warnings from Plugin Checker

= 1.0.9 =
* Update: Added support for other post custom fields
* Fix: Multiselect post custom fields not showing all values (props calamarigold)

= 1.0.8 =
* Fix: Fatal error undefined function (props calamarigold)

= 1.0.7 =
* Fix: Multiselect fields not showing all values (props calamarigold)
* Tweak: Removed required email field (props calamarigold)

= 1.0.6 =
* Update: Added filter for embeds to further customize message
* Tweak: Added support for mentioning a role via the feed message box using `{{@&role_id}}`

= 1.0.5 =
* Fix: & symbol displayed as &amp;
* Fix: Deprecation notice passing # in hexdec()
* Update: Added support for tagging a channel via the feed message box using `{{#channel_id}}`
* Update: Added support for mentioning a user via the feed message box using `{{@user_id}}` (props yaboinish)
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