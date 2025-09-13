# Graduates

Advanced WordPress plugin for managing graduates, compliant with PSR-12 and WordPress Coding Standards. Modular, secure, fully translatable, and REST API ready.

## Requirements

- PHP 8.1 or newer
- WordPress 6.0 or newer

## Installation

You can use the plugin with or without Composer. The plugin contains a fallback PSR-4 autoloader, so Composer is optional.

### Option A: Without Composer (simple)
1. Download the latest version of the plugin
2. Upload the `graduates` folder to `/wp-content/plugins/`
3. Activate the plugin in the WordPress admin panel
4. Go to the "Graduates" section in the sidebar menu

### Option B: With Composer (recommended for development)
1. Change directory to the plugin
   - `cd wp-content/plugins/graduates`
2. Install dependencies and generate autoload
   - Locally: `composer install`
   - Or via Docker: `docker run --rm -v "$PWD":/app -w /app composer:2 install`
3. Activate the plugin in the WordPress admin panel

If you later add new classes under `src/`, run `composer dump-autoload -o` (or `composer install`) to optimize the autoloader.

## Autoloading (PSR-4)

The plugin follows PSR-4. Namespace `Graduates\\` maps to the `src/` directory.

- Composer mapping (in `composer.json`):

  ```json
  {
    "autoload": {
      "psr-4": {
        "Graduates\\": "src/"
      }
    }
  }
  ```

- If `vendor/autoload.php` is present, it will be loaded automatically.
- If not, a built-in fallback autoloader will map `Graduates\\Foo\\Bar` to `src/Foo/Bar.php`.

## Activation / Deactivation hooks

On activation, the plugin:
- Adds role capabilities for the custom post type (administrator/editor)
- Registers the post type for permalink rules
- Flushes rewrite rules

On deactivation, the plugin:
- Removes previously added capabilities from common roles
- Flushes rewrite rules

All of the above happen exactly once per activation/deactivation.

## Composer scripts (post-install-cmd / post-update-cmd)

Composer supports lifecycle scripts. For a WordPress plugin these can be used to:

- Optimize autoload (`composer dump-autoload -o`)
- Run linters/tests (e.g., `phpcs`, `phpstan`)
- Automatically copy translation files to a safe location (`@copy-translations`)

Example (implemented in composer.json):

```json
{
  "scripts": {
    "post-install-cmd": [
      "composer dump-autoload -o",
      "@copy-translations"
    ],
    "post-update-cmd": [
      "composer dump-autoload -o",
      "@copy-translations"
    ],
    "copy-translations": [
      "sh -c \"if [ -f languages/graduates-pl_PL.mo ]; then cp languages/graduates-pl_PL.mo ../../../../wp-content/languages/plugins/ && echo 'Translations copied.'; else echo 'No translations to copy.'; fi\""
    ]
  }
}
```

### About the `@copy-translations` script

This script automatically copies `.mo` translation files from the plugin's `languages/` directory to WordPress's safe location (`wp-content/languages/plugins/`) during `composer install` or `composer update`. This ensures translations survive plugin updates.

### Manual compilation (if needed)

If you're not using Composer or need to compile translations manually:

```bash
# Compile .po to .mo (requires gettext)
msgfmt languages/graduates-pl_PL.po -o languages/graduates-pl_PL.mo

# Copy to WordPress languages directory
mkdir -p ../../../../wp-content/languages/plugins/
cp languages/graduates-pl_PL.mo ../../../../wp-content/languages/plugins/
```

Note: Tasks like activating the plugin or flushing rewrites require a running WordPress environment (typically via WP-CLI) and are best executed manually or in your CI/CD pipeline.

## Features

- Custom post type: "Graduates"
- Additional meta fields:
  - First name (meta field)
  - Last name (meta field)
- Graduate description (post content)
- Featured image support (photo)
- Admin panel columns:
  - Full name (post title)
  - 50-character description excerpt
  - Photo thumbnail
- Full REST API integration
- Ready for localization

## REST API Endpoints

The plugin exposes a REST API for managing graduates.

### List graduates

**Endpoint:**  
`GET /wp-json/graduates/v1/graduates`

**Parameters:**

| Param      | Type    | Default | Description                                   |
|------------|---------|---------|-----------------------------------------------|
| `page`     | integer | 1       | Current page of the collection                |
| `per_page` | integer | 10      | Maximum number of items per page (1-100)      |
| `search`   | string  |         | Search graduates by string                    |
| `orderby`  | string  | title   | Sort by: `title`, `date`, `id`                |
| `order`    | string  | asc     | Sort direction: `asc`, `desc`                 |

**Example request:**
```
GET /wp-json/graduates/v1/graduates?per_page=5&search=Smith&orderby=date&order=desc
```

**Response fields:**

- `id`: Graduate post ID
- `title.rendered`: Full name (post title)
- `first_name`, `last_name`: Meta fields
- `content.rendered`: Description
- `excerpt.rendered`: Short excerpt
- `date`, `date_gmt`, `modified`, `modified_gmt`: Timestamps
- `status`: Post status
- `featured_media`: Featured image ID
- `link`: Permalink
- `_links`: REST resource links

**Pagination headers:**

- `X-WP-Total`: Total graduates found
- `X-WP-TotalPages`: Total pages

**Note:**  
All endpoints are public (read-only). For write access, extend the API and add permission checks.

## Usage

### Adding a new graduate

1. Go to "Graduates" > "Add New"
2. Enter first and last name in dedicated fields
3. Add description in the post content
4. Add a photo as the featured image
5. Save changes

## License

GPL-2.0+

## Author

Jakub Grzesiak <jakub.grzesiak@jg-webtech.pl>
