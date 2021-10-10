Key Changes
Version 4.4.3 is a small maintenance update to fix issues reported since 4.4.2.

Additional Information
Security

Fixed an XSS concern deleting members in the AdminCP.
Fixed an XSS concern managing Downloads versions from the changelog view.
Fixed a minor XXE possibility in blog RSS imports.
Core

Upgraded CKEditor to 4.11.4.
Updated LinkedIn login handler to use the LinkedIn v2 API.
Improved performance when merging two comments with duplicated member reactions for large sites.
Improved performance when working with edit history logs.
Improved security of how passwords are handled in the code to decrease the likelihood of a password being included in an error log.
Improved the display of the upgrader confirmation page.
Improved performance of the latest activity stream shown on user profiles.
Improved anonymous log in tracking to resolve an issue with "Since my last visit" activity streams
Improved the UX configuring moderator permissions for clubs, including the ability to disable club-level moderators.
Improved database error reporting in certain error situations.
Improved performance of a 4.1.8 upgrader step.
Changed AdminCP notifications for "A new member has completed registration" and "A member is flagged as a spammer" to show all applicable members grouped into a single notification, rather than a separate notification for each member. This change gives a significant performance improvement for sites which have lots of new registrations.
Fixed multiple formatting concerns with custom profile fields.
Fixed an issue where restoring soft-deleted content would throw an exception under specific circumstances ( e.g. when there was no record in the soft deletion log ).
Fixed an issue where the member group restriction to require one piece of approved content before users can bypass content moderation was not correctly applied to posts made before registering.
Fixed an issue where deleting a member's content and then deleting the member may result in the content not being removed.
Fixed SVG images breaking when served through the built in image proxy.
Fixed an issue with profile completion if you choose not to upload a profile photo.
Fixed third party processor information not showing when users are forced to reaccept an updated privacy policy.
Fixed the "Remove followers from uncommented content" setting not working.
Fixed an issue where clicking to delete a member twice might result in all status updates being removed from the search index.
Fixed an issue where copying content from one area with an attached image and pasting into another area may result in a broken image.
Fixed an issue where allowing a user to moderate comments, but not items, would result in an error when using the multi-moderation menu.
Fixed inability to edit profile fields by members if the field was not displayed on the profile.
Fixed invalid HTML in the quick search form.
Fixed an issue where a comment or post made before registering which requires moderator approval after the registration is completed may not update the container flag to indicate that comments within the container require approval.
Fixed an error where the member view in the AdminCP may become broken if the member history for the user includes an old subscription group change and Commerce is not installed.
Fixed an issue where broken letter photos may be displayed in emails.
Fixed an issue with clean up tasks where they may try to delete a member that doesn't exist.
Fixed an error that can occur if you double click the "unfollow" button quickly.
Fixed autosaved content in the editor not clearing out when it was deleted within the editor.
Fixed an issue where MFA while the login would send 3 'new device' emails to the member instead of only one.
Fixed an issue where attachment links inserted into content may have a hard coded URL.
Fixed an upgrade issue where custom file storage configurations in 3.x may not be preserved correctly when upgrading to 4.x.
Fixed an uncaught exception when visiting a specifically malformed follow link.
Fixed attachment bbcode tags not converting correctly when upgrading from version 2.0 or older.
Fixed the About Me default custom field not showing on new installs.
Fixed email statistic charts so they report more accurately.
Fixed issues with performing advanced member searches in the AdminCP when multi-select custom profile fields are present.
Fixed a minor inconsistency with group name formatting.
Fixed an issue rebuilding certain meta data in Elasticsearch.
Fixed an issue where items and comments queued for deletion or submitted by a guest prior to registration are returned via the REST API.
Fixed an error when searching a specific search string.
Fixed a possible error that can occur during login when using the post before register feature.
Fixed the Notification Settings form in the Admin CP so that it can save properly.
Fixed an issue where Login Handlers were shown out of order.
Fixed an issue where the canonical link HTML tag may include unnecessary query string parameters (i.e. filters).
Fixed an issue where AdminCP settings search results were not always highlighted when clicked on.
Fixed an issue where the pagination for comment and review areas wouldn't link directly to the comments area when Javascript is disabled.
Fixed an issue where content item and comment widgets would show content from not specified categories.
Fixed an issue where editor auto saved content may not be removed.
Fixed an issue where some content may not show a report link.
Removed the hide signatures toggles from guests when they are able to see signatures.
Prevented search engine spiders from following the cookie notice dismissal link.
Removed poll votes from showing in the All Activity stream.
Removed ability to copy theme settings.
Removed the unread indicator in several widgets because it can't be used there because of the widget cache.
Removed the ability to toggle cover photos in clubs list when no image was uploaded.
Fixed an issue where the support tool could incorrectly report undiagnosed problems.
Fixed missing images when lazy loading is enabled in several areas.
Fixed an HTML validation issue with mini-pagination next to multi-page content item titles.
Fixed an uncaught exception which is thrown by the Admin Notification System.
Fixed member validation display issue in ACP notifications page while mobile.
Fixed attachments being added to an editor which has attachments disabled.
Fixed an issue where a display name sync error may be displayed on the AdminCP member profile.
Fixed two language strings where countries have changed their names: Macedonia is now North Macedonia and Swaziland is now Eswatini.
Fixed some broken messenger related links.
Core - Clubs

Fixed "Clubs" tab showing when splitting content even if clubs are disabled.
Fixed display issue with club tabs on mobile devices
Removed ability to reorder club tabs on mobile devices
Forums

