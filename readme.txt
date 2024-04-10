=== Fooorms! ===
Contributors: builderius, mrpsiho
Tags: form, form builder, rest api, email template
Requires at least: 6.0
Tested up to: 6.5
Stable tag: 1.4.0
License: GPLv2.1
License URI: http://www.gnu.org/licenses/gpl-2.1.html

Fooorms is a REST endpoints and email templates manager. Think of it as a back-end functionality for your front-end
forms.

== Description ==

Fooorms is a REST endpoints and email templates manager. Think of it as a back-end functionality for your front-end
forms. These two main plugin's functionalities:
- create REST endpoints for submitting forms by using RESTful API;
- form submissions manager and email templates editor;

Fooorms works best with Builderius site builder plugin and its Smart Form module!

== Credit ==

Fooorms is a fork of the Advanced Forms for ACF plugin by philkurth and fabianlindfors which was released under the GPL 2.0.

== Installation ==

1. Upload `fooorms` to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress


== Frequently Asked Questions ==

= Does it work with any WordPress theme? =

Yes, it will work with any standard WordPress theme.

= Do I have to have ACF plugin installed and activated? =

Yes, Fooorms admin UI is built on ACF fields. You have to have ACF PRO version in order to use Fooorms.

== Screenshots ==


== Changelog ==

= 1.4.0 =
* Fixed: errors when uploading files
* Fixed: sending files through custom SMTP server

= 1.3.0 =
* Fixed: the script now returns success/error object in the same format as in version 1.0

= 1.2.0 =
* Added a possibility to use your own SMTP server on per form basis

= 1.1.0 =
* Added a possibility to set custom error message for validation type 'required'.
* Added 'Forms' and 'Entries' quick links for plugin item. For convenient navigation.
* Updated description of the plugin and added credits

= 1.0.0 =
* Initial Release
