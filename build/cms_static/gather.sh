#!/bin/bash

set -e

cd "$( dirname "$0" )"/../../

echo "" > src/webroot/cms/lib/pack.js

cat build/cms_static/js.txt | while read line ; do cat "src/webroot/$line" >> src/webroot/cms/lib/pack.js; done

echo "JS OK"

echo "" > src/webroot/cms/lib/pack.css

cat build/cms_static/css.txt | while read line ; do php src/webroot/cms/lib/supra/combo/combo_cli.php "$line" >> src/webroot/cms/lib/pack.css; done

echo "CSS OK"
