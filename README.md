# plg_system_convertformsghsvs

Joomla-System-Plugin. Führt zusätzliche Aktionen durch, wenn die Erweiterung ConvertForms verwendet wird.

Joomla system plugin. Performs additional actions when extension ConvertForms is used.

## Vorsicht! Dieses Plugin ist mehr oder weniger ein Draft.
- Bisher erst auf einer individuellen Seite minimal implementiert.
- Joomla 4 noch nicht getestet, auch, wenn es installierbar ist.

## Caution! This plugin is more or less a draft.
- Only minimally implemented on 1 individual site so far.
- Joomla 4 not yet tested, even if it is installable.

----------------------

# My personal build procedure (WSL 1, Debian, Win 10)

**Build procedure uses local repo fork of https://github.com/GHSVS-de/buildKramGhsvs**

- Prepare/adapt `./package.json`.
- `cd /mnt/z/git-kram/plg_system_convertformsghsvs`

## node/npm updates/installation
- `npm run updateCheck` or (faster) `npm outdated`
- `npm run update` (if needed) or (faster) `npm update --save-dev`
- `npm install` (if needed)

## Build installable ZIP package
- `node build.js`
- New, installable ZIP is in `./dist` afterwards.
- All packed files for this ZIP can be seen in `./package`. **But only if you disable deletion of this folder at the end of `build.js`**.s

#### For Joomla update server
- Use/See `dist/release.txt` as basic release text.
- Create new release with new tag.
- See extracts(!) of the update and changelog XML for update and changelog servers are in `./dist` as well. Check for necessary additions! Then copy/paste.
