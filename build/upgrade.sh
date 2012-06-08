# Upgrades database and runs upgrade scripts (or checks if anything must be run) 

if [ -z $1 ]
then
	echo "Upgrade type (check or force) must be specified"
	exit 1
fi

_TARGET_DIR=/var/www/vhosts/${JOB_NAME}/

if [ -z $TARGET_DIR ]
then
	_TARGET_DIR=$TARGET_DIR
fi

cd $_TARGET_DIR

if [ $1 = "force" ]
then
	# Update schema if is not up to date
	php5.3 bin/supra su:upgrade:all --force --list
	exit
fi

if [ $1 = "check" ]
then
	# Check if up to date
	php5.3 bin/supra su:upgrade:all --check --list
	exit
fi

echo "Upgrade type must be check or force"
exit 1
