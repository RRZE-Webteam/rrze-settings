#!/usr/bin/env node

'use strict';

var fs = require('fs');
var path = require('path');
var sass = require('sass');
var rtlcss = require('rtlcss');

var entries = [
    {
        source: 'src/sass/rrze-settings-admin.scss',
        target: 'build/admin/admin.css'
    },
    {
        source: 'src/sass/rrze-settings-advanced-placeholder.scss',
        target: 'build/advanced/placeholder.css'
    },
    {
        source: 'src/sass/rrze-settings-media-columns.scss',
        target: 'build/media/columns.css'
    },
    {
        source: 'src/sass/rrze-settings-media-svg.scss',
        target: 'build/media/svg.css'
    },
    {
        source: 'src/sass/rrze-settings-media-svg-edit-post.scss',
        target: 'build/media/svg-edit-post.css'
    },
    {
        source: 'src/sass/rrze-settings-taxonomies-attachment-media-filters.scss',
        target: 'build/taxonomies/attachment-media-filters.css'
    }
];

function ensureDir(dirPath) {
    if (!fs.existsSync(dirPath)) {
        fs.mkdirSync(dirPath, { recursive: true });
    }
}

function parseArgs(argv) {
    var mode = 'prod';
    var watch = false;
    var i;

    for (i = 2; i < argv.length; i++) {
        if (argv[i] === 'dev' || argv[i] === 'prod') {
            mode = argv[i];
        } else if (argv[i] === '--watch') {
            watch = true;
        }
    }

    return {
        mode: mode,
        watch: watch
    };
}

function getRtlTarget(target) {
    return target.replace(/\.css$/, '-rtl.css');
}

function compileEntry(entry, mode, root) {
    var source = path.join(root, entry.source);
    var target = path.join(root, entry.target);
    var rtlTarget = path.join(root, getRtlTarget(entry.target));
    var result;
    var css;
    var rtl;

    if (!fs.existsSync(source)) {
        throw new Error('Sass source not found: ' + entry.source);
    }

    ensureDir(path.dirname(target));

    result = sass.compile(source, {
        style: mode === 'prod' ? 'compressed' : 'expanded',
        sourceMap: false
    });

    css = result.css;
    rtl = rtlcss.process(css);

    fs.writeFileSync(target, css, 'utf8');
    fs.writeFileSync(rtlTarget, rtl, 'utf8');

    return [entry.target, getRtlTarget(entry.target)];
}

function listSubdirectoriesRecursive(dirPath) {
    var result = [];
    var entries;
    var fullPath;
    var i;

    if (!fs.existsSync(dirPath)) {
        return result;
    }

    entries = fs.readdirSync(dirPath, { withFileTypes: true });

    for (i = 0; i < entries.length; i++) {
        if (entries[i].isDirectory()) {
            fullPath = path.join(dirPath, entries[i].name);
            result.push(fullPath);
            result = result.concat(listSubdirectoriesRecursive(fullPath));
        }
    }

    return result;
}

function debounce(fn, wait) {
    var timeoutId = null;

    return function debounced() {
        if (timeoutId) {
            clearTimeout(timeoutId);
        }

        timeoutId = setTimeout(function runDebounced() {
            timeoutId = null;
            fn();
        }, wait);
    };
}

function watchSass(root, mode) {
    var sassDir = path.join(root, 'src/sass');
    var dirs = [sassDir].concat(listSubdirectoriesRecursive(sassDir));
    var rebuild = debounce(function rebuildSass() {
        runBuild(root, mode);
    }, 100);
    var i;

    for (i = 0; i < dirs.length; i++) {
        fs.watch(dirs[i], rebuild);
    }

    console.log('Sass watch active.');
}

function main() {
    var args = parseArgs(process.argv);
    var root = process.cwd();

    runBuild(root, args.mode);

    if (args.watch) {
        watchSass(root, args.mode);
    }
}

function runBuild(root, mode) {
    var built = [];
    var i;

    for (i = 0; i < entries.length; i++) {
        built = built.concat(compileEntry(entries[i], mode, root));
    }

    console.log('Sass built:');
    for (i = 0; i < built.length; i++) {
        console.log(' - ' + built[i]);
    }
}

main();
