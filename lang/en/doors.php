<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * English strings for mod_doors.
 *
 * @package    mod_doors
 * @copyright  2026 Jon Bolton, Simon Lewis
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['alreadyopened'] = 'You have already opened this door.';
$string['appearance'] = 'Appearance';
$string['aspect'] = 'Door shape ratio';
$string['aspect:landscape'] = 'Landscape';
$string['aspect:portrait'] = 'Portrait';
$string['aspect:square'] = 'Square';
$string['availablefrom'] = 'Available from';
$string['availablefrom_help'] = 'The door stays locked until this date and time. Leave disabled to make it available immediately.';
$string['background'] = 'Calendar background image';
$string['background_help'] = 'An image drawn behind the whole calendar. In the free layout this is the scene the doors sit on.';
$string['backgroundfit'] = 'Background image';
$string['backgroundfit:cover'] = 'Fill the calendar, cropping if needed';
$string['backgroundfit:fit'] = 'Fit the doors to the image';
$string['backgroundfit_help'] = 'Fill the calendar scales the image to cover whatever shape the doors make, cropping whatever does not fit. That suits a backdrop or a texture.

Fit the doors to the image does the opposite: the calendar takes the image\'s own proportions and the doors stretch to tile it exactly. Combined with no gap between doors and transparent doors, that gives the traditional advent calendar look, where each door is a piece of one picture. Choose a number of columns that divides your doors neatly, so 6 columns for 24 doors.';
$string['backtocalendar'] = 'Back to the calendar';
$string['bgcolour'] = 'Background colour';
$string['bgopacity'] = 'Background image strength';
$string['bgopacity:full'] = 'Full strength';
$string['bgopacity_help'] = 'Fades the background image so the doors stand out against it. The doors themselves are not faded.

The picture is drawn on its own layer, so custom CSS can go further: blur, desaturate or tint ".doors-bg" and only the picture is affected.';
$string['bulkapplied'] = 'Release dates applied';
$string['bulkapply'] = 'Apply dates';
$string['bulkclear'] = 'Remove all dates';
$string['bulkcleared'] = 'Release dates removed';
$string['bulkdates'] = 'Schedule release dates';
$string['bulkinterval'] = 'Interval between doors';
$string['bulkinterval:daily'] = 'One day';
$string['bulkinterval:none'] = 'None (all doors on the same date)';
$string['bulkinterval:weekly'] = 'One week';
$string['bulkstart'] = 'First door available from';
$string['bulkstart_help'] = 'Sets the release date of door 1, then spaces the remaining doors out by the interval below.';
$string['bulkweekdays'] = 'Skip weekends';
$string['buttonstyle'] = 'Buttons inside a door';
$string['buttonstyle:calendar'] = 'Match the calendar';
$string['buttonstyle:theme'] = 'Site colour';
$string['buttonstyle_help'] = 'The buttons behind a door, for a link or a linked activity, normally take the site\'s primary colour like every other button in Moodle.

Match the calendar instead and they take this calendar\'s door colour, so a campaign branded calendar is branded all the way through. The text and the activity icon switch between white and near black to stay readable against whatever colour you choose. Where the calendar has no door colour of its own the buttons fall back to the site colour.';
$string['centredoors'] = 'Centre the last row';
$string['centredoors_help'] = 'A grid packs the doors from the left, so a last row that does not fill the width sits to one side. Turn this on to centre it instead, which looks tidier for a five door week in a three column grid.

