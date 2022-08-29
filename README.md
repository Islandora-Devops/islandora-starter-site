# Islandora Starter Site

Base configuration and module suite for a starter site.

## Pre-requisites

1. Database installation/configuration for Drupal
2. Fedora installation

## Usage

1. Create a project based on this repository:

    ```bash
    composer create-project islandora/islandora-starter-site
    ```

    This should:
   1. Grab the code and all dependencies,
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

4. Run the migrations in the `islandora` migration group to populate the base
    taxonomies.

This should get you a starter Islandora site with:

* A basic node bundle to represent repository content
* A handful of media types presentable
* RDF and JSON-LD mappings for miscellaneous entities to support storage in
    Fedora, Triplestore indexing and client requests.
