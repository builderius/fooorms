# Fooorms

## Usecases

- Headless WP setup that acts like a hub for form submissions from 
React/Vue/Svelte/you_name_it based front-end application.
- Backend solution for possible WP based SaaS like Hubspot web forms
or Jotform or Typeform etc.
- White-label Knowledge Base WP site/SaaS.

## Overview

Fooorms is a WordPress plugin for creating a back-end functionality for 
web forms which send data to REST API endpoint.

**Important: Fooorms has a dependency - Advanced Custom Fields. You must 
have this plugin installed and activated in order to use Fooorms 
plugin! ACF plugin is NOT included!**

## Features:

- create form(s) for submitting data from any web form; each form has
its own REST API endpoint;
- create form fields validation schema to be used for back-end validation;
forms may share same schema or use their unique ones;
- save records/submissions into DB;
- send email(s) upon successful form submission;
- create customizable email templates for your forms plus use the
submitted form data inside the templates;
- a possibility to configure (provide credentials for) SMTP 
server(s) for each form;

Fooorms integrates well with 
[Builderius site builder](https://builderius.io) and its Smart Forms.

## Usage

### Setting up the back-end

1) **Fooorms -> Add new** - create a form and configure it. The 
configuration includes setting up a "success submission" text,
email templates, optionally external SMTP servers and deciding whether
form submissions should be saved to DB in addition to sending emails.
2) Add a new ACF field group. Add new fields with the type "Fooorms 
form field". Each field has type (default: "string") and can include
validation rules. The rules can be of two formats:
- Builderius/Smart Form compatible format based on expressions;
- Validate.js compatible format based on JSON;
Finally, assign the field group to a specific Fooorms form.

### Setting up the front-end

For the front-end part you may use a form from any plugin or custom 
coded one! **Important: the form must be able to submit JSON data to 
a REST API endpoint!**

The plugin exposes an endpoint for getting form config. It returns a
JSON object with these keys:
- `submitUrl` - data submission endpoint URL;
- `successText` - text of success submission;
- `eeValidation` - expressions based validation config;
- `jsValidation` - [validate.js](https://validatejs.org/) compatible 
validation config;

#### For users of Builderius site builder and Smart Form

Use `eeValidation` config in Smart Form along with `submitUrl`.
Smart Form automatically recognizes the validation config and uses it.

#### For users of a custom React/Vue/Svelte/etc web form

Personally, I would recommend to check 
[validate.js](https://validatejs.org/) library and possibly build
your form validation based on this library. It may look like this:

```js
import validate from 'validate.js';

// .... later in your code

const handleSubmit = (formData) => {
    // 'formData' is a JS object that contains
    // your form data
    // 'jsValidation' is the validation config from our API
    const validationResult = validate(formData, jsValidation);

    // it must be 'undefined', that means no errors
    if (!validationResult) {
        // ... form submission logic
    }
}
```

However, you may use your own solution for form validation and
use the validation config somehow. It looks like this (just an 
example generated for some real project):

```json
{
  "jsValidation":{
    "first_name":{
      "type":"string",
      "presence":{
        "message":"Nome \u00e8 obligatorio."
      }
    },
    "last_name":{
      "type":"string",
      "presence":{
        "message":"Last name is required!"
      }
    },
    "email":{
      "type":"string",
      "presence":{
        "message":"Email is required!"
      },
      "email":true
    },
    "tel":{
      "type":"integer"
    },
    "company":{
      "type":"string",
      "presence":{
        "message":"Company is required!"
      }
    },
    "message":{
      "type":"string",
      "presence":{
        "message":"Message is required!"
      }
    },
    "privacy":{
      "type":"array",
      "presence":{
        "message":"Privacy is required!"
      }
    }
  }
}
```

## PHP based API

Few PHP functions-helpers to integrate better with your 
front-end form when you use it within the same WordPress site.

### `fooorms_get_smartform_action($form_key)`

This function returns an URL to REST API endpoint where you must 
submit your form to. The only argument is a form key.

### `fooorms_get_smartform_success_msg($form_key, $translate = true)`

This function returns the success message as it is set up for the 
given form. The first argument is required, it is a form key. The 
second argument is optional, it is needed only if you are using 
Builderius/Smart form.

### `fooorms_get_smartform_validation_schema($form_key, $translate = true)`

This function returns an array of validation config for the given 
form. The first argument is required, it is a form key. The second 
argument is optional. This is Builderius/Smart Form specific.

## REST based API

### `https://<your_domain>/?rest_route=/fooorms/v1/config/<your_form_key>`

This is the main REST API endpoint for getting form configuration. Use
it for your non WP based forms/applications.

## Credits

Fooorms is a fork of 
[Advanced Forms for ACF](https://wordpress.org/plugins/advanced-forms/).
More specifically, it replicates a big part of UI architecture of AF 
plugin.

However, Fooorms is different in its purpose - it was created to 
serve as back-end part of forms functionality specifically. Unlike AF, 
Fooorms does NOT create/generate front-end part of the forms! 
At least not yet ;)