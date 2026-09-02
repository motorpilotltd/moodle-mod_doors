# Door calendar (mod_doors)

A Moodle activity module that presents a set of doors. Behind each door you can
put text, an image, a video, an audio file, a link to an activity elsewhere on
the site, or a link out to the web. It does a similar job to the H5P Advent
Calendar content type, but there are quite a few differences:
* The number of doors can be anything from 1 to 31, so it
works for campaign weeks, awareness months, induction countdowns and revision
calendars... as well as advent.
* Doors can be opened in any order, or sequentially
* Doors can be 'locked' until a certain date
* Very customisable in terms of layout and appearance

Current release: 1.0.0-alpha.1. See CHANGELOG.md for history.

## Requirements

* Moodle 4.5 or later (tested up to 5.2)
* PHP 8.1 or later

No Node or Grunt toolchain is needed. The JavaScript is plain script loaded with
`$PAGE->requires->js()`, not a compiled AMD module, so the files in `js/` are the
files that run.

## Installing and upgrading

Copy the `doors` folder to `mod/doors` in your Moodle root, then visit
Site administration > Notifications. Upgrades from an earlier release run their
own database steps from the same place. Purge caches afterwards, or an old
`styles.css` will still be served.

## Site defaults

Site administration > Plugins > Activity modules > Door calendar sets the
starting values for new calendars: door count, layout, maximum width, corner
style, how an opened door looks, whether activity links carry an icon, button
colour, colour mode, the four colours and the palette.

Colours behave differently from the rest. An activity that leaves a colour empty
falls back to the site default every time it is displayed, so setting your
organisation's colour once is enough and changing it later reaches existing
calendars. The non-colour defaults apply to new activities only.

## What an editor can set

Per activity:

* Number of doors, 1 to 31. Lowering the number after content has been added to higher numbered doors hides the doors above the new count
  rather than deleting them, so raising the number again brings the content back.
  Stored doors are listed on the manage page and can be deleted from there
* Opening order: any order, or in sequence (door 5 unlocks when door 4 is opened)
* Shuffle door positions, allow reopening, show opened state, show progress bar
* How an opened door looks: flip it over, or dim it in place with a tick. With
  no opened door image set, a flipped door keeps its own artwork on the back,
  dimmed, so picture doors stay recognisable
* Layout: grid (with a chosen number of columns and a door aspect ratio) or free,
  where each door is dragged to a position on the background image
* Whether the description appears above the calendar, separately from Moodle's
  own display on the course page
* Maximum width. The calendar pages are full width, like the forum, since a grid
  benefits from the room. Set a width here to rein it in, which matters most for
  a free layout whose background image was made for a particular size
* Door corners: square, rounded or circle
* Background image, default closed door image, default opened door image
* Background, door, opened door and door text colours
* Door colours: one colour for every door, or varied from a palette. The colour
  a door gets is derived from its number so it is stable between page loads, and
  the number is switched to black or white for contrast. The palette is resolved
  activity list, then site list, then the colours this Moodle gives its course
  cards (read at runtime from `$CFG->coursecolorN` or the renderer's
  `get_generated_color_for_id()`), then a built in list
* Buttons inside a door: the site's primary colour, or the calendar's own door
  colour so a campaign branded calendar is branded throughout
* Custom CSS applied to that activity's page
* Completion: mark complete when N doors have been opened

Per door:

* Title, and a face label that replaces the number on the door (e.g. "Monday")
* Rich text content, with anything the editor supports
* One image, video or audio file, placed above or below the text
* A link to an activity or resource anywhere on the site, shown as a button with
  the activity's name and, unless switched off per activity, its icon in white,
  and hidden from anyone who cannot see the target
* A link out to the web, shown as a button, embedded in the site media player,
  or in a frame
* Its own door face image and door colour
* An "available from" date and time
* Position and size in the free layout, set by dragging, the Bigger and Smaller
  buttons, or the arrow and + and - keys

There is also a bulk scheduler on the manage page: pick a start date and an
interval (daily or weekly, optionally skipping weekends) and the doors are
dated in order.

## Styling

Every colour, image and shape setting is exposed in the activity form, and the
Custom CSS box handles anything beyond that. Under the box, Selectors for this
activity opens a list of every class and custom property the plugin emits, each
with a one line description. Selecting one drops a rule into the box, prefixed
with the activity's own wrapper id, with the caret between the braces.

Custom CSS is written into the activity page as a style block and is not scoped
for you, which is why the reference prefixes rules with `#doors-cal-N`. Two
groups cannot be scoped that way: the content panel and its backdrop are drawn
on the page body rather than inside the calendar, so rules for those reach every
door calendar on the page. The reference says so where it applies. Custom CSS
runs on the calendar page only, not the manage page.

## Behaviour worth knowing

* **Embedded media** only handles URLs one of the site's media players
  recognises, such as a YouTube link or an MP4. The door form refuses to save
  embed mode with anything else and tells you to use a button or a frame.
* **In a frame** depends on the target site allowing itself to be framed. Most
  large sites forbid it and the result is a blank area, which the browser gives
  no cross-origin way to detect, so a link to open the page in a new tab is
  always shown underneath.
* **Activity links** are checked at display time, not just when saved, so a door
  never shows a link to something the person looking cannot see.
* **Backup and restore** carries doors, their files and, with user data, the
  record of who opened what. An activity link is remapped after every module has
  been restored. A link to an activity that was not part of the backup survives
  a restore on the same site; anywhere else it is cleared, with a warning in
  the restore log, rather than left pointing at whatever activity happens to
  hold that id on the destination site.

## The report

Door opening report, reachable from the activity's More menu, alongside Manage
doors. One row per enrolled participant, showing how many doors they have
opened, their completion state where completion is switched on, and a tick per
door with the time it was opened on hover.

The table pages at 25 rows, sorts by name or by the number of doors opened,
filters by name, and downloads as CSV or any other format the site offers.

## Files of interest

| Path | Purpose |
| --- | --- |
| `view.php` | Renders the calendar |
| `open.php` | Ajax endpoint that opens a door and returns its content |
| `edit.php` | Manage doors, drag positions, bulk schedule, stored doors |
| `editdoor.php` / `door_form.php` | Edit one door |
| `savepos.php` | Ajax endpoint for the free layout positions |
| `report.php` | Who has opened which doors |
| `classes/output/report_table.php` | The report table itself |
| `lib.php` | Moodle callbacks: add, update, delete, files, completion, reset |
| `locallib.php` | Door queries, availability, content rendering, palettes |
| `mod_form.php` / `settings.php` | Activity settings and site defaults |
| `classes/output/renderer.php` | All the calendar markup |
| `js/doors.js` | Front end behaviour and the content modal |
| `js/doors_edit.js` | Drag, nudge and resize doors in the free layout |
| `js/doors_cssref.js` | Selector reference on the settings form |
| `styles.css` | All the look and feel |

## Privacy

The only user data stored is which doors a user opened and when, in
`doors_opened`. Full privacy API support is implemented, including export and
deletion, and course reset can clear the lot.

## Changelog

See CHANGELOG.md. The plugin was re-baselined at 1.0.0-alpha.1 when it moved
into this repository; Jon Bolton's pre-release iterations (1.0.0 to 1.3.0,
August 2026) are folded into that baseline.

## Licence

GNU GPL v3 or later.