It has no effect when the doors are fitted to a background image, since they tile the picture exactly and there is nothing left over.';
$string['cleardoor'] = 'Empty this door';
$string['cleardoorconfirm'] = 'Delete all the content behind door {$a}?';
$string['colourfield'] = 'Colour';
$string['colourfield_help'] = 'A hex value such as #1f5c8b, or a CSS colour name such as navy. Leave empty to use the theme default.';
$string['colourmode'] = 'Door colours';
$string['colourmode:fixed'] = 'One colour for all doors';
$string['colourmode:varied'] = 'Varied, from a palette';
$string['colourmode_help'] = 'One colour uses the door colour below for every door. Varied gives each door a colour from a palette, in the way Moodle colours course cards. The colour a door gets is worked out from its number, so it does not change between page loads, and the number on the door is switched to black or white to stay readable. A colour set on an individual door always wins.';
$string['completiondetail:opened'] = 'Open {$a} doors';
$string['completionopened'] = 'Doors opened:';
$string['completionopenedgroup'] = 'Require doors to be opened';
$string['completionopenedgroup_help'] = 'Mark the activity complete once the participant has opened at least this many doors.';
$string['contentsummary'] = 'Contains';
$string['cssref:activityicon'] = 'The activity icon on that button, flattened to white by default';
$string['cssref:activitylink'] = 'The button linking to another activity';
$string['cssref:activitytext'] = 'The activity name and, where it is elsewhere, its course, on the button';
$string['cssref:back'] = 'The opened face';
$string['cssref:backdrop'] = 'The dark overlay behind the panel';
$string['cssref:bg'] = 'The background picture on its own layer, so fading or blurring it leaves the doors alone';
$string['cssref:bgimg'] = 'The image on a door face';
$string['cssref:bgopacity'] = 'How strongly the background picture is drawn, from 0 to 1';
$string['cssref:btn'] = 'Either button when it is set to match the calendar rather than the site';
$string['cssref:canvas'] = 'The area holding the doors';
$string['cssref:caption'] = 'The text block on a door';
$string['cssref:centre'] = 'On the wrapper when a part filled last row is centred';
$string['cssref:cols'] = 'Number of grid columns';
$string['cssref:content'] = 'Content inside a door';
$string['cssref:dimmed'] = 'Door artwork reused on the opened face';
$string['cssref:door'] = 'A door';
$string['cssref:doorbg'] = 'Closed door background';
$string['cssref:doorbutton'] = 'The door button';
$string['cssref:doorfg'] = 'Number and title colour';
$string['cssref:doorlocked'] = 'A door that is not available yet';
$string['cssref:dooropen'] = 'A door that has been opened';
$string['cssref:dooropenbg'] = 'Opened door background';
$string['cssref:doortitle'] = 'The title under the number';
$string['cssref:embed'] = 'Embedded media or a framed page';
$string['cssref:emptydoor'] = 'The placeholder when a door has no content';
$string['cssref:face'] = 'Either face of a door';
$string['cssref:facestacked'] = 'On the wrapper when door text sits under the image rather than over it';
$string['cssref:flash'] = 'The message shown when a locked door is selected';
$string['cssref:front'] = 'The closed face';
$string['cssref:frontbg'] = 'Closed door background, varied colours only';
$string['cssref:frontfg'] = 'Door text colour, varied colours only';
$string['cssref:gap'] = 'Space between doors in the grid';
$string['cssref:iframe'] = 'The frame itself';
$string['cssref:iframenote'] = 'The open in a new tab line under a frame';
$string['cssref:inner'] = 'The element that rotates when a door flips';
$string['cssref:label'] = 'A face label, as opposed to a number';
$string['cssref:layoutfree'] = 'On the wrapper in the free layout. Grid calendars carry .doors-layout-grid instead';
$string['cssref:link'] = 'The external link button';
$string['cssref:lock'] = 'The padlock on a locked door';
$string['cssref:media'] = 'The uploaded image, video or audio';
$string['cssref:mediaimage'] = 'An uploaded image specifically';
$string['cssref:modal'] = 'The panel that opens';
$string['cssref:modalbody'] = 'The panel body, where the content goes';
$string['cssref:modalclose'] = 'The close button';
$string['cssref:modalheader'] = 'The panel header';
$string['cssref:modalpanel'] = 'The panel';
$string['cssref:modaltitle'] = 'The door title in the header';
$string['cssref:number'] = 'The number, or the face label where one is set';
$string['cssref:openeddim'] = 'On the wrapper when opened doors dim in place rather than flipping';
$string['cssref:plate'] = 'The shaded backing behind the text when a door has an image';
$string['cssref:progress'] = 'The progress indicator';
$string['cssref:progressbar'] = 'The bar itself';
$string['cssref:progressfill'] = 'The filled part of the bar';
$string['cssref:properties'] = 'Colours and spacing';
$string['cssref:richtext'] = 'The rich text';
$string['cssref:structure'] = 'The calendar';
$string['cssref:tick'] = 'The tick on an opened door in dim mode';
$string['cssref:transparent'] = 'On the wrapper when the doors are drawn as transparent outlines';
$string['cssref:wrapper'] = 'The whole activity. Also carries the layout, shape, aspect and opened style as classes';
$string['cssrefinsert'] = 'Add this to the custom CSS box';
$string['cssrefintro'] = 'Select any selector below to add it to the box above, already prefixed with this activity\'s wrapper id so it affects nothing else on the page.';
$string['cssrefintronew'] = 'Select any selector below to add it to the box above. Once the activity has been saved, the list prefixes each rule with the activity\'s wrapper id so it affects nothing else on the page.';
$string['cssrefopen'] = 'Selectors for this activity';
$string['cssrefunscoped'] = 'These are drawn outside the calendar, so they cannot be tied to one activity. Rules here reach every door calendar on the page.';
$string['customcss'] = 'Custom CSS';
$string['customcss_help'] = 'CSS applied to this activity\'s page, on top of the plugin\'s own stylesheet. Use it for anything the settings above do not reach.

