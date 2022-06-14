# plg_system_convertformsghsvs

# This plugin is a non-functioning draft and will break your Joomla if you try to install it.

# This plugin is a non-functioning draft and will break your Joomla if you try to install it.

Joomla system plugin. Performs additional actions when users are submitting a ConvertForms form.

----------------------

# My personal build procedure (WSL 1, Debian, Win 10)

**Build procedure uses local repo fork of https://github.com/GHSVS-de/buildKramGhsvs**

- Prepare/adapt `./package.json`.
- `cd /mnt/z/git-kram/plg_system_convertformsghsvs`

## node/npm updates/installation
- `npm run updateCheck` or (faster) `npm outdated`
- `npm run update` (if needed) or (faster) `npm update --save-dev`
- `npm install` (if needed)

## PHP Codestyle
If you think it's worth it.
- `cd /mnt/z/git-kram/php-cs-fixer-ghsvs`
- `npm run plg_system_convertformsghsvsDry` (= dry test run).
- `npm run plg_system_convertformsghsvs` (= cleans code).
- `cd /mnt/z/git-kram/plg_system_convertformsghsvs` (back to this repo).

## Build installable ZIP package
- `node build.js`
- New, installable ZIP is in `./dist` afterwards.
- All packed files for this ZIP can be seen in `./package`. **But only if you disable deletion of this folder at the end of `build.js`**.s

#### For Joomla update server
- Use/See `dist/release.txt` as basic release text.
- Create new release with new tag.
- See extracts(!) of the update and changelog XML for update and changelog servers are in `./dist` as well. Check for necessary additions! Then copy/paste.
