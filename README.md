# Islandora Starter Site

Base configuration and module suite for a starter site.

## Pre-requisites

1. Database installation/configuration for Drupal to use
2. Fedora installation
   1. [Syn](https://github.com/Islandora/Syn/) installed and configured with a
key.
3. Cantaloupe installation
   1. `http://127.0.0.1:8080/cantaloupe/iiif/2` is expected to be resolvable.
4. ActiveMQ/Alpaca/Crayfish/Crayfits installation
   1. ActiveMQ expected to be listening for STOMP messages at `tcp://127.0.0.1:61613`
   2. Queues (and underlying (micro)services) configured appropriately:

        | Queue Name | Destination                                        |
        |----------------------------------------------------| --- |
        | `islandora-connector-homarus` | Homarus (Crayfish ffmpeg transcoding microservice) |
        | `islandora-indexing-fcrepo-delete` | FCRepo indexer                                     |
        | `islandora-indexing-triplestore-delete` | Triplestore indexer                                |
        | `islandora-connector-houdini` | Houdini (Crayfish imagemagick transformation microservice) |
        | `islandora-connector-fits` | Crayfits |
        | `islandora-connector-ocr` | Hypercube (Crayfish OCR microservice) |
        | `islandora-indexing-fcrepo-file-external` | FCRepo indexer |
        | `islandora-indexing-fcrepo-media` | FCrepo indexer |
        | `islandora-indexing-triplestore-index` | Triplestore indexer |
        | `islandora-indexing-fcrepo-content` | FCRepo indexer |


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

4. Add (or otherwise create) a user to the `fedoraadmin` role; for example,
giving the default `admin` user the role:

    ```bash
    composer exec -- drush user:role:add fedoraadmin admin
    ```

5. Make the Syn/JWT keys available to our configuration either by (or by some combination of):
   1. Symlinking the private key to `/opt/islandora/auth/private.key`; or,
   2. Configuring the location to the private key on the site at `admin/config/system/keys/manage/islandora_rsa_key`

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
