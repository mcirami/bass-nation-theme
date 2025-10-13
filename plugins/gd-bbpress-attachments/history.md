# GD bbPress Attachments

## Changelog

### Version 4.6 (2023.05.26)
* **new** filter `d4p_bbpressattchment_login_url` for the login URL
* **new** FAQ information included in the plugin readme file
* **new** plugin is tested with the upload of the WEBP images
* **edit** more settings page with use of escaping for attributes
* **edit** icons format definition for attached files

### Version 4.5 (2023.03.08)
* **new** Tools tab added to plugin admin page
* **new** Tools: option to clear all logged upload errors
* **new** System requirements: PHP 7.3 or newer
* **new** System requirements: WordPress 5.5 or newer
* **new** plugin fully tested with PHP 8.0, 8.1 and 8.2
* **edit** various things in PHP code for better PHP 8.x compatibility
* **edit** plugin admin interface items for better accessibility
* **fix** some accessibility issues with options labels

### Version 4.4 (2022.12.03)
* **new** plugin is tested with WordPress 6.1
* **edit** rendering of attachments with improving escaping for attributes
* **edit** settings page with use of escaping for attributes
* **edit** some system requirements
* **fix** XSS vulnerability (thanks to [Lana Codes](https://patchstack.com/) for reporting)

### Version 4.3.1 (2022.05.16)
* **new** plugin is tested with WordPress 6.0

### Version 4.3 (2021.10.05)
* **new** system requirements: WordPress 5.1 or newer
* **edit** improvements to the plugin readme file
* **edit** few more minor updates

### Version 4.2 (2021.01.30)
* **new** system requirements: PHP 7.0 or newer
* **new** system requirements: WordPress 5.0 or newer
* **new** system requirements: bbPress 2.6.2 or newer
* **edit** improvements to the rendering attachments code
* **edit** various minor updates and improvements

### Version 4.1 (2020.07.23)
* **new** rendering method for attachments list
* **new** classes added to the attachments OL wrappers
* **new** trigger topic/reply edit revision when attachment is added
* **edit** improvements to the attachments' layout styling
* **edit** improvements to the attachments icons styling
* **edit** improvements to the plugin readme file
* **fix** some issues when showing attachments list on small screens
* **fix** some styling issues with various themes

### Version 4.0.1 (2020.05.13)
* **edit** improvements to the plugin readme file
* Few minor updates to the frontend CSS code
* **fix** few more typos

### Version 4.0 (2020.05.08)
* **new** fieldset used to wrap attachments upload control
* **new** replacement function to determine valid forum id
* **new** confirmation dialog for deleting and detaching files
* **new** reorganized plugin code and the way it is loaded and run
* **new** completely rewritten JavaScript for attachments handling
* **new** rewritten loading of JavaScript and CSS files
* **new** fully reorganized CSS now written using SCSS
* **new** loading JavaScript and CSS minified or normal
* **new** error icon added to the list of errors
* Replaced icon for the attachment in the topics list
* **del** obsolete form encoding attribute settings
* **fix** issue with the topic and reply edit pages

### Version 3.2 (2019.09.02)
* **new** show KB or MB file size limit depending on the size
* **del** all outdated translations
* **del** some duplicated links
* **fix** a minor sanitation issues related to shortcodes

### Version 3.1 (2019.03.11)
* Few minor updates and improvements

### Version 3.0.1 (2018.10.05)
* **fix** problem with attachments save reply method passed arguments

### Version 3.0 (2018.07.26)
* **new** interface for the plugin settings panel
* **new** panel with advanced settings
* **new** panel with images settings
* **new** support for thumbnails for PDF and SVG file types
* **edit** settings form with proper field types
* **edit** toolbar icon to use bbPress dashicon

### Version 2.6 (2018.04.27)
* **new** sanitize file name stored for the upload errors
* **new** escape the file name displayed for upload errors
* **edit** plugin requirements
* **fix** potential stored XSS vulnerability (thanks to [Luigi Gubello](https://www.gubello.me/blog/) for reporting)
* **fix** few typos and missing translation strings

### Version 2.5 (2017.09.20)
* **edit** JS and CSS files are by default always loaded
* **edit** WordPress minimal requirement to 4.2
* **edit** several broken URLs
* **edit** and improved readme file

### Version 2.4 (2016.09.24)
* **new** download attribute to attached files links
* **edit** sanitation of the plugin settings on save
* **edit** PHP minimal requirement to 5.3
* **edit** WordPress minimal requirement to 4.0
* **edit** several broken URL's
* **edit** several missing translation strings

### Version 2.3.2 (2015.08.02)
* **new** Swedish translation
* **edit** readme file

### Version 2.3.1 (2015.07.09)
* **new** Russian translation
* **edit** readme file

### Version 2.3 (2015.07.04)
* **edit** several Dev4Press links
* **fix** XSS and LFI security issues with un-sanitized input
* **fix** order of displayed attachments to match upload order
* **fix** inline image alignment when there is no image caption

### Version 2.2 (2015.03.06)
* **fix** problem with uploading video or audio files in some cases

### Version 2.1
* **edit** improved default styling for the list of attachments
* **del** support for bbPress 2.2.x
* **fix** posts deletion problem caused by attachments module

### Version 2.0
* **edit** improved default styling for the list of attachments
* **del** obsolete hooks and functions
* **del** support for bbPress 2.1.x
* **fix** method for adding some plugin hooks
* **fix** issue with attachments DIV not closed properly
* **fix** few typos and missing translation strings

### Version 1.9.2
* **new** Slovak translation
* **edit** changed upload field location to end of the form
* **del** dropped support for bbPress 2.0
* **del** dropped support for WordPress 3.2
* **fix** problem with saving some settings

### Version 1.9.1
* **fix** detection of bbPress 2.2
* **fix** missing function fatal error

### Version 1.9
* **new** support for dynamic roles from bbPress 2.2
* **new** class to attachments elements in the topic/reply forms
* **edit** using enqueue scripts and styles to load files on frontend
* **edit** admin menu now uses 'activate_plugins' capability by default
* **edit** screenshots removed from plugin and added into assets directory
* **fix** problem with some themes and embedding of JavaScript
* **fix** issues with some themes and displaying attachments

### Version 1.8.4
* **new** additional settings information
* **new** BuddyPress with site wide bbPress supported
* **new** expanded list of FAQ entries
* **new** panel for upgrade to GD bbPress Toolbox
* **fix** duplicated registration for reply embed filter

### Version 1.8.3
* **new** Italian translation
* **edit** several translations

### Version 1.8.2
* **new** Portuguese translation

### Version 1.8.1
* **new** adding meta field to identify file as attachment
* **fix** few minor issues with plugin settings

### Version 1.8
* **new** option to display thumbnails in line
* **new** Persian translation
* **edit** improvements for the bbPress 2.1 compatibility
* **edit** several embedding styling improvements
* **fix** some loading issues for admin side

### Version 1.7.6
* **edit** changes to readme.txt file
* **edit** improvements to the shared code

### Version 1.7.5
* **new** additional loading optimization
* **new** French language
* **edit** some translations
* **fix** **edit** minor issues with saving settings

### Version 1.7.2
* **fix** missing license.txt file

### Version 1.7.1
* **new** option for improved embedding of JS and CSS code
* **edit** minor changes to the plugins admin interface panels
* **edit** and expanded plugin FAQ and requirements

### Version 1.7
* Loading optimization with separate admin and front end code
* **new** options for deleting and detaching attachments
* **new** several new filters for additional plugin control
* **new** option for error logging visibility for moderators
* **fix** logging of multiple upload errors
* **fix** several issues with displaying upload errors

### Version 1.6
* **new** hide attachments from visitors option
* **new** option to hook in topic and reply deletion
* **new** Polish translation
* **edit** improved adding of plugin styling and JavaScript
* **fix** visibility of meta settings for non admins

### Version 1.5.3
* **new** context Help for WordPress 3.3

### Version 1.5.2
* **new** rel attribute allows use of topic or reply ID
* **new** admin topic and reply editor list of errors
* **edit** German and Serbian translations
* **edit** readme file with error logging information

### Version 1.5.1
* **fix** logging of empty error messages

### Version 1.5
* **new** image attachments display and styling
* **new** error logging displayed to admin and author
* **edit** improved tabbed admin interface
* **fix** upload from edit topic and reply
* **fix** including of jQuery into header
* **fix** bbPress detection for edit pages

### Version 1.2.4
* **edit** improved Dutch Translation
* **edit** Frequently Asked Questions

### Version 1.2.3
* **edit** minor change to user roles detection
* **fix** problem with displaying attachments to visitors

### Version 1.2.2
* **new** Spanish Translation

### Version 1.2.1
* **new** German Translation
* **edit** check for the bbPress to add JavaScript and CSS

### Version 1.2.0
* **new** disable attachments for individual forums
* **edit** improved admin side topic and reply editor integration

### Version 1.1.0
* **new** attachments icons in the attachment lists

### Version 1.0.4
* **new** attachment icon of forums

### Version 1.0.3
* **new** Serbian Translation
* **new** Dutch Translation

### Version 1.0.2
* **edit** improvements to the main settings panel
* **fix** missing variable for topic attachments saving
* **fix** ignoring selected roles to display upload form elements
* **fix** upgrading plugin settings process
* **fix** few more undefined variables warnings

### Version: 1.0.0
* First official release