Fixed a duplicated error code in the topics REST API endpoints.
Fixed images used in forum rules not displaying when image lazy loading is enabled.
Fixed a potential upgrade error when reformatting forum rules during the 4.0.0 upgrade routine.
Commerce

Added an additional subtotal language phrase to the cart summary for localization flexibility.
Improved legacy parser to potentially allow conversions of tables in content.
Changed renewal terms to not allow $0 renewals.
Fixed an error occurring submitting new tickets when read/write database separation is enabled.
Fixed an issue where up/downgrading a purchase could result in an error or the expiry date changing incorrectly.
Fixed an issue where a cancelled subscription may still generate a renewal invoice (and subsequently charge the user).
Fixed tax class being lost with renewal terms in some cases.
Fixed an issue where images may not show in printable invoices if lazyload is enabled.
Fixed support stream date-based filters producing incorrect results.
Fixed stock action text not defaulting in the form when creating a new ticket from the AdminCP if you do not use a signature.
Fixed an issue where the password field on the store checkout form might disappear if using Chrome's password autofill feature.
Fixed an issue where invoices may not have a billing address set when one is available.
Fixed an issue where a template error may be thrown for non-recurring subscriptions.
Fixed a missing language string if you had servers configured prior to upgrading to 4.4.
Fixed adding a custom package to an invoice.
Fixed an exception being logged when rebuilding the search index if any custom packages have been created.
Fixed the PayPal Billing Agreements radio element not showing selected if BAs are enabled.
Fixed an issue where files uploaded to a custom field may not be downloadable.
Removed a stray HTML end tag.
Restored Braintree gateway option. Included a disclaimer about qualification process.
Pages

Fixed an issue where cloning a custom field would result in a database error.
Fixed an issue on the media page where the "File Overview" Tab was not set as activated and also not clickable when one or more files from the file list were selected.
Added last modified date to database category and record sitemaps.
Gallery

Changed the reputation type flag for album comments and reviews to resolve bugs where the reputation may be mistakingly treated as if it belongs to an image comment or review.
Fixed an issue submitting images to an album if the category requires moderator approval.
Fixed an error that can occur when downloading the original image in Gallery if the original image is missing on disk by forcing the largest available size to download instead.
Fixed image lazy loading not working correctly in category rules, descriptions and custom error messages.
Fixed the submission dialog box potentially showing an incorrect dialog title.
Fixed an issue where uploaded videos could not be played in the lightbox.
Fixed editor showing twice for each image during submission.
Downloads

Added a group setting that will allow users to bypass download restrictions when downloading a file that's been purchased.
Added the ability to shut off version numbers per-category.
Improved the header styling on the homepage.
Improved performance, especially of the index page.
Reduced top spacing (margin) of the sidebar when viewing the index page.
Fixed an issue where custom fields may show out of order.
Blog

Fixed an issue where the previous and next link under the blog entry could link to hidden or soft deleted entries.
Fixed some minor UI issues with the "Blogs" widget.
Calendar

Added an option to prevent edits and RSVPs for events that have passed.
REST & OAuth

Fixed the search REST API endpoint.
Converters

Improved vBulletin archive redirects.
Improved vBulletin blog conversions to retain the date the blog or blog entry was followed.
Fixed an issue where converted members won't be marked as completed.
Fixed an issue when trying to convert from a platform with converters for apps that are not installed.
Fixed an issue where PM replies may be duplicated when converting from vBulletin.
Fixed an issue with converting comments from Vanilla.
Upgrader

Fixed an edge case issue where some legacy customers may be unable to use the AdminCP upgrader.
Changes affecting third-party developers and designers
Backwards-incompatible changes that may affect third party applications / plugins:

Methods that handle passwords in login handlers (authenticateUsernamePassword(), authenticatePasswordForMember(), changePassword()) now receive an object which can be cast to a string, rather than a normal string, for the password. This reduces the likelihood of a password being included in an error log.
The onPassChange MemberSync callback now receives an object which can be cast to a string, rather than a normal string, for the password. This reduces the likelihood of a password being included in an error log.
Enhancements / fixes for developers:

Added a new constant \IPS\DEV_LOG_HEADERS which allows you to log all headers being sent during responses.
Better abstracted code that dynamically builds class paths for areas that are no longer using iterators.
Improved some extension skeleton files to not cause a ParseError once the extension is created.
Fixed color fields not initializing for new rows added in a manageable matrix.
Fixes that only affect developer mode or third party apps/plugins:

Fixed some functions not being called from the root namespace and throwing warnings when in developer mode.
Fixed an undefined index loading form to add a new hosting server in Commerce.
Code-level fixes that may have been causing bugs in third party apps/plugins:

Added code comments to all of the default constant values in init.php explaining what they all do.
Ensured all default wizard instances are cast as a string before being sent to the output handler.
Fixed an issue when pluralization and sprintf functionality is used together and the placeholder is used in the pluralized string.
Fixed an issue with post before register where it was assumed content items would have a container.
Fixed an exception when post before registering is checked against a content item that supports reviews but not comments.
Fixed some ambiguous column concerns with the \IPS\Content\Item::_comments() method.
Fixed an issue editing titles via Ajax when the item class does not use containers.
Fixed an issue where the release date may not show correctly for third party plugins or themes.
Fixed some functions not being called from the root namespace and throwing an IN_DEV warning.
Fixed the widget configuration form being called twice which may result in some form elements duplicating.
Improved some extension skeleton files to not cause a ParseError once the extension is created.
