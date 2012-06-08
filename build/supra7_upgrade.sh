#!/bin/bash

if [ -z "$1"]
then
	echo "Branch name must be defined"
	exit 1
fi

cd ${WORKSPACE}
git checkout master
git pull

git submodule foreach git checkout $1
git submodule foreach git pull

git commit -a -m "Updated submodules" || echo "Nothing to commit"
git push || echo "Nothing to push"
