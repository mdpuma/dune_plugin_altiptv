#!/bin/sh
directory="/persistfs/plugins/altiptv/bin"
directory_f="/flashdata/plugins/altiptv/bin"
 if [ -d $directory ]; then
		 cp -a /persistfs/plugins/altiptv/data/ /persistfs/plugins_data/altiptv/altiptv_data/
 elif [ -d $directory_f ]; then
		 cp -a /flashdata/plugins/altiptv/data/ /flashdata/plugins_data/altiptv/altiptv_data/
 fi
