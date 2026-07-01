# Drupal 11 Project: bwtf-drupal11-site

![Drupal Logo](https://www.drupal.org/files/Wordmark_blue_RGB.png)

## Overview

This repository contains the Drupal 11 project for **Ben's World of Transformers (BWTF)**, scaffolded for modern development and supporting custom modules, themes, recipes, and configuration management. The document root is relocated to the `web/` directory.

Drupal 11 is a flexible, open-source content management platform for building highly-customized web applications. For more information, visit [Drupal.org](https://www.drupal.org/).

---

## Directory Structure

- [composer.json](file:///Users/thelabadm/Documents/development/bwtf-drupal11-site/composer.json): Project dependencies and Composer configuration.
- **web/**: Drupal web root (public document root)
  - **core/**: Drupal core files
  - **modules/**: Site-wide modules
    - **contrib/**: Contributed modules from Drupal.org
    - **custom/**: Custom modules for this project
  - **themes/**: Site-wide themes
    - **custom/**: Custom themes (e.g., the main `bwtf` theme)
  - **profiles/**: Installation profiles
  - **libraries/**: External libraries
  - **sites/**: Site-specific configuration (multisite support)
    - **default/**: Default site settings, files, and services
      - [settings.php](file:///Users/thelabadm/Documents/development/bwtf-drupal11-site/web/sites/default/settings.php): Main site configuration
      - [settings.ddev.php](file:///Users/thelabadm/Documents/development/bwtf-drupal11-site/web/sites/default/settings.ddev.php): DDEV-specific overrides
      - [settings.local.php](file:///Users/thelabadm/Documents/development/bwtf-drupal11-site/web/sites/default/settings.local.php): Local development overrides
      - **files/**: Public files directory
- **config/**: Configuration management
  - **sync/**: Exported site configuration YAML files
- **recipes/**: Automation for module/theme installation and configuration

---

## Installation & Local Development

We use [DDEV](https://ddev.readthedocs.io/) for local development to ensure a consistent environment (PHP 8.3, MariaDB 11.8, Nginx) across all setups.

### Prerequisites

Ensure you have the following installed on your machine:
1. **Docker Engine / Provider** (e.g., [Docker Desktop](https://www.docker.com/products/docker-desktop/), [OrbStack](https://orbstack.dev/), or [Colima](https://github.com/abiosoft/colima))
2. **DDEV CLI** (refer to the [DDEV Installation Guide](https://ddev.readthedocs.io/en/stable/users/install/ddev-installation/))

### Quick Start with DDEV (Recommended)

1. **Clone this repository:**
   ```sh
   git clone <repo-url> && cd bwtf-drupal11-site
   ```
2. **Start the DDEV containers:**
   ```sh
   ddev start
   ```
3. **Install Composer dependencies:**
   ```sh
   ddev composer install
   ```
4. **Initialize Drupal database:**
   * **Option A: Import an existing database backup** (e.g., from production/staging):
     ```sh
     ddev import-db --file=path/to/backup.sql
     ddev drush config:import -y
     ddev drush cache:rebuild
     ```
   * **Option B: Run a fresh install**:
     ```sh
     ddev drush site:install --account-name=admin --account-pass=admin -y
     ```
5. **Launch the site in your browser:**
   ```sh
   ddev launch
   ```
   *The local URL is usually:* [https://bwtf-drupal11-site.ddev.site](https://bwtf-drupal11-site.ddev.site)

> [!TIP]
> Use `ddev describe` at any time to see the URLs, database credentials, mailpit UI, and other details of your running environment.

---

### Non-DDEV / Manual Installation

If you prefer to configure your own web server and database:
1. **Prerequisites:** PHP 8.3+, Composer 2.x, and MySQL/MariaDB/PostgreSQL.
2. **Clone & Install Dependencies:**
   ```sh
   git clone <repo-url> && cd bwtf-drupal11-site
   composer install
   ```
3. **Configure Web Server:** Point the document root to the `web/` directory.
4. **Database Configuration:**
   - Copy `web/sites/default/default.settings.php` to `web/sites/default/settings.php` (if not already present).
   - Configure your local database credentials by adding them to `web/sites/default/settings.local.php`.
5. **Install via Drush:**
   ```sh
   vendor/bin/drush site:install -y
   ```

---

## Supporting & Maintaining the Site

Most support tasks are run using **Drush** (Drupal Shell) inside the DDEV container.

### Common Developer Commands

| Action | DDEV Command | Non-DDEV Command | Description |
| :--- | :--- | :--- | :--- |
| **Rebuild Cache** | `ddev drush cr` | `vendor/bin/drush cr` | Clears and rebuilds all cache tables. |
| **Import Configuration** | `ddev drush cim` | `vendor/bin/drush cim` | Imports YAML configuration files from `config/sync` into the database. |
| **Export Configuration** | `ddev drush cex` | `vendor/bin/drush cex` | Exports active database settings to `config/sync` configuration files. |
| **Run DB Updates** | `ddev drush updb` | `vendor/bin/drush updb` | Runs pending database update hooks. |
| **View Status** | `ddev drush status` | `vendor/bin/drush status` | Displays information about the current Drupal installation. |

> [!IMPORTANT]
> Always run `ddev drush cex` and commit any configuration changes to git before pushing them to the staging environment.

### Managing Drupal Modules & Themes

To maintain security and stability, all third-party modules and libraries must be managed via Composer.

* **Install a contributed module:**
  ```sh
  ddev composer require drupal/module_name
  ddev drush pm:enable module_name -y
  ```
* **Uninstall and remove a module:**
  ```sh
  ddev drush pm:uninstall module_name -y
  ddev composer remove drupal/module_name
  ```
* **Apply module updates:**
  ```sh
  ddev composer update drupal/module_name --with-dependencies
  ddev drush updb -y
  ```

---

## Custom Theme Development

The custom theme is located at `web/themes/custom/bwtf`.

### Styling & CSS Compilation
The theme styling is authored in Sass (`.scss`) and compiled into CSS files (`web/themes/custom/bwtf/assets/css/bwtf_v2.css`).
* **Sass files** are located in: `web/themes/custom/bwtf/assets/css/`
* Because there is no package manager (npm/yarn) configured in the root or theme directories, compile files using a local Sass compiler (such as a VSCode extension like *Live Sass Compiler* or command line `sass` utility).
* Compile `web/themes/custom/bwtf/assets/css/bwtf_v2.scss` into `bwtf_v2.css`.

---

## Deployment & Hosting

### Automated Deployment

We use a GitHub Actions workflow defined in [.github/workflows/deploy.yml](file:///Users/thelabadm/Documents/development/bwtf-drupal11-site/.github/workflows/deploy.yml) to automatically deploy code pushed to the `main` branch.

**Deployment Target:**
* **Host:** NameHero Staging Server
* **Staging URL:** [https://stage.bwtf.com](https://stage.bwtf.com)

**What the Deployment Workflow Does:**
1. Packages the repository files (excluding local settings, DDEV configs, cache, and vendor directories).
2. Uploads the code archive to `/home/bwtfcom/stage.bwtf.com/` via SSH/SFTP.
3. Extracts the archive and installs production Composer dependencies using the server's PHP 8.3 CLI binary.
4. Executes pending database updates (`drush updb`), imports the configuration (`drush cim`), and rebuilds caches.

### Manual Staging Support & Troubleshooting

Staging environment runs on a NameHero cPanel-based host. To facilitate running CLI tools directly on the server:
- A helper script [post_deploy_fix.sh](file:///Users/thelabadm/Documents/development/bwtf-drupal11-site/post_deploy_fix.sh) is provided to configure the path, PHP 8.3 wrapper, and Drush command alias.
- To execute Drush commands directly on the server:
  1. SSH into the NameHero staging account.
  2. Run:
     ```sh
     drush <command>
     ```

---

## License

This project is licensed under the GNU General Public License v2.0 or later. See [LICENSE.txt](file:///Users/thelabadm/Documents/development/bwtf-drupal11-site/LICENSE.txt) and `web/core/LICENSE.txt` for details.

---

## Security

- For security advisories, see [Drupal Security](https://www.drupal.org/security).
- To report a security issue, see the [Drupal Security Team page](https://www.drupal.org/drupal-security-team).

---

## Acknowledgements

- Built with [Drupal 11](https://www.drupal.org/project/drupal).
- Project template: [drupal/recommended-project](https://github.com/drupal/recommended-project)