It is written into the page as a plain style block and is not scoped for you, so a selector like ".doors-door" would affect every door calendar on the page. Prefix your rules with the activity wrapper id to be certain, which is what the selector list under the box does for you.

Open Selectors for this activity under the box for the full list, and select any one of them to drop it into the box.';
$string['deletedoor'] = 'Delete this door permanently';
$string['deletedoorconfirm'] = 'Permanently delete stored door {$a} and everything behind it? This cannot be undone.';
$string['door'] = 'Door';
$string['dooractivity'] = 'Activity';
$string['dooractivity_help'] = 'An activity or resource elsewhere on this site to send participants to. The door shows it as a button carrying the activity\'s own icon and name, so a rename follows through automatically.

The link is hidden from anyone who cannot see the target activity, whether because it is hidden, restricted or in a course they are not in, so it is safe to point at material not everyone can reach.';
$string['dooractivitycourse'] = 'Course';
$string['dooractivitycourse_help'] = 'Which course to choose the activity from. Choose a course, select Show activities from this course, then pick from the list.';
$string['dooractivityheader'] = 'Link to an activity';
$string['dooractivityincourse'] = 'in {$a}';
$string['dooractivitynone'] = 'None';
$string['dooractivityreload'] = 'Show activities from this course';
$string['doorbigger'] = 'Bigger';
$string['doorcleared'] = 'Door {$a} emptied';
$string['doorclosed'] = 'Default closed door image';
$string['doorclosed_help'] = 'Used as the face of every door that does not have its own image. Leave empty to use a plain coloured door.';
$string['doorcolour'] = 'Door colour';
$string['doorcolour_help'] = 'The colour of the closed doors in this activity, which overrides the site default. Handy for matching the branding of a particular campaign or awareness week. Leave empty to use the site default, and set a colour on an individual door to override this.';
$string['doorcontent'] = 'Content';
$string['doorcontent_help'] = 'Text, images and embedded media shown inside the door. Anything you can put in an editor can go here.

