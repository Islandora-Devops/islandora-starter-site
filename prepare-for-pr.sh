#!/bin/bash

# ActiveMQ
sed -i.bak 's/activemq/127.0.0.1/' ./config/sync/islandora.settings.yml

# Cantaloupe
sed -i.bak "s/iiif_server: 'https:\/\/islandora.traefik.me\/cantaloupe\/iiif\/2'/iiif_server: 'http:\/\/127.0.0.1:8080\/cantaloupe\/iiif\/2'/" ./config/sync/islandora_iiif.settings.yml
sed -i.bak "s/iiif_server: 'https:\/\/islandora.traefik.me\/cantaloupe\/iiif\/2'/iiif_server: 'http:\/\/127.0.0.1:8080\/cantaloupe\/iiif\/2'/" ./config/sync/openseadragon.settings.yml

# RSA Key
sed -i.bak "s/file_location: \/opt\/keys\/jwt\/private.key/file_location: \/opt\/islandora\/auth\/private.key/" ./config/sync/key.key.islandora_rsa_key.yml

# Matomo
sed -i.bak "s/url_http: 'http:\/\/islandora.traefik.me\/matomo\/'/url_http: 'http:\/\/localhost:8000\/matomo\/'/" ./config/sync/matomo.settings.yml
sed -i.bak "s/url_https: 'https:\/\/islandora.traefik.me\/matomo\/'/url_https: ''/" ./config/sync/matomo.settings.yml


# Solr
sed -i.bak "s/host: solr/host: 127.0.0.1/" ./config/sync/search_api.server.default_solr_server.yml

rm config/sync/*.bak

