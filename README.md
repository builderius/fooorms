# Fooorms

## Overview

Fooorms is a WordPress plugin for creating a back-end functionality for any front end forms 
which can send dat ato REST API endpoint.

**Important: Foorms has dependency of Advanced Custom Fields. You must have this plugin 
installed and activated in order to use Foorms plugin!**

Features:

- add REST API endpoints for forms' submissions - as many as needed;
- save records-submissions into DB;
- create email templates - as many as needed - plus use forms data inside the templates;

Fooorms integrates well with [Builderius](https://builderius.io) and its Smart Forms.

## Usage

1) **Fooorms -> Add new** - create a form, configure it.
2) Add a new ACF field group. Add new fields with the type "Fooorms form field". At the end create a rule to show these fields on specific Foooorms form.

Back end setup is completed!

Fot the front end part, you may use a form from any plugin or custom coded one! **Important:
the form must be able to submit JSON data to REST API endpoint!** Please, check "API" section
for functions helpers, like how to get REST API endpoint URL for specific form etc.

## API

Few functions-helpers to integrate better with your front end form!

`fooorms_get_smartform_action($form_key)`

This function returns an URL to REST API endpoint where you must submit your form to. The 
only argument is a form key.

`fooorms_get_smartform_success_msg($form_key, $translate = true)`

This function returns the success message as it is set up for the given form. The
first argument is required, it is a form key. The second argument is optional,
it is needed only if you are using Builderius/Smart form.

`fooorms_get_smartform_validation_schema($form_key, $translate = true)`

This function returns an array of validation config for the given form. The
first argument is required, it is a form key. The second argument is optional. This is 
Builderius/Smart Form specific.


## Credits

Fooorms is a fork of [Advanced Forms for ACF](https://wordpress.org/plugins/advanced-forms/).
More specifically, it replicates a big part of UI architecture of AF plugin.

However, Fooorms differs in purpose - it was created to serve as back-end part of 
forms functionality specifically. Unlike AF, Fooorms does NOT create/generate front-end 
part of the forms!