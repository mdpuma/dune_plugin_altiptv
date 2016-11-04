#!/bin/bash

cd dune_plugin_altiptv_5.2.8
find -type f -exec chmod 644 {} \;
find -type d -exec chmod 755 {} \;
zip -r ../dune_plugin_altiptv_latest.zip * -x *.zip data/logo/*