A door is laid out in this order: the uploaded file and this text, in whichever order you choose below, then the activity link, then the web link or embed.';
$string['doordeleted'] = 'Door {$a} deleted';
$string['doorgap'] = 'Space between doors';
$string['doorgap_help'] = 'The gap between the doors in the grid. Set it to 0 and the doors tile edge to edge, which is what you want when the doors are cut out of a single picture.';
$string['doorimage'] = 'Door face image';
$string['doorimage_help'] = 'An image just for this door, overriding the default closed door image.';
$string['doorlabel'] = 'Face label';
$string['doorlabel_help'] = 'Replaces the door number on the front of the door. Use it for days of the week, dates, or short words.';
$string['doorlabelaria'] = 'Door {$a->number} {$a->title}';
$string['doorlabelariaopened'] = 'Door {$a->number} {$a->title}, opened';
$string['doorlinkheader'] = 'Link or embed';
$string['doorlinkheaderinfo'] = 'Links and embedded media appear at the foot of the door, below the text.';
$string['doorlinkmode'] = 'Show the link as';
$string['doorlinkmode_help'] = 'A button opens the resource in a new tab, and works for anything.

Embedded media plays the URL in the site media player. It only handles URLs a player recognises, such as a YouTube video or an MP4 file, and is refused for an ordinary web page.

In a frame displays the page inside the door. Most large sites send headers that forbid being framed, and the result is a blank white area, so test it before relying on it. A link to open the page in a new tab is always shown underneath the frame.';
$string['doorlinknewwindow'] = 'Open in a new tab';
$string['doorlinktext'] = 'Link text';
$string['doorlinkurl'] = 'Link URL';
$string['doorlinkurl_help'] = 'A link to an external resource, or a media URL such as a YouTube video to embed.';
$string['doormedia'] = 'Image, video or audio file';
$string['doormedia_help'] = 'A single uploaded file. Images are displayed inline; video and audio use the site media player. Where it sits in relation to the text is set below.

A video added as a link rather than an uploaded file is not this field, and always appears at the foot of the door with the other links.';
$string['dooropened'] = 'Default opened door image';
$string['dooropened_help'] = 'Shown on the back of the door once it has been opened. Leave this empty and each door keeps its own face image on the back instead, dimmed, so artwork is not lost when the door opens.';
$string['doors:addinstance'] = 'Add a new door calendar';
$string['doors:ignoreavailability'] = 'Open doors before their release date';
$string['doors:manage'] = 'Manage the contents of a door calendar';
$string['doors:view'] = 'View a door calendar';
$string['doors:viewreport'] = 'View the door opening report';
$string['doorsaved'] = 'Door {$a} saved';
$string['doorshape'] = 'Door corners';
$string['doorsmaller'] = 'Smaller';
$string['doorsname'] = 'Name';
$string['doorssettings'] = 'Doors';
$string['doortextcolour'] = 'Door text colour';
$string['doortitle'] = 'Title';
$string['doortitle_help'] = 'Shown as the heading when the door is opened.';
$string['doortitledefault'] = 'Door {$a}';
$string['editingdoor'] = 'Editing door {$a}';
$string['empty'] = 'Empty';
$string['errorcolour'] = 'Enter a hex colour such as #1f5c8b, or a CSS colour name.';
$string['errorcompletionopened'] = 'The number of doors required must be between 1 and the number of doors in the calendar.';
$string['errordeleteactive'] = 'That door is in use and cannot be deleted here. Empty it instead.';
$string['errorlinkurlrequired'] = 'A URL is needed when the link is embedded or framed.';
$string['errorloading'] = 'The contents of this door could not be loaded.';
$string['errormaxwidth'] = 'Enter 0 for the full width of the page, or a width between 320 and 4000 pixels.';
$string['errornotembeddable'] = 'None of the site\'s media players can play that URL, so embedding it would show nothing. Use a button for an ordinary web page, or a frame if the site allows framing.';
$string['errornumdoors'] = 'The number of doors must be between 1 and {$a}.';
$string['errorpalette'] = 'No usable colours were found. Use hex values such as #81ecec, separated by commas.';
$string['errorsaving'] = 'Could not save. Please try again.';
$string['eventdooropened'] = 'Door opened';
$string['facelayout'] = 'Door face layout';
$string['facelayout:overlay'] = 'Text over the image';
$string['facelayout:stacked'] = 'Text under the image';
$string['facelayout_help'] = 'Where a door has its own face image, this decides how the image and the number or title share the door.

