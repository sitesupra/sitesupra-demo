# Change current working directory

set -e

if [ -z $1 ]
then
	echo "Build profile must be defined"
	exit 1
fi

cd /var/www/vhosts/${JOB_NAME}/

# Sync contents
rsync -r \
  --links \
  --safe-links \
  --exclude=.git* \
  --exclude=/src/files/* \
  --exclude=/src/webroot/files/* \
  --exclude=/src/log/* \
  ${WORKSPACE}/ ./

# Replace INI file
cp src/conf/supra.$1.ini src/conf/supra.ini

if [ -f "tests/src/conf/supra.$1.ini" ]
then
	cp tests/src/conf/supra.$1.ini tests/src/conf/supra.ini
fi

# Old versioning logics
#cp src/conf/supra.ini src/conf/supra.ini.tmp
#sed "s/@build\.number@/${BUILD_NUMBER}/" src/conf/supra.ini.tmp > src/conf/supra.ini

# Version file
echo ${BUILD_ID} > ./VERSION
