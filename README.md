![Islandora logo](https://cloud.githubusercontent.com/assets/2371345/25624809/f95b0972-2f30-11e7-8992-a8f135402cdc.png)

# Islandora Starter Site

[![Minimum PHP Version](https://img.shields.io/badge/php-%3E%3D%207.4-8892BF.svg?style=flat-square)](https://php.net/)
[![Contribution Guidelines](http://img.shields.io/badge/CONTRIBUTING-Guidelines-blue.svg)](./CONTRIBUTING.md)
[![LICENSE](https://img.shields.io/badge/license-GPLv2-blue.svg?style=flat-square)](./LICENSE)

Base configuration and module suite for a starter site.

Your mileage may vary. Depending on your particular use case, some Islandora
prerequisites may not be needed; for example, if you do not need Fedora,
Cantaloupe, or otherwise you can skip their installation and configuration. Note
that this may leave behind stray configurations that should be removed. This
is left as an exercise for the site admin, as documenting such advanced usage is
beyond the scope of this document.

## Prerequisites

1. PHP and [Composer](https://getcomposer.org/) installed
2. Database installation/configuration [for Drupal](https://www.drupal.org/docs/system-requirements/database-server-requirements)
   * Drivers for MySQL/MariaDB/Percona (`mysql`), PostgreSQL (`pgsql`), and
SQLite (`sqlite`) are installed. Using other (contrib) drivers would require
additional installation/configuration steps outside the scope of this document.
3. [Fedora Commons (FCRepo)](https://github.com/fcrepo/fcrepo) installation
   1. [Syn](https://github.com/Islandora/Syn/) installed and configured with a
key.
4. Triplestore installation
5. Cantaloupe installation
   1. `http://127.0.0.1:8080/cantaloupe/iiif/2` is expected to be resolvable,
and to accept full URLs as resource IDs.
6. ActiveMQ/Alpaca/Crayfish/Crayfits installation
   1. ActiveMQ expected to be listening for STOMP messages at `tcp://127.0.0.1:61613`
   2. Queues (and underlying (micro)services) configured appropriately:

        | Queue Name                                | Destination                                                |
        |-------------------------------------------|------------------------------------------------------------|
        | `islandora-connector-homarus`             | Homarus (Crayfish ffmpeg transcoding microservice)         |
        | `islandora-indexing-fcrepo-delete`        | FCRepo indexer                                             |
        | `islandora-indexing-triplestore-delete`   | Triplestore indexer                                        |
        | `islandora-connector-houdini`             | Houdini (Crayfish imagemagick transformation microservice) |
        | `islandora-connector-fits`                | Crayfits                                                   |
        | `islandora-connector-ocr`                 | Hypercube (Crayfish OCR microservice)                      |
        | `islandora-indexing-fcrepo-file-external` | FCRepo indexer                                             |
        | `islandora-indexing-fcrepo-media`         | FCRepo indexer                                             |
        | `islandora-indexing-triplestore-index`    | Triplestore indexer                                        |
        | `islandora-indexing-fcrepo-content`       | FCRepo indexer                                             |
7. A [Drupal-compatible web server](https://www.drupal.org/docs/system-requirements/web-server-requirements)

## Usage

1. Create a project based on this repository:

    ```bash
    composer create-project islandora/islandora-starter-site
    ```

    This should:
   1. Grab the code and all PHP dependencies,
   2. Scaffold the site, and have the `default` site's `settings.php` point at
      our included configuration for the next step.

2. Configure Flysystem's `fedora` scheme in the site's `settings.php`:

    ```php
    $settings['flysystem'] = [
      'fedora' => [
        'driver' => 'fedora',
        'config' => [
          'root' => 'http://127.0.0.1:8080/fcrepo/rest/',
        ],
      ],
    ];
    ```

    Changing `http://127.0.0.1:8080` to point at your Fedora installation.

3. Install the site:

    ```bash
    composer exec -- drush site:install --existing-config
    ```

    After this step, you should configure your web server to serve `web/`
directory as its document root.

4. Add (or otherwise create) a user to the `fedoraadmin` role; for example,
giving the default `admin` user the role:

    ```bash
    composer exec -- drush user:role:add fedoraadmin admin
    ```

5. Make the Syn/JWT keys available to our configuration either by (or by some combination of):
   1. Symlinking the private key to `/opt/islandora/auth/private.key`; or,
   2. Configuring the location to the private key on the site at
`http://<your-web-server>/admin/config/system/keys/manage/islandora_rsa_key`
(where `<your-web-server>` accesses your web server which is configured to serve
from the starter site's `web/` directory).

6. Run the migrations in the `islandora` migration group to populate the base
taxonomies, specifying the `--userid` targeting the user with the `fedoraadmin`
role:

    ```bash
    composer exec -- drush migrate:import --userid=1 islandora_tags,islandora_defaults_tags
    ```

This should get you a starter Islandora site with:

* A basic node bundle to represent repository content
* A handful of media types presentable
* RDF and JSON-LD mappings for miscellaneous entities to support storage in
Fedora, Triplestore indexing and client requests.

### Post-installation cleanup

1. Uninstall the database driver modules you are not using; for example, if
you are using `mysql` to use a MySQL-compatible database, you should be clear to
uninstall the `pgsql` (PostgreSQL) and `sqlite` (SQLite) modules:

     ```bash
     composer exec -- drush pm:uninstall pgsql sqlite
     ```

## Documentation

Further documentation for this ecosystem is available on the [Islandora documentation site](https://islandora.github.io/documentation/).

## Troubleshooting/Issues

Having problems or solved a problem? Check out the Islandora google groups for a solution.

* [Islandora Group](https://groups.google.com/forum/?hl=en&fromgroups#!forum/islandora)
* [Islandora Dev Group](https://groups.google.com/forum/?hl=en&fromgroups#!forum/islandora-dev)

## Development

If you would like to contribute, please get involved by attending our weekly [Tech Call](https://github.com/Islandora/islandora-community/wiki/Weekly-Open-Tech-Call). We love to hear from you!

If you would like to contribute code to the project, you need to be covered by an Islandora Foundation [Contributor License Agreement](https://github.com/Islandora/islandora-community/wiki/Onboarding-Checklist#contributor-license-agreements) or [Corporate Contributor License Agreement](https://github.com/Islandora/islandora-community/wiki/Onboarding-Checklist#contributor-license-agreements). Please see the [Contributor License Agreements](https://github.com/Islandora/islandora-community/wiki/Contributor-License-Agreements) page on the islandora-community wiki for more information.

We recommend using the [islandora-playbook](https://github.com/Islandora-Devops/islandora-playbook) to get started.

## License

[GPLv2](http://www.gnu.org/licenses/gpl-2.0.txt)