Text over the image fills the door with the picture and lays the text on top, on a shaded backing so it stays readable. It suits photographs and full bleed artwork.

Text under the image shows the whole image, scaled to fit, with the number and title underneath. It suits icons, logos and line art, where covering the middle with text spoils the picture.';
$string['gridcols'] = 'Columns';
$string['hasactivity'] = 'activity link';
$string['hasdoorimage'] = 'door image';
$string['haslink'] = 'link';
$string['hasmedia'] = 'media file';
$string['hastext'] = 'text';
$string['hiddendoors'] = 'Stored doors';
$string['hiddendoorsinfo'] = 'These {$a} doors are above the current number of doors, so they are not shown to participants. Their content is kept: raise the number of doors in the activity settings and they come back. Deleting one here removes it and its content for good.';
$string['iframefallback'] = 'Nothing showing? Open this page in a new tab';
$string['layout'] = 'Layout';
$string['layout:free'] = 'Free (drag to position)';
$string['layout:grid'] = 'Grid';
$string['layout_help'] = 'Grid arranges the doors evenly in rows and columns. Free lets you drag each door to wherever you want it on the background image, which is useful for scene based calendars.';
$string['linkmode:embed'] = 'Embedded media (video and audio URLs only)';
$string['linkmode:iframe'] = 'In a frame';
$string['linkmode:link'] = 'A button';
$string['loading'] = 'Loading';
$string['locked'] = 'This door is not available yet.';
$string['lockedsequential'] = 'Open door {$a} first.';
$string['lockeduntil'] = 'This door opens on {$a}.';
$string['managedoors'] = 'Manage doors';
$string['markopened'] = 'Show doors as opened';
$string['maxwidth'] = 'Maximum width';
$string['maxwidth_help'] = 'The widest the calendar will be drawn, in pixels, centred in the page. Leave it at 0 and the calendar uses the full width of the page, which suits a grid because the doors simply get roomier.

It is worth setting for the free layout, where the canvas grows with the page and a background image made for a laptop is stretched well past its natural size on a large monitor. Set it to the width the image was made for. 830 matches the width Moodle uses for reading pages.

The description above the calendar is brought in to the same width, so the two line up.';
$string['mediaposition'] = 'Where to put the file';
$string['mediaposition:above'] = 'Above the text';
$string['mediaposition:below'] = 'Below the text';
$string['mediaposition_help'] = 'Whether the uploaded file appears above the text or between the text and the links. Links and embedded links always come last.';
$string['modulename'] = 'Door calendar';
$string['modulename_help'] = 'A door calendar presents a set of numbered doors (from 1 to 31). Behind each door you can put text, an image, a video, an audio clip or a link to another resource.

Use it for campaign weeks, induction countdowns, awareness months, revision calendars or a traditional advent calendar. Doors can be released on set dates, opened in any order or in sequence, and the whole thing can be skinned with your own background and door graphics.';
$string['modulename_link'] = 'mod/doors/view';
$string['modulenameplural'] = 'Door calendars';
$string['nocontentyet'] = 'Nothing has been put behind this door yet.';
$string['nodoors'] = 'This calendar has no doors yet.';
$string['noinstances'] = 'There are no door calendars in this course.';
$string['nousers'] = 'Nobody is enrolled with permission to view this activity.';
$string['numdoors'] = 'Number of doors';
$string['numdoors_help'] = 'How many doors the calendar has, from 1 to 31.

