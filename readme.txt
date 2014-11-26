=== pollka polls ===
Contributors: Unicornis
Tags: polls, bbpress, buddypress
Requires at least: 3.8
Tested up to: 4.0.1
Stable tag: trunk
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/license-list.html#GPLCompatibleLicenses

This is a simple and flexible plugin that allows to use polls in worpress site and bbpress forums.

== Description ==
Just put a shortcode into your post or page and the plugin will display a poll.
You can also use Poll quicktag button to automate this process a bit.
Votes are assigned to the site user, you must be logged in to vote.
**poll question='Was it difficult?' answers='Yes,No'**
Votes are assigned to the site user, you must be logged in to vote.

poll question='Was it difficult?' answers='Yes,No'

All tags are easily customizable via Settings page, so you can adapt them to your page or language.
There are several different types of polls available.


**Default**
You can see who has voted, but not which answer was chosen. Default answers available.
poll question=\'Is it simple enough?'

**With custom answers**
As above, but with answers of your choice separated by commas
poll question='Is it simple enough?' answers='Oh yeah, Nope, Its too hard'

Public
Public poll - it is visible who has voted and how
poll question="How do you like a public poll?" answers="A bit, So-so, Not at all" options="public"

Secret
Secret poll - only the number of votes will be displayed
poll question="How do you like a secret poll?" answers="A bit, So-so, Not at all" options="secret"

Open
Open poll - everybody can vote more than once
poll question="How do you like a open poll?" answers="A bit, So-so, Not at all" options="open"

Time constrained
Poll is open only for a given period of time, specified like with strtotime function.
"10 September 2020"
"+1 day"
"+1 week"
"+1 week 2 days 4 hours 2 seconds"
"next Thursday"
poll question="How do you like a open, time constrained poll?" answers="A bit, So-so, Not at all" options="open" time="2014-12-31"



== Installation ==
Install as any other WordPress plugin - from plugin repository or upload plugin zip file to the server from wp admin area.

== Frequently Asked Questions ==
No Faq so far, but I will be more than happy to help. Post via <a href="http://wp.unicornis.pl">my webpage</a>.

== Screenshots ==
1. Pic1.png
2. Pic2.png
3. Pic3.png
4. Pic4.png

== Changelog ==

1.14

    First public version

1.0

    Initial code

 == Upgrade Notice ==
None

