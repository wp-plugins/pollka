=== pollka polls ===
Contributors: Unicornis
Donate link: http://wp.unicornis.pl/pollka-poll-donation
Tags: polls, bbpress
Requires at least: 3.8
Tested up to: 4.2.3
Version: 1.2
Stable tag: trunk
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/license-list.html#GPLCompatibleLicenses

This is a simple and flexible plugin that allows to use polls in worpress site and bbpress forums. The poll can be created by any registered user. 

== Description ==
Just put a shortcode into your post or page and the plugin will display a poll.
You can also use Poll quicktag button to automate this process a bit.

Votes are assigned to the site user, **you must be logged in to vote.**

Poll can be created by any user, not just editor, **even without access to the wp-admin part.** Polls are compatible with **bbpress** posts.

**[poll question='Was it difficult?' answers='Yes,No']**

All tags are easily customizable via Settings page, so you can adapt them to your page or language.
There are several different types of polls available.

Examples available on a sample page:

<a href="http://pollka.unicornis.pl/pollka-poll-plugin/">Pollka polls in action on a wordress page</a>

<a href="http://pollka.unicornis.pl/forums/topic/pollka-poll-plugin/">Pollka polls in action on a bbress forum topic</a>

There are two sample accounts available, feel free to play with it.


**Default**
You can see who has voted, but not which answer was chosen. Default answers available.
[poll question='Is it simple enough?']


**With custom answers**
As above, but with answers of your choice separated by commas

[poll question='Is it simple enough?' answers='Oh yeah, Nope, Its too hard']


**Public**
Public poll - it is visible who has voted and how

[poll question="How do you like a public poll?" answers="A bit, So-so, Not at all" options="public"]

**Secret**
Secret poll - only the number of votes will be displayed

[poll question="How do you like a secret poll?" answers="A bit, So-so, Not at all" options="secret"]

**Open**
Open poll - everybody can vote more than once

[poll question="How do you like a open poll?" answers="A bit, So-so, Not at all" options="open"]

**Time constrained**
Poll is open only for a given period of time, specified like with strtotime function.
* "10 September 2020"
* "+1 day"
* "+1 week"
* "+1 week 2 days 4 hours 2 seconds"
* "next Thursday"

[poll question="How do you like a open, time constrained poll?" answers="A bit, So-so, Not at all" options="open" time="2014-12-31"]



== Installation ==
Install as any other WordPress plugin - from plugin repository or upload plugin zip file to the server from wp admin area.

== Frequently Asked Questions ==
No Faq so far, but I will be more than happy to help. Post via <a href="http://pollka.unicornis.pl/forums/forum/pollka-polls/pollka-polls-support/">support forum.</a> 

== Screenshots ==
1. Sample poll
2. After voting
3. Options page


== Changelog ==
2.0
   Redone ajax response, now handling properly open polls when user can vote more than ones.
   New summary for public polls - it's easily visible who has chosen each option
  
1.14.6
   Tested with WP 4.2.2

1.14.5
   Tags corrected.

1.14.4
   Added ability to customize colors with options settings screen.

1.14.3
    Translation fixes.

1.14.2
    Fixed broken graphic link

1.14.1
    Translation fixes.


1.14
    First public version

1.0
    Initial code

 == Upgrade Notice ==
None

== Translations ==
 
* English - default, always included
* Polish: Nie na darmo to Pollka! included in the distribution 

The pot file included.

*Note:* All my plugins are localized/ translateable by default. This is very important for all users worldwide. 
So please contribute your language to the plugin to make it even more useful. 
For translating I recommend the awesome ["Codestyling Localization" plugin](http://wordpress.org/extend/plugins/codestyling-localization/) 
and for validating the ["Poedit Editor"](http://www.poedit.net/).
 