Increasing the number adds new empty doors at the end. Reducing it hides the doors above the new count rather than deleting them, so raising the number again brings their content back. Hidden doors are listed as stored doors on the manage doors page, where they can be deleted for good if you want rid of them.';
$string['opened'] = 'Doors opened';
$string['openedcolour'] = 'Opened door colour';
$string['openedstyle'] = 'How an opened door looks';
$string['openedstyle:dim'] = 'Dim it in place';
$string['openedstyle:flip'] = 'Flip the door over';
$string['openedstyle_help'] = 'Flip turns the door over to show its opened side. If you have not set an opened door image, the door keeps its own artwork on the back, dimmed, so a picture door still looks like itself once opened.

Dim in place leaves the door exactly where it is, dims it and adds a tick. This suits calendars where each door carries its own artwork and you want it to stay recognisable, and it is the quieter option on a busy background.';
$string['openlink'] = 'Open the resource';
$string['openmode'] = 'Opening order';
$string['openmode:any'] = 'Any order';
$string['openmode:sequential'] = 'In sequence';
$string['openmode_help'] = 'Choose whether participants can open doors in any order, or must work through them in sequence. In sequence, door 5 only unlocks once door 4 has been opened.';
$string['palette'] = 'Palette';
$string['palette_help'] = 'The colours to draw from, separated by commas, for example #81ecec, #74b9ff, #a29bfe. Leave empty to use the site wide palette, which in turn follows the colours this site uses for course cards.';
$string['pluginadministration'] = 'Door calendar administration';
$string['pluginname'] = 'Door calendar';
$string['positiondoors'] = 'Door positions';
$string['positiondoorsinfo'] = 'Drag each door to where you want it. Select a door and it stays selected: the arrow keys then nudge it, shift and an arrow key move it a little at a time, and the buttons below or the + and - keys change its size. Remember to save.';
$string['positionssaved'] = 'Positions saved';
$string['poswidth'] = 'Width (%)';
$string['poswidth_help'] = 'The door width as a percentage of the calendar width. Used in the free layout only.';
$string['posx'] = 'Position from left (%)';
$string['posy'] = 'Position from top (%)';
$string['printintro'] = 'Display description on the activity page';
$string['printintro_help'] = 'Whether the description appears above the calendar. Turn it off to bring the doors up the page, or where the description is only there to explain the activity on the course page.

