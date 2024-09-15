# Release Notes for 1.6.x

## 1.6.0 - September 24, 2024

### ✨ Added

- Added the [Internationalization](https://wpbones.vercel.app/docs/Internationalization/overview) support for the ReactJS app and blocks.
- Added the [Core Classes](https://wpbones.vercel.app/docs/CoreClasses/overview) documentation.
- Added the [Core Plugin Files](https://wpbones.vercel.app/docs/CorePluginFiles/overview) documentation.
- Added the [FAQs](https://wpbones.vercel.app/docs/faqs) documentation.
- Added the npm script [`make-pot`](https://developer.wordpress.org/cli/commands/i18n/make-pot/) to generate the `.pot` file for the ReactJS app and blocks.
- Added the npm script [`make-json`](https://developer.wordpress.org/cli/commands/i18n/make-json/) to generate the `.json` file for the ReactJS app and blocks.
- Added the npm script [`package-update`](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-scripts/#packages-update).
- Added the npm script [`check-engines`](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-scripts/#check-engines).
- Added the npm script [`check-licenses`](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-scripts/#check-licenses).
- Added the npm script `format` to format the code.
- Added the [`wp_set_script_translations()`](https://developer.wordpress.org/reference/functions/wp_set_script_translations/) support for the ReactJS app and blocks.
- Added a new [`dev`](https://github.com/wpbones/WPBones/tree/dev) branch for the development.
- Added [`withInlineScript()`](https://wpbones.vercel.app/docs/CoreClasses/view#withinlinescript) fluent method in the [`View Class`](https://wpbones.vercel.app/docs/CoreClasses/view)
- Added [`withInlineStyle()`](https://wpbones.vercel.app/docs/CoreClasses/view#withinlinestyle) fluent method in the [`View Class`](https://wpbones.vercel.app/docs/CoreClasses/view)
- Added [CHANGELOG.md](https://github.com/wpbones/WPBones/blob/master/CHANGELOG.md) file.
- Added new [WP Bones API Boilerplate](https://github.com/wpbones/WPKirk-API-Boilerplate) example plugin.
- Added new [WP Bones Internationalization Boilerplate](https://github.com/wpbones/WPKirk-Internationalization-Boilerplate) example plugin.
- Added new [WP Bones Mantine Boilerplate](https://github.com/wpbones/WPKirk-Mantine-Boilerplate) example plugin.
- Added new [WP Bones ReactJS Boilerplate](https://github.com/wpbones/WPKirk-ReactJS-Boilerplate) example plugin.
- Added new [WP Bones Routes Boilerplate](https://github.com/wpbones/WPKirk-Routes-Boilerplate) example plugin.
- Added a new [Flags Package](https://wpbones.vercel.app/docs/Packages/flags) to manage the static feature flags in your plugin.

### 💎 Changed and Improved

- The `bones` command displays the [WP-CLI](https://make.wordpress.org/cli/handbook/guides/installing/) version.
- Minor fixes and improvements in the `bones` command.
- Updated and improved the [Documentation](https://wpbones.vercel.app/docs).
- Updated the [WPBones demo plugin](https://github.com/wpbones/WPKirk).
- Updated the [WPBones Boilerplate plugin](https://github.com/wpbones/WPKirk-Boilerplate)
- Update the [Help Functions](https://wpbones.vercel.app/docs/helpers) documentation.

### 🐛 Fixed

- Fixed an issue where admin scripts and styles were always being loaded in the [`View Class`](https://wpbones.vercel.app/docs/CoreClasses/view), even on the theme side.
- Fixed Compatibility with macOS `.DS_Store` files (#47)

## 💥 Breaking Changes

- Deprecated `withAdminScripts()` and `withAdminStyles()` fluent methods in the [`View Class`](https://wpbones.vercel.app/docs/CoreClasses/view) - use `withAdminScript()` and `withAdminStyle()` instead.
- Deprecated `withLocalizeScripts()` fluent methods in the [`View Class`](https://wpbones.vercel.app/docs/CoreClasses/view) - use `withLocalizeScript()` instead.
- Deprecated `withAdminAppsScripts()` fluent methods in the [`View Class`](https://wpbones.vercel.app/docs/CoreClasses/view) - use `withAdminAppsScript()` instead.
- Deprecated `getBasePath()`and `getBaseUri()` methods in the [`Plugin Class`](https://wpbones.vercel.app/docs/CoreClasses/plugin) - use `basePath` and `baseUri` properties instead.
- In the [WPBones demo plugin](https://github.com/wpbones/WPKirk) and [WPBones Boilerplate plugin](https://github.com/wpbones/WPKirk-Boilerplate) we have renamed the `localization` folder to `languages`.

## 🤝 Suggestions

- To use the new npm scripts for the localization, you need to install [WP-CLI](https://make.wordpress.org/cli/handbook/guides/installing/).

### 🧑‍💻👩‍💻 New Contributors

- [@bredecl](https://github.com/bredecl)
