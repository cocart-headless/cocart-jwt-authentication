# WARNING! DO NOT PUT CUSTOM TRANSLATIONS HERE!

CoCart will delete all custom translations placed in this directory. Every Monday translations are pulled from our GlotPress if 30% of the language is translated.

## Where to put my custom translations for CoCart JWT Authentication?

Put your custom CoCart JWT Authentication translations in your WordPress language directory, located at: `WP_LANG_DIR . "/cocart-jwt-authentication/{$textdomain}-{$locale}.mo";`

## How do I translate CoCart JWT Authentication?

If you want to help translate CoCart JWT Authentication, please visit our [GlotPress](https://translate.cocartapi.com/projects/cocart-jwt-authentication/). There you can select the language to translate. If a language is not listed the please request it.

If CoCart JWT Authentication is already 100% translated for your language, join anyway! The language files are regularly updated with new strings that need translation and will likely be added soon.

## String localization guidelines

 1. Use `cocart-jwt-authentication` textdomain in all strings.
 2. When using dynamic strings in printf/sprintf, if you are replacing > 1 string use numbered args. e.g. `Test %s string %s.` would be `Test %1$s string %2$s.`
 3. Use sentence case. e.g. `Some Thing` should be `Some thing`.
 4. Avoid HTML. If needed, insert the HTML using sprintf.

For more information, see WP core document [i18n for WordPress Developers](https://codex.wordpress.org/I18n_for_WordPress_Developers).