This is separate from Display description on course page in the settings above, which controls whether it appears alongside the activity in the course.';
$string['privacy:metadata:doors_opened'] = 'A record of the doors a user has opened.';
$string['privacy:metadata:doors_opened:doorid'] = 'The ID of the door that was opened.';
$string['privacy:metadata:doors_opened:doorsid'] = 'The ID of the door calendar.';
$string['privacy:metadata:doors_opened:timeopened'] = 'The time the door was opened.';
$string['privacy:metadata:doors_opened:userid'] = 'The ID of the user who opened the door.';
$string['privacy:openeddoors'] = 'Doors opened';
$string['progresscount'] = '{$a->done} of {$a->total} doors opened';
$string['randomise'] = 'Shuffle door positions';
$string['randomise_help'] = 'Display the doors in a scrambled order rather than 1, 2, 3. The order is fixed for the calendar, so everyone sees the same arrangement.';
$string['reopen'] = 'Allow doors to be reopened';
$string['reopen_help'] = 'If disabled, each door can be opened once only and the content cannot be viewed again.';
$string['report'] = 'Door opening report';
$string['reportcomplete'] = 'Complete';
$string['reportcompletefail'] = 'Complete (not passed)';
$string['reportincomplete'] = 'Incomplete';
$string['reportsearch'] = 'Find a participant';
$string['reportsearchclear'] = 'Clear';
$string['reportsearchgo'] = 'Search';
$string['reportsearchplaceholder'] = 'Name';
$string['resetopened'] = 'Delete all records of doors being opened';
$string['savepositions'] = 'Save positions';
$string['selectdoorfirst'] = 'Select a door first';
$string['setting:bgcolour'] = 'Calendar background colour';
$string['setting:bgcolour_desc'] = 'Drawn behind the doors. Leave empty for no background colour.';
$string['setting:buttonstyle'] = 'Buttons inside a door';
$string['setting:buttonstyle_desc'] = 'Whether the buttons behind a door take the site\'s primary colour or the calendar\'s own door colour.';
$string['setting:colourmode'] = 'Door colours';
$string['setting:colourmode_desc'] = 'Whether new calendars use one colour for every door or take varied colours from the palette below.';
$string['setting:doorcolour'] = 'Door colour';
$string['setting:doorcolour_desc'] = 'The colour of closed doors. Used wherever an activity leaves its own door colour empty, so this is the place to set your organisation\'s colour once.';
$string['setting:doorshape'] = 'Door corners';
$string['setting:doorshape_desc'] = 'The corner style new calendars start with.';
$string['setting:doortextcolour'] = 'Door text colour';
$string['setting:doortextcolour_desc'] = 'The colour of the number and title on a door.';
$string['setting:heading'] = 'Default settings for new door calendars';
$string['setting:heading_desc'] = 'These are the starting values for a new door calendar. Every one of them can be changed in an individual activity, and changing them here does not alter calendars that already exist.';
$string['setting:layout'] = 'Layout';
$string['setting:layout_desc'] = 'Whether new calendars start as an even grid or as a free layout you drag doors around on.';
$string['setting:maxwidth'] = 'Maximum width';
$string['setting:maxwidth_desc'] = 'The widest a new calendar will be drawn, in pixels. Leave it at 0 for the full width of the page.';
$string['setting:numdoors'] = 'Number of doors';
$string['setting:numdoors_desc'] = 'The door count a new calendar starts with.';
$string['setting:openedcolour'] = 'Opened door colour';
$string['setting:openedcolour_desc'] = 'The colour shown on the back of a door once it has been opened.';
$string['setting:openedstyle'] = 'How an opened door looks';
$string['setting:openedstyle_desc'] = 'Whether a door flips over when opened or is dimmed where it stands.';
$string['setting:palette'] = 'Palette';
$string['setting:palette_desc'] = 'The colours used when an activity is set to varied door colours, separated by commas. Activities can supply their own list instead. Leave this empty to follow the colours this site uses for course cards.';
$string['setting:palettedetected'] = 'The course card colours found on this site are: {$a}.';
$string['setting:palettenotdetected'] = 'No course card colours could be read from this site, so the built in list would be used: {$a}.';
$string['setting:showactivityicon'] = 'Show the icon on activity links';
$string['setting:showactivityicon_desc'] = 'Whether new calendars show the activity icon on a door that links to another activity. The icon is rendered in white to suit the button.';
$string['shape:circle'] = 'Circle';
$string['shape:rounded'] = 'Rounded';
$string['shape:square'] = 'Square';
$string['showactivityicon'] = 'Show the icon on activity links';
$string['showactivityicon_help'] = 'A door that links to another activity shows a button carrying that activity\'s icon and name. Moodle\'s activity icons are drawn in a single flat colour, so the button renders them in white. A plugin that ships a full colour icon instead will come out as a white shape, in which case turn this off and the button shows the name alone.';
$string['shownumbers'] = 'Show door numbers';
$string['showprogress'] = 'Show progress bar';
$string['showtitles'] = 'Show door titles on the front';
$string['showtitles_help'] = 'Display each door\'s title on the closed door as well as inside it. Useful when the doors are topics rather than a surprise.';
$string['transparentdoors'] = 'Transparent doors';
$string['transparentdoors_help'] = 'Draws the doors as dashed outlines with the background image showing through, the way a paper advent calendar looks before it is opened. An opened door darkens instead of turning a colour.

The door and palette colours are ignored while this is on, though a door with its own face image still shows it.';
