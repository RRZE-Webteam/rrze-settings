'use strict';

var fs = require('fs');
var path = require('path');

function readJson(filePath) {
    return JSON.parse(fs.readFileSync(filePath, 'utf8'));
}

function getString(value, fallback) {
    return typeof value === 'string' && value.trim() !== '' ? value.trim() : fallback;
}

function replaceHeaderValue(content, key, value) {
    var expression = new RegExp('"' + key + ': [^\\n]*\\\\n"', '');
    var replacement = '"' + key + ': ' + value.replace(/"/g, '\\"') + '\\n"';

    return content.replace(expression, replacement);
}

function main() {
    var root = process.cwd();
    var packageJson = readJson(path.join(root, 'package.json'));
    var author = packageJson.author && typeof packageJson.author === 'object' ? packageJson.author : {};
    var repository = packageJson.repository && typeof packageJson.repository === 'object' ? packageJson.repository : {};
    var supports = packageJson.supports && typeof packageJson.supports === 'object' ? packageJson.supports : {};
    var authorName = getString(author.name, 'RRZE Webteam');
    var email = getString(supports.email, 'webmaster@fau.de');
    var title = getString(packageJson.title, getString(packageJson.name, 'RRZE Plugin'));
    var version = getString(packageJson.version, '0.0.0');
    var issuesUrl = getString(repository.issues, '');
    var potPath = path.join(root, 'languages', getString(packageJson.textDomain, 'rrze-settings') + '.pot');
    var content = fs.readFileSync(potPath, 'utf8');

    content = replaceHeaderValue(content, 'Project-Id-Version', title + ' ' + version);
    content = replaceHeaderValue(content, 'Report-Msgid-Bugs-To', issuesUrl);
    content = replaceHeaderValue(content, 'Last-Translator', authorName + ' <' + email + '>');
    content = replaceHeaderValue(content, 'Language-Team', authorName + ' <' + email + '>');
    content = replaceHeaderValue(content, 'PO-Revision-Date', '');

    fs.writeFileSync(potPath, content, 'utf8');
}

main();
