# plg_system_convertformsghsvs

Joomla-System-Plugin. Führt zusätzliche Aktionen durch, wenn die Erweiterung ConvertForms verwendet wird.

Joomla system plugin. Performs additional actions when extension ConvertForms is used.

## Vorsicht! Dieses Plugin ist mehr oder weniger ein Draft.
- Bisher erst auf 2 individuellen Seiten minimal implementiert.
- Nur mit ConvertForms FREE getestet und verwendet, das nur 1 einzelne Emailbenachrichtigung senden kann.
- Das Plugin geht von folgendem, einfachen Standard-Szenario aus:
  - Ein Seitenbesucher füllt eine Art Kontaktformular aus.
	- Die Daten des Formulars werden an den Seitenbetreiber (bzw. eingetragenen Email-Empfänger) gesendet.
	- Weitere abweichende Szenarien, die mit ConvertForms möglich sind, wurden nicht getestet.

## Caution! This plugin is more or less a draft.
- Only minimally implemented on 2 individual pages so far.
- Only tested and used with ConvertForms FREE which can only send 1 single email notification.
- The plugin assumes the following simple standard scenario:
  - A site visitor fills out a kind of contact form.
	- The data of the form is sent to the page operator (or registered email recipient).
	- Other deviating scenarios that are possible with ConvertForms have not been tested.

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
