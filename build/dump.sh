#!/bin/bash

set -e

cd ${WORKSPACE}
mysqldump -h db.vig -u dev --password=dev --default-character-set=utf8 ${JOB_NAME/%-release}_uat > ${JOB_NAME/%-release}.dump.${project_version}.${BUILD_NUMBER}.sql
zip ${JOB_NAME/%-release}.dump.${project_version}.${BUILD_NUMBER}.zip ${JOB_NAME/%-release}.dump.${project_version}.${BUILD_NUMBER}.sql
rm ${JOB_NAME/%-release}.dump.${project_version}.${BUILD_NUMBER}.sql
