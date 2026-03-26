# Drupal 11 Project: bwtf-drupal11-site

![Drupal Logo](https://www.drupal.org/files/Wordmark_blue_RGB.png)

## Overview

This repository is a Drupal 11 project scaffolded for modern development, supporting custom modules, themes, recipes, and multisite configurations. It is based on the official `drupal/recommended-project` template, with a relocated document root (`web/`).

Drupal is a flexible, open-source content management platform for building everything from personal blogs to enterprise applications. For more information, visit [Drupal.org](https://www.drupal.org/).

---

## Directory Structure

- **composer.json**: Project dependencies and Composer configuration.
- **web/**: Drupal web root (public document root)
  - **core/**: Drupal core files
  - **modules/**: Site-wide modules
    - **contrib/**: Contributed modules from Drupal.org
    - **custom/**: Custom modules for this project
  - **themes/**: Site-wide themes
    - **custom/**: Custom themes
  - **profiles/**: Installation profiles
  - **libraries/**: External libraries
  - **sites/**: Site-specific configuration (multisite support)
    - **default/**: Default site settings, files, and services
      - **settings.php**: Main site configuration
      - **settings.ddev.php**: DDEV-specific overrides (if using DDEV)
      - **files/**: Public files directory
  - **recipes/**: Automation for module/theme installation and configuration

---

## Installation

### Prerequisites
- PHP 8.3.0 or greater
- Composer
- Database (e.g., MySQL, MariaDB, PostgreSQL)

### Quick Start
1. **Clone this repository:**
   ```sh
   git clone <repo-url> && cd bwtf-drupal11-site
   ```
2. **Install dependencies:**
   ```sh
   composer install
   ```
3. **Set up your web server:**
   - Point your web server's document root to the `web/` directory.
   - Configure your database and update `web/sites/default/settings.php` as needed.
4. **Install Drupal:**
   - Visit your site in a browser and follow the installation wizard, or use Drush:
     ```sh
     vendor/bin/drush site:install
     ```

For advanced installation, multisite, or recipe usage, see `web/core/INSTALL.txt` and `web/core/USAGE.txt`.

---

## Usage & Customization

- **Modules:** Place contributed modules in `web/modules/contrib/` and custom modules in `web/modules/custom/`.
- **Themes:** Place custom themes in `web/themes/custom/`.
- **Recipes:** Use the `recipes/` directory for automation of module/theme installation and configuration.
- **Multisite:** Add additional site folders under `web/sites/` as needed. See `web/sites/README.txt` and `web/core/INSTALL.txt` for details.
- **Configuration:** Site-specific settings are in `web/sites/default/settings.php`. For local overrides, use `settings.local.php`.

---

## Updating Drupal

- Use Composer to update core and contributed projects:
  ```sh
  composer update drupal/core-recommended drupal/core-project-message drupal/core-composer-scaffold
  ```
- See `web/core/UPDATE.txt` for full update instructions.

---

## Contributing

- Custom code should be placed in `web/modules/custom/` or `web/themes/custom/`.
- Follow Drupal coding standards and best practices.
- For Drupal core or contributed module issues, use the [Drupal.org issue queue](https://www.drupal.org/project/issues/drupal).

---

## Support & Documentation

- [Drupal Documentation](https://www.drupal.org/documentation)
- [Drupal Community](https://www.drupal.org/community)
- [Support](https://www.drupal.org/support)

---

## License

This project is licensed under the GNU General Public License v2.0 or later. See [LICENSE.txt](LICENSE.txt) and [web/core/LICENSE.txt](web/core/LICENSE.txt) for details.

---

## Security

- For security advisories, see [Drupal Security](https://www.drupal.org/security).
- To report a security issue, see the [Drupal Security Team page](https://www.drupal.org/drupal-security-team).

---

## Acknowledgements

- Built with [Drupal 11](https://www.drupal.org/project/drupal).
- Project template: [drupal/recommended-project](https://github.com/drupal/recommended-project)
