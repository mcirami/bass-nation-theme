=== GD bbPress Attachments ===
Contributors: GDragoN, freemius
Donate link: https://www.dev4press.com/plugins/gd-bbpress-attachments/
Stable tag: 4.9
Tags: dev4press, bbpress, attachments, upload, limit
Requires at least: 6.0
Requires PHP: 7.4
Tested up to: 6.8
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Implement attachments upload to the topics and replies in bbPress plugin through a media library and add additional forum-based controls.

== Description ==
GD bbPress Attachments is an easy-to-use plugin for WordPress and bbPress for implementing file upload for bbPress Forums topics and replies. You can control file sizes from the main plugin settings panel, or you can change some attachments settings for each forum individually. Currently, included features:

* Attachments are handled through WordPress media library. 
* Limit the number of files to upload at once.
* Embed a list of attached files into topics and replies.
* Attachment icon in the topic list for topics with attachments.
* Attachments icons for file types in the attachments list.
* Option to control if visitors can see a list of attachments.
* Display uploaded images as thumbnails.
* Control thumbnail size.
* Control thumbnail CLASS and REL attributes.
* Upload errors can be logged.
* Post author and administrators can see errors.
* Tool to clean up uploads error log entries from postmeta table.
* Administration: attachments count for topics and replies.
* Administration: metabox for settings override for forums.
* Administration: metabox with attachments list and errors for topics and replies.

= bbPress Plugin Versions =
GD bbPress Attachments 4.8 supports bbPress 2.6.2 or newer. Older bbPress versions are no longer supported!

