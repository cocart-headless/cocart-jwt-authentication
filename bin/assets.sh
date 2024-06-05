#!/bin/sh

set -e
clear

# ASK INFO
echo "---------------------------------------------------------------------"
echo "                WordPress.org Plugin Assets Updater                  "
echo "---------------------------------------------------------------------"
read -p "Enter the ROOT PATH of the plugin you want to update: " ROOT_PATH

if [[ -d $ROOT_PATH ]]; then
	echo "---------------------------------------------------------------------"
	echo "New ROOT PATH has been set."
	cd $ROOT_PATH
elif [[ -f $ROOT_PATH ]]; then
	echo "---------------------------------------------------------------------"
	read -p "$ROOT_PATH is a file. Please enter a ROOT PATH: " ROOT_PATH
fi

echo "---------------------------------------------------------------------"
read -p "Enter the WordPress plugin slug: " SVN_REPO_SLUG
echo "---------------------------------------------------------------------"
clear

echo "Now processing..."

SVN_REPO_URL="https://plugins.svn.wordpress.org"

# Set WordPress.org Plugin URL
SVN_REPO=$SVN_REPO_URL"/"$SVN_REPO_SLUG"/assets/"

# Set temporary SVN folder for WordPress.
TEMP_SVN_REPO=${SVN_REPO_SLUG}"-svn"

# Delete old SVN cache just incase it was not cleaned before after the last release.
rm -Rf $ROOT_PATH$TEMP_SVN_REPO

# CHECKOUT SVN DIR IF NOT EXISTS
if [[ ! -d $TEMP_SVN_REPO ]];
then
	echo "Checking out WordPress.org plugin assets."
	svn checkout $SVN_REPO $TEMP_SVN_REPO || { echo "Unable to checkout repo."; exit 1; }
fi

read -p "Enter your GitHub username: " GITHUB_USER
clear

read -p "Now enter the GitHub repository slug: " GITHUB_REPO_NAME
clear

# Set temporary folder for GitHub.
TEMP_GITHUB_REPO=${GITHUB_REPO_NAME}"-git"

# Delete old GitHub cache just incase it was not cleaned before after the last release.
rm -Rf $ROOT_PATH$TEMP_GITHUB_REPO

echo "---------------------------------------------------------------------"
echo "Is the line secure?"
echo "---------------------------------------------------------------------"
echo " - y for SSH"
echo " - n for HTTPS"
read -p "" SECURE_LINE

# Set GitHub Repository URL
if [[ ${SECURE_LINE} = "y" ]]
then
	GIT_REPO="git@github.com:"${GITHUB_USER}"/"${GITHUB_REPO_NAME}".git"
else
	GIT_REPO="https://github.com/"${GITHUB_USER}"/"${GITHUB_REPO_NAME}".git"
fi;

clear

# Clone Git repository
echo "Cloning GIT repository from GitHub"
git clone --progress $GIT_REPO $TEMP_GITHUB_REPO || { echo "Unable to clone repo."; exit 1; }

# Move into the temporary GitHub folder
cd $ROOT_PATH$TEMP_GITHUB_REPO
clear

# LIST BRANCHES
echo "---------------------------------------------------------------------"
read -p "Which remote are we fetching? Default is 'origin'" ORIGIN
echo "---------------------------------------------------------------------"

# IF REMOTE WAS LEFT EMPTY THEN FETCH ORIGIN BY DEFAULT
if [[ -z ${ORIGIN} ]]
then
	git fetch origin

	# Set ORIGIN as origin if left blank
	ORIGIN=origin
else
	git fetch ${ORIGIN}
fi;

echo "Which branch contains the updated asset files?"
git branch -r || { echo "Unable to list branches."; exit 1; }
echo ""
read -p ${ORIGIN}"/"${BRANCH}

# Switch Branch
echo "Switching to branch"
git checkout ${BRANCH} || { echo "Unable to checkout branch."; exit 1; }

# Remove unwanted files and folders
echo "Removing unwanted files..."
rm -Rf .git
rm -Rf .github
rm -Rf tests
rm -Rf apigen
rm -Rf node_modules
rm -f .gitattributes
rm -f .gitignore
rm -f .gitmodules
rm -f .jscrsrc
rm -f .jshintrc
rm -f phpunit.xml.dist
rm -f .editorconfig
rm -f *.lock
rm -f *.rb
rm -f *.js
rm -f *.json
rm -f *.xml
rm -f *.md
rm -f *.yml
rm -f *.neon
rm -f *.jpg
rm -f *.sh
rm -f *.php
rm -f *.txt

read -p "Enter the directory from your GitHub repository where the assets are stored: " ASSETS_FOLDER

# Copy GitHub asset files to SVN temporary folder.
cp -prv -f -R ${ASSETS_FOLDER}"/." "../"${TEMP_SVN_REPO}"/"

# Move into the SVN temporary folder
cd "../"${TEMP_SVN_REPO}

# Update SVN
echo "Updating SVN"
svn update || { echo "Unable to update SVN."; exit 1; }

# Add all JPG and PNG files.
svn add --force *".png"
svn add --force *".jpg"

# SVN Commit
clear
echo "Getting SVN Status."
svn status --show-updates

# Deploy Update
echo ""
echo "Committing assets to WordPress.org..."
svn commit -m "Updated assets for "${SVN_REPO_SLUG}"" || {
	echo "Unable to commit. Loading last log.";
	svn log -l 1
	exit 1;
}

read -p "Asset files were updated. Press [ENTER] to clean up."
clear

# Remove the temporary directories
echo "Cleaning Up..."
cd "../"
rm -Rf $ROOT_PATH$TEMP_GITHUB_REPO
rm -Rf $ROOT_PATH$TEMP_SVN_REPO

# Done
echo "Update Done."
echo ""
read -p "Press [ENTER] to close program."

clear
