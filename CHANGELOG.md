# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0-alpha.2] - 2026-09-03

### Added

- The pieces needed for a traditional advent calendar: transparent doors
  drawn as dashed outlines over the picture, a configurable gap between
  doors including none at all, and a background option that takes the
  image's own proportions and stretches the doors to tile it exactly.
- A background image strength setting. The picture is now drawn on its own
  layer (`.doors-bg`) rather than as the canvas background, so custom CSS
  can fade, blur or tint it without taking the doors along.
- A door face layout choice: text over the image, or the whole image with
  the text underneath, which suits icons and line art.
- An option to centre a last row that does not fill the grid width.

### Changed

- A maximum width now also narrows the activity description to line up
  with the calendar, and the course name on an activity link button sits
  on its own line under the activity name.
- With transparent doors on, varied per-door colours are skipped: they
  were computed for a background that is no longer painted.

## [1.0.0-alpha.1] - 2026-09-02

Initial version of the plugin, folding in Jon Bolton's pre-release
iterations (1.0.0 to 1.3.0, August 2026) and a standards review.

### Added

- A door calendar activity: 1 to 31 doors in a grid or free (drag to
  position) layout, opened in any order or sequentially, each holding rich
  text, one media file, a link to another activity on the site and/or a web
  link (button, embed or frame).
- Release dates per door with a bulk scheduler (daily or weekly, optionally
  skipping weekends), door face images, colour and palette settings with a
  per-activity custom CSS box and selector reference.
- Activity completion after opening N doors, a who-opened-what report with
  name filter and download, backup and restore including openings, course
  reset, a `door_opened` event and full privacy API support.
- Site-level defaults for the appearance settings; colours fall back to the
  site defaults at display time.

### Changed (during the review, relative to the code as received)

- Completion rule elements use the Moodle 4.3+ suffix API so the default
  activity completion form works.
- Restoring into another site clears activity links that cannot be remapped,
  with a restore-log warning, instead of keeping an id that may collide.
- Utility classes dual-classed for Bootstrap 4 and 5; the bulk scheduler's
  weekend skipping follows the user's timezone; the whole codebase passes
  `phpcs --standard=moodle`.
- Compatibility floors set to Moodle 4.5 / PHP 8.1; the development-time
  upgrade steps are folded into the installation schema for this baseline.

[1.0.0-alpha.2]: https://github.com/motorpilotltd/moodle-mod_doors/releases/tag/v1.0.0-alpha.2
[1.0.0-alpha.1]: https://github.com/motorpilotltd/moodle-mod_doors/releases/tag/v1.0.0-alpha.1
