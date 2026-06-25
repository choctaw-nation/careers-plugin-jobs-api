# Copilot Instructions

## Project Context

This repository contains a WordPress plugin for the Choctaw Nation of Oklahoma, running on a custom classic/hybrid WordPress theme.

## General Principles

Write code that is readable, maintainable, accessible, and consistent with WordPress conventions.

Prefer simple, explicit solutions over clever abstractions.

Do not introduce unnecessary dependencies.

Do not assume multilingual support is needed. Do not use WordPress translation, localization, or internationalization functions unless explicitly requested.

Prioritize accessibility, semantic HTML, keyboard support, and screen reader compatibility in all UI work.

When generating code, include enough surrounding context to make the change clear, but avoid rewriting unrelated files.

## Validation Before Completion

Before considering any file change finished, run the relevant lint commands and fix any errors that occurred:

-   For PHP, run `composer phpcbf && composer phpcs` from `wp-content/themes/cno-starter-theme`
-   For JS/TS files, run `npm run lint:js`
-   For SCSS/CSS files, run `npm run lint:css`

If a command fails, fix the issue before marking the work complete. If the failure is unrelated to the current change, report the failing command and the relevant error.

## WordPress Standards

Follow WordPress Coding Standards for PHP, JavaScript, CSS, and documentation.

Use WordPress APIs where appropriate.

Escape output at the point of rendering.

Sanitize and validate input as close to the source as practical.

Use nonces and capability checks for privileged actions.

Prefer hooks, filters, template parts, block patterns, and theme-supported APIs over hardcoded behavior.

Avoid direct database queries unless there is a clear need.

Do not modify WordPress core, plugin vendor files, or generated build artifacts.

## PHP Guidelines

Use modern PHP where compatible with the project requirements.

Prefer named functions or class methods over large anonymous callbacks when logic is non-trivial.

Use early returns to reduce nesting.

Keep template files focused on presentation.

Move reusable logic into clearly named helper functions, classes, or theme/plugin includes.

Always escape rendered values using the appropriate escaping function, such as `esc_html()`, `esc_attr()`, `esc_url()`, or `wp_kses_post()`.

Sanitize request data using functions appropriate to the expected data type.

Do not use translation functions such as `__()`, `_e()`, `esc_html__()`, or `esc_attr__()` unless explicitly requested.

## JavaScript Guidelines

Prefer modern Typescript over vanilla JS.

Prefer classes and OOP over functional programming.

Avoid unnecessary client-side JavaScript when the same result can be achieved with semantic HTML, CSS, or server-rendered WordPress markup.

Do not use jQuery unless the existing project already depends on it and there is no practical reason to replace it.

## CSS and Front-End Guidelines

Prefer WordPress utility classes. Fallback to Bootstrap 5 utility classes and components when they fit the design and markup requirements. Do not write custom class names unless absolutely necessary.

Write semantic, accessible HTML before adding CSS or JavaScript.

Use logical heading order.

Use buttons for actions and links for navigation.

Ensure interactive elements have visible focus states.

Do not rely on color alone to communicate meaning.

Prefer reduced-motion-friendly patterns.

Do not remove accessibility attributes unless they are incorrect or redundant.

## Accessibility Requirements

Accessibility is a first-class requirement.

When creating UI components:

-   Use semantic HTML whenever possible.
-   Ensure keyboard operability.
-   Ensure visible focus indicators.
-   Provide accessible names for controls.
-   Associate labels with form fields.
-   Use ARIA only when native HTML is insufficient.
-   Avoid ARIA patterns that are not fully implemented.
-   Ensure dynamic updates are announced when needed.
-   Respect `prefers-reduced-motion`.
-   Maintain sufficient color contrast.

For custom blocks, consider both the editor experience and the front-end experience.


## REST API and Data Handling

When creating custom REST API endpoints:

-   Register routes with `register_rest_route()`.
-   Include a proper `permission_callback`.
-   Sanitize and validate parameters.
-   Return `WP_REST_Response`, `WP_Error`, or structured arrays as appropriate.
-   Avoid exposing private data.
-   Check user capabilities for protected data or actions.
-   Use clear, predictable response shapes.

## File and Naming Conventions

Ensure names and conventions fit within the WordPress Coding Standard

Use descriptive file names.

Use kebab-case for folders and asset files.

Use clear, predictable names for PHP functions, classes, block names, handles, and scripts.

Prefix globally scoped PHP functions, script handles, style handles, and block names according to the project convention.

Do not invent a new naming convention if one already exists in the repository.

## Plugin File Organization

Place code in the correct layer of the plugin:

-   For plugins, PHP lives in root & /inc

PHP files should generally follow a layered approach:

-   inc/WP (WordPress related functionality, e.g. hooks, emails, admin screens, custom post types, taxonomies, etc.)
-   inc/Data (classes that handle data transformation)
-   inc/Http (classes that handle HTTP requests, e.g. REST API endpoints, external API calls)
-   inc/Service (classes that handle business logic, e.g. data processing, calculations, etc.)
-   inc/Jobs (classes that handle background jobs, e.g. cron jobs, queue processing, etc.)



Reusable PHP logic belongs in classes, helper files, or service files according to the existing project structure.

Do not place large blocks of HTML-generating PHP in `careers-plugin-jobs-api.php`.

Use `inc/class-plugin-loader.php` only for bootstrapping, registering hooks, loading files, plugin setup, enqueueing assets, and small configuration callbacks.

Before creating a new file, look for an existing similar pattern and follow that location and naming convention.

## Comments and Documentation

Add comments only when they clarify intent, constraints, or non-obvious behavior.

Do not add comments that merely repeat what the code does.

Use PHPDoc for reusable functions, classes, hooks, and filters when helpful.

For complex components or blocks, include brief usage notes or examples when appropriate.

## Security

Never expose secrets, API keys, access tokens, private URLs, or credentials.

Do not hardcode secrets.

Use environment variables, WordPress constants, or approved configuration mechanisms for sensitive values.

Validate permissions before performing privileged actions.

Escape output and sanitize input.

Be cautious with user-generated content, embeds, query parameters, cookies, and request headers.

## Performance

Avoid unnecessary queries, loops, network requests, and client-side scripts.

Cache expensive operations when appropriate.

Use transients or object caching carefully and invalidate caches when needed.

Load scripts and styles only where needed.

Prefer native browser capabilities and WordPress APIs before adding custom JavaScript.

## Pull Request Expectations

When suggesting changes, explain the reasoning briefly.

Call out accessibility, security, or migration implications when relevant.

If there are multiple reasonable approaches, recommend the best fit and briefly explain the tradeoff.

Do not make broad unrelated refactors unless requested.

## Response Style for Copilot

When answering questions or generating code:

-   Be direct and practical.
-   Prefer complete, working examples.
-   Match the existing project patterns.
-   Ask for clarification only when the ambiguity would materially change the implementation.
-   When making assumptions, state them briefly.
-   Suggest a better WordPress-native approach when the requested approach is fragile or outdated.