= More free Dev4Press plugins for bbPress =
* [GD Forum Manager](https://wordpress.org/plugins/gd-forum-manager-for-bbpress/) - quick and bulk forums and topics edit
* [GD Members Directory](https://wordpress.org/plugins/gd-members-directory-for-bbpress/) - add new page with list of all forum members
* [GD bbPress Tools](https://wordpress.org/plugins/gd-bbpress-tools/) - various expansion tools for forums
* [powerSearch for bbPress](https://wordpress.org/plugins/gd-power-search-for-bbpress/) - add advanced search to the bbPress topics
* [topicPolls for bbPress](https://wordpress.org/plugins/gd-topic-polls/) - add polls to the bbPress topics

= Upgrade to forumToolbox for bbPress =
AKA: GD bbPress Toolbox Pro. The Pro version contains 63 features, with over 500 options to control the integration:

* Enhanced attachments features
* Limit file types attachments upload
* Add custom file types for upload
* BBCodes editor toolbar
* Report topics and replies
* Say thanks to forum members
* Various SEO features
* Various privacy features
* Enable TinyMCE editor
* Private topics and replies
* Auto closing of inactive topics
* Notification email control
* Show user stats in topics and replies
* Track new and unread topics
* Mute Forums and Users
* Great new responsive admin UI
* Setup Wizard
* Forum based settings overrides
* Edit: BuddyPress support
* 40 BBCodes (including Hide and Spoiler)
* 19 more Topics Views
* 9 additional widgets
* Many great tweaks
* And much, much more

With more features on the roadmap exclusively for a Pro version.

* More information about [forumToolbox for bbbPress](https://www.dev4press.com/plugins/gd-bbpress-toolbox/)
* More Premium plugins with [bbPress Plugins Club](https://www.dev4press.com/bbpress-club/)

== Installation ==
= General Requirements =
* PHP: 7.4 or newer

= WordPress Requirements =
* WordPress: 5.9 or newer

= bbPress Requirements =
* bbPress Plugin: 2.6.2 or newer

= Basic Installation =
* Plugin folder in the WordPress plugins folder must be `gd-bbpress-attachments`
* Upload folder `gd-bbpress-attachments` to the `/wp-content/plugins/` directory
* Activate the plugin through the 'Plugins' menu in WordPress

== Frequently Asked Questions ==
= Where can I configure the plugin? =
Open the Forums menu, and you will see Attachments item there. This will open a panel with global plugin settings.

= Why is Media Library required? =
All attachments uploads are handled by the WordPress Media Library, and plugin uses native WordPress upload functions. When the file is uploaded it will be available through Media Library. Consult WordPress documentation about Media Library requirements.

= Sometimes upload fails without any error? =
If there is no error to log, it means that PHP during upload has not provided any information about upload, and that upload has failed before reaching WordPress. Usually, this is related to the file size limit in the PHP.

= How can I increase PHP file size limit? =
This has to be done on server, in the PHP.INI file (depends on the hosting) with the directive called `upload_max_filesize`. Also, you need to increase `post_max_size` value also. If you allow MAX file size of 32MB, and you want to have 4 files uploaded at once, `upload_max_filesize` has to be 32MB and `post_max_size` has to be 128M or more.

= Does this plugin work with bbPress and BuddyPress groups? =
GD bbPress Attachments 4.6 is tested with BuddyPress 10.0 and newer, using bbPress for Groups forums. Make sure you enable JavaScript and CSS Settings Always Include option in the plugin settings.

= What are the common problems that can prevent upload to work? =
In some cases, it can happen that jQuery is not included on the page, even so, the bbPress requires it to be loaded. That can happen if something else is unloading jQuery. If the jQuery is not present, the upload will not work.
Another common issue is that WordPress Media Library upload is not working. If that is not set up, attachments upload can't work.

= Will this plugin work with standalone (non WordPress) bbPress installation? =
No. This plugin requires the plugin versions of bbPress 2.6.2 or higher.

== Upgrade Notice ==
= 4.9 =
Few updates and improvements.

= 4.8 =
Few updates and improvements.

= 4.7 =
Few updates and improvements.

== Changelog ==
= 4.9 (2025.05.31) =
* New: tested with `WordPress` 6.8
* New: tested with `bbPress` up to 2.6.13
* Edit: additional validation and sanitization
* Edit: various minor tweaks and updates
* Edit: Freemius SDK 2.12.0
* Fix: early triggering of the translation functions

= 4.8 (2025.02.15) =
* New: integration with `Freemius SDK`
* New: option to upgrade to `forumToolbox for bbPress`
* Edit: a lot of small PHP code related updates
* Edit: changes to many of the links nad plugin names
* Fix: problem with the plugin directory name
* Fix: issue with loading plugin translation files

= 4.7.3 (2024.11.15) =
* Edit: proper escaping of the URL's in several instances
* Edit: proper sanitization of the HTML class in few instances
* Fix: reflected cross-site scripting with the attachment actions

= 4.7.2 (2024.08.19) =
* Edit: updated links to the Dev4Press website
* Edit: various PHP code changes and improvements

= 4.7.1 (2024.05.15) =
* Edit: various small updates to readme file

= 4.7 (2024.04.28) =
* New: directive `Requires Plugin` added into main plugin file
* New: System requirements: PHP 7.4 or newer
* New: System requirements: WordPress 5.8 or newer
* New: plugin fully tested with WordPress up to 6.5
* New: plugin fully tested with PHP 8.3
* Edit: code style and translation formatting

= 4.6 (2023.05.26) =
* New: filter `d4p_bbpressattchment_login_url` for the login URL
* New: FAQ information included in the plugin readme file
* New: plugin is tested with the upload of the WEBP images
* Edit: more settings page with use of escaping for attributes
* Edit: icons format definition for attached files

= 4.5 (2023.03.08) =
* New: Tools tab added to plugin admin page
* New: Tools: option to clear all logged upload errors
* New: System requirements: PHP 7.3 or newer
* New: System requirements: WordPress 5.5 or newer
* New: plugin fully tested with PHP 8.0, 8.1 and 8.2
* Edit: various things in PHP code for better PHP 8.x compatibility
* Edit: plugin admin interface items for better accessibility
* Fix: some accessibility issues with options labels

= 4.4 (2022.12.03) =
* New: plugin is tested with WordPress 6.1
* Edit: rendering of attachments with improving escaping for attributes
* Edit: settings page with use of escaping for attributes
* Edit: some system requirements
* Fix: XSS vulnerability (thanks to [Lana Codes](https://patchstack.com/) for reporting)

= 4.3.1 (2022.05.16) =
* New: plugin is tested with WordPress 6.0

= 4.3 (2021.10.05) =
* New: system requirements: WordPress 5.1 or newer
* Improvements to the plugin readme file
* Few more minor updates

= 4.2 (2021.01.30) =
* New: system requirements: PHP 7.0 or newer
* New: system requirements: WordPress 5.0 or newer
* New: system requirements: bbPress 2.6.2 or newer
* Improvements to the rendering attachments code
* Various minor updates and improvements

= 4.1 (2020.07.23) =
* New: rendering method for attachments list
* New: classes added to the attachments OL wrappers
* Trigger topic/reply edit revision when attachment is added
* Improvements to the attachments' layout styling
* Improvements to the attachments icons styling
* Improvements to the plugin readme file
* Fix: some issues when showing attachments list on small screens
* Fix: some styling issues with various themes

= 4.0.1 (2020.05.13) =
* Improvements to the plugin readme file
* Few minor updates to the frontend CSS code
* Fix: few more typos

= 4.0 (2020.05.08) =
* New: fieldset used to wrap attachments upload control
* New: replacement function to determine valid forum id
* New: confirmation dialog for deleting and detaching files
* New: reorganized plugin code and the way it is loaded and run
* New: completely rewritten JavaScript for attachments handling
* New: rewritten loading of JavaScript and CSS files
* New: fully reorganized CSS now written using SCSS
* New: loading JavaScript and CSS minified or normal
* New: error icon added to the list of errors
* Replaced icon for the attachment in the topics list
* Removed: obsolete form encoding attribute settings
* Fix: issue with the topic and reply edit pages

= 3.2 (2019.09.02) =
* Show KB or MB file size limit depending on the size
* Removed: all outdated translations
* Removed: some duplicated links
* Fix: a minor sanitation issues related to shortcodes

= 3.1 (2019.03.11) =
* Few minor updates and improvements

= 3.0.1 (2018.10.05) =
* Fix: problem with attachments save reply method passed arguments

= 3.0 (2018.07.26) =
* New: interface for the plugin settings panel
* New: panel with advanced settings
* New: panel with images settings
* New: support for thumbnails for PDF and SVG file types
* Edit: settings form with proper field types
* Edit: toolbar icon to use bbPress dashicon

= 2.6 (2018.04.27) =
* Edit: plugin requirements
* Sanitize file name stored for the upload errors
* Escape the file name displayed for upload errors
* Fix: potential stored XSS vulnerability (thanks to [Luigi Gubello](https://www.gubello.me/blog/) for reporting)
* Fix: few typos and missing translation strings

= 2.5 (2017.09.20) =
* Edit: JS and CSS files are by default always loaded
* Edit: WordPress minimal requirement to 4.2
* Edit: several broken URLs
* Edit: and improved readme file

= 2.4 (2016.09.24) =
* New: download attribute to attached files links
* Edit: sanitation of the plugin settings on save
* Edit: PHP minimal requirement to 5.3
* Edit: WordPress minimal requirement to 4.0
* Edit: several broken URL's
* Edit: several missing translation strings

= 2.3.2 (2015.08.02) =
* New: Swedish translation
* Edit: readme file

= 2.3.1 (2015.07.09) =
* New: Russian translation
* Edit: readme file

= 2.3 (2015.07.04) =
* Edit: several Dev4Press links
* Fix: XSS and LFI security issues with un-sanitized input
* Fix: order of displayed attachments to match upload order
* Fix: inline image alignment when there is no image caption

= 2.2 (2015.03.06) =
* Fix: problem with uploading video or audio files in some cases

= 2.1 =
* Edit: default styling for the list of attachments
* Removed: support for bbPress 2.2.x
* Fix: posts deletion problem caused by attachments module

= 2.0 =
* Edit: default styling for the list of attachments
* Removed: obsolete hooks and functions
* Removed: support for bbPress 2.1.x
* Fix: method for adding some plugin hooks
* Fix: issue with attachments DIV not closed properly
* Fix: few typos and missing translation strings

= 1.9.2 =
* New: Slovak translation
* Changed upload field location to end of the form
* Dropped support for bbPress 2.0
* Dropped support for WordPress 3.2
* Fix: problem with saving some settings

= 1.9.1 =
* Fix: detection of bbPress 2.2
* Fix: missing function fatal error

= 1.9 =
* New: support for dynamic roles from bbPress 2.2
* New: class to attachments elements in the topic/reply forms
* Using enqueue scripts and styles to load files on frontend
* Admin menu now uses 'activate_plugins' capability by default
* Screenshots removed from plugin and added into assets directory
* Fix: problem with some themes and embedding of JavaScript
* Fix: issues with some themes and displaying attachments

= 1.8.4 =
* Additional settings information
* BuddyPress with site wide bbPress supported
* Expanded list of FAQ entries
* Panel for upgrade to GD bbPress Toolbox
* Fix: duplicated registration for reply embed filter

= 1.8.3 =
* New: Italian translation
* Edit: several translations

= 1.8.2 =
* New: Portuguese translation

= 1.8.1 =
* Adding meta field to identify file as attachment
* Few minor issues with plugin settings

= 1.8 =
* New: option to display thumbnails in line
* New: Persian translation
* Improvements for the bbPress 2.1 compatibility
* Several embedding styling improvements
* Fix: some loading issues for admin side

= 1.7.6 =
* Changes to readme.txt file
* Improvements to the shared code

= 1.7.5 =
* Additional loading optimization
* New: French language
* Edit: some translations
* Fix: minor issues with saving settings

= 1.7.2 =
* Missing license.txt file

= 1.7.1 =
* New: option for improved embedding of JS and CSS code
* Minor changes to the plugins admin interface panels
* Edit: and expanded plugin FAQ and requirements

= 1.7 =
* Loading optimization with separate admin and front end code
* New: options for deleting and detaching attachments
* New: several new filters for additional plugin control
* New: option for error logging visibility for moderators
* Fix: logging of multiple upload errors
* Fix: several issues with displaying upload errors

= 1.6 =
* New: hide attachments from visitors option
* New: option to hook in topic and reply deletion
* New: Polish translation
* Edit: adding of plugin styling and JavaScript
* Fix: visibility of meta settings for non admins

= 1.5.3 =
* Context Help for WordPress 3.3

= 1.5.2 =
* Rel attribute allows use of topic or reply ID
* Admin topic and reply editor list of errors
* Edit: German and Serbian translations
* Edit: readme file with error logging information

= 1.5.1 =
* Fix: logging of empty error messages

= 1.5 =
* Edit: tabbed admin interface
* Image attachments display and styling
* Error logging displayed to admin and author
* Fix: upload from edit topic and reply
* Fix: including of jQuery into header
* Fix: bbPress detection for edit pages

= 1.2.4 =
* Edit: Dutch Translation
* Edit: Frequently Asked Questions

= 1.2.3 =
* Minor change to user roles detection
* Fix: problem with displaying attachments to visitors

= 1.2.2 =
* Spanish Translation

= 1.2.1 =
* German Translation
* Check for the bbPress to add JavaScript and CSS

= 1.2.0 =
* Disable attachments for individual forums
* Edit: admin side topic and reply editor integration

= 1.1.0 =
* Attachments icons in the attachment lists

= 1.0.4 =
* Attachment icon of forums

= 1.0.3 =
* Serbian Translation
* Dutch Translation

= 1.0.2 =
* Edit: Improvements to the main settings panel
* Fix: missing variable for topic attachments saving
* Fix: ignoring selected roles to display upload form elements
* Fix: upgrading plugin settings process
* Fix: few more undefined variables warnings

== Screenshots ==
1. Main plugins settings panel
2. Images settings panel
3. Advanced settings panel
4. Reply with attachments and file type icons
5. Attachments upload elements in the form
6. Single forum meta box with settings
7. Icons for the forums with attachments
8. Thumbnails displayed in line
9. Attachments with delete and detach actions
10. Image attachments with upload errors
