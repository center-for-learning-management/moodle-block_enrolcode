# moodle-block_enrolcode
This moodle plugin allows user enrolment by showing temporary access codes. These access codes are only valid as long as they are shown in a modal dialog, but a longer expiration period can be set optionally.

That way an invitation link to a course can be sent using other media (e-Mail, Messengers, ...) and users can self-enrol directly using that link.

## General usage

The plugin adds a button to the participants page of all courses via javascript, that allows teachers to create an access code. The access code can also be shared as a link and a QR code is created dynamically.

Furthermore it injects a button to the main menu using javascript, that allows all users to enter access codes from wherever they are.

When a user enters a valid access code this plugin will check for the first occurrance of a "manual enrol method" of the referenced course. If the first manual enrol method found is inactive, it will activate it. If it finds none, it creates a default one in that course.

## The block

The block can be added to any course page, on the frontpage or dashboard. Within a course page it allows the creation of codes, and offers a field to enter access codes to users.
