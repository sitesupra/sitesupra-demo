#!/bin/bash

set -e

# Compile less files outside the CMS folder
cd ${WORKSPACE}/release/src/webroot

find ./ -name "*.css.less" -exec php ./cms/lib/supra/combo/combo_pregenerate.php {} \;
find ./ -name "*.css.less" -delete

# Create temporary directory for webroot static files
#mkdir ${WORKSPACE}/release-static || echo "Folder already exists"
#cd ${WORKSPACE}/release-static

# Copy static files from project's webroot to release-static folder
#rsync -r --delete \
#  --copy-links \
#  --safe-links \
#  --exclude=.* \
#  --exclude=/components \
#  --exclude=/components-supra7 \
#  --exclude=*.php \
#  --exclude=/dev \
#  ${WORKSPACE}/release/src/webroot/ .

# Zip static files
#zip -r ${WORKSPACE}/${JOB_NAME%-release}.web-static.${project_version}.${BUILD_NUMBER}.zip *

# Remove temporary folder
#rm -rf ${WORKSPACE}/release-static
