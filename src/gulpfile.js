var gulp = require('gulp');
var path = require('path');
var rename = require('gulp-rename');

var fs = require('fs');

var uglify = require('gulp-uglify');
var cssMinify = require('gulp-mini-css');
var lessCss = require('gulp-less');
var replace = require('gulp-replace');
var jsValidate = require('gulp-jsvalidate');
var cssValidate = require('gulp-css-validator');
var insert = require('gulp-insert');
var shell = require('gulp-shell');
var inlining = require('gulp-inlining-node-require');
//var concat = require('gulp-concat');
var package = require('./package.json');
//var watchify = require('gulp-watchify');
//var streamify = require('gulp-streamify');
var include = require('../../gulp-include');
var spawn = require('child_process').spawn;
var stripLine  = require('gulp-strip-line');
var tar = require('gulp-tar');
var gzip = require('gulp-gzip');
var symlink = require('gulp-symlink');
var del = require('del');

var paths = {
	dist: '../dist/',
	build: '../build/',
	ext: {},
	int: ['LICENSE', 'lib/GHierarchy.php', 'lib/GHAlbum.php', 'lib/utils.php',
			'screenshot-*.jpg'],
	albumsSrc: 'albums/*.php',
	main: 'gallery-hierarchy.php',
	readme: 'README.md',
	//jsSrc: '{js/ghierarchy.js,albums/js/*.js}',
	jsSrc: ['js/{ghierarchy,tinymce}.js', 'albums/js/*.js'],
	cssSrc: 'css/{jquery.plupload.queue.css,ghierarchy.less}',
	basicStylesScript: 'createBasicStyle.php',
	basicSrc: 'js/basicStyle.css'
};
//paths.albums = path.join(paths.dist, 'albums');
paths.allJsSrc = include.files(paths.jsSrc);
console.log(paths.allJsSrc);
paths.js = path.join(paths.dist, 'js');
paths.css = path.join(paths.dist, 'css');
paths.builtJsDir = path.join(paths.build, 'js');
paths.builtCssDir = path.join(paths.build, 'css');
paths.builtCss = path.join(paths.builtCssDir, '*.css');
if (paths.jsSrc instanceof Array) {
	paths.builtJs = [];
	var i;
	for (i in paths.jsSrc) {
		paths.builtJs.push(path.join(paths.build, paths.jsSrc[i]));
	}
} else {
	paths.builtJs = path.join(paths.build, paths.jsSrc);
}

// WP Settings
paths.ext.wpsettings = {
	js: 'lib/wp-settings/js/wpsettings.min.js',
	css: 'lib/wp-settings/css/wpsettings.min.css',
	lib: 'lib/wp-settings/WPSettings.php'
};

// JQuery Touch Events
paths.ext.touchEvents = {
	js: 'lib/jquery-touch-events/src/jquery.mobile-events.min.js',
};

// JQuery Timepicker
paths.ext.timepicker = {
	js: 'lib/jquery-ui-timepicker/src/jquery-ui-timepicker-addon.js',
	css: 'lib/jquery-ui-timepicker/src/jquery-ui-timepicker-addon.css'
};

// Lightbox 2
paths.ext.lightbox = {
	js: 'lib/lightbox2/js/lightbox.min.{js,map}',
	css: 'lib/lightbox2/css/*',
	img: 'lib/lightbox2/img/*.{png,gif}',
};

// Fancybox
paths.ext.fancybox = {
	js: 'lib/fancybox/{jquery.fancybox-1.3.4.pack.js,jquery.mousewheel-3.0.4.pack.js}',
	css: 'lib/fancybox/{blank.gif,fancybox{,-{x,y}}.png,jquery.fancybox-1.3.4.css}',
};

// Jquery Folders
paths.ext.hierarchySelect = {
	js: 'lib/jquery-hierarchy-select/dist/js/folders.js',
	css: 'lib/jquery-hierarchy-select/dist/css/folders.css'
};

// Jquery UI
paths.ext.ui = {
	css: 'css/jquery-ui/{jquery-ui.min.css,jquery-ui.structure.min.css,jquery-ui.theme.min.css}',
	'css/images': 'css/jquery-ui/images/*'
};

// fadeSlideShow
paths.ext.fadeSlideShow = {
	css: 'lib/simplefadeslideshow/demoStyleSheet.css',
	js: 'lib/simplefadeslideshow/fadeSlideShow.js'
};

// plupload
paths.ext.plupload = {
	i18n: 'lib/plupload/js/i18n/*',
	js: 'lib/plupload/js/{moxie.min.js,plupload.full.min.js,jquery.plupload.queue/jquery.plupload.queue.min.js}',
	css: 'css/jquery.plupload.queue.css',
	img: 'lib/plupload/js/jquery.plupload.queue/img/{{transp50,buttons{,-disabled}}.png,{delete,done,backgrounds}.gif}'
};

// packery
paths.ext.packery = {
	js: 'lib/packery/dist/packery.pkgd.min.js'
};

// arranger
paths.ext.arranger = {
	js: 'lib/jquery-arranger/dist/js/arranger.js',
	css: 'lib/jquery-arranger/dist/css/arranger.css'
};

gulp.task('clean', [], function() {
	del([
		path.join(paths.dist, '**'),
		path.join(paths.build, '**')
	], {force: true});
});

gulp.task('markupMainPhp', [], function() {
	return gulp.src(paths.main)
			.pipe(insert.prepend('<?php\n/**\n'
					+ 'Plugin Name: ' + package.title + '\n'
					+ 'Plugin URI: ' + package.homepage + '\n'
					+ 'Version: ' + package.version + '\n'
					+ 'Description: ' + package.description + '\n'
					+ 'Author: ' + package.author.name + '\n'
					+ 'Author URI: ' + package.author.url + '\n'
					+ 'Text Domain: gallery-hierarchy\n'
					+ 'Tags: ' + package.keywords.join(',') + '\n'
					+ 'Licence: ' + package.licence + '\n'
					+ '*/\n?>\n'
			))
			.pipe(gulp.dest(paths.dist));
});

gulp.task('readme', [], function() {
	// readme.txt
	return gulp.src(paths.readme)
			.pipe(replace(/^## (.*)/mg, '== $1 =='))
			.pipe(replace(/^###+ (.*)/mg, '= $1 ='))
			.pipe(insert.prepend('=== ' + package.title + ' ===\n'
					+ 'Contributors: weldstudio\n'
					+ 'Donate link: http://gift.meldce.com\n'
					+ 'Link: ' + package.homepage + '\n'
					+ 'Tags: ' + package.keywords.join(', ') + '\n'
					+ 'Requires at least: 3.8.0\n'
					+ 'Tested up to: 4.2.2\n'
					+ 'Stable tag: ' + package.version + '\n'
					+ 'License: ' + package.licence + '\n'
					+ 'License URI: http://www.gnu.org/licenses/gpl-2.0.html\n'
					+ '\n'
					+ package.description + '\n'
			))
			.pipe(rename({
					basename: "readme",
					extname: ".txt"
			}))
			.pipe(gulp.dest(paths.dist))
			// README.md
			&& gulp.src(paths.readme)
					.pipe(insert.prepend('[' + package.title + ' (' + package.name
							+ ')](' + package.homepage + ')\n'
							+ '====================\n'
							+ package.description + '\n\n'
					))
					.pipe(gulp.dest('../'));
});

gulp.task('auto-reload', function() {
	var process;

	function restart() {
		if (process) {
			process.kill();
		}

		process = spawn('gulp', ['default'], {stdio: 'inherit'});
	}

	gulp.watch('gulpfile.js', restart);
	restart();
});

gulp.task('validateJs', [], function() {
	return gulp.src(paths.allJsSrc)
			.pipe(jsValidate());
});

gulp.task('js', ['validateJs'], function() {
	return gulp.src(paths.jsSrc)
			.pipe(include())
			.pipe(gulp.dest(paths.js))
			.pipe(uglify({
				preserveComments: 'some'
			}))
			.pipe(rename(function (path) {
						path.extname = '.min.js'
					}))
			.pipe(gulp.dest(paths.js));
});

gulp.task('validateCss', ['buildCss'], function() {
			return gulp.src(paths.builtCss)
					.pipe(cssValidate());
		});

gulp.task('buildCss', [], function() {
			return gulp.src(paths.cssSrc)
					.pipe(lessCss())
					.pipe(gulp.dest(paths.builtCssDir));
		});

//gulp.task('css', ['validateCss'], function() {
gulp.task('css', ['buildCss'], function() {
			return gulp.src(paths.builtCss)
					.pipe(rename(function (path) {
								path.extname = '.css'
							}))
					.pipe(gulp.dest(paths.css))
					.pipe(cssMinify())
					.pipe(rename(function (path) {
								path.extname = '.min.css'
							}))
					.pipe(gulp.dest(paths.css));
		});

gulp.task('createBasicStyle', [], shell.task([
			'php ' + paths.basicStylesScript + ' > ' + paths.basicSrc
		]));

gulp.task('basicStyle', ['createBasicStyle'], function() {
			return gulp.src(paths.basicSrc)
					.pipe(cssMinify())
					.pipe(rename(function (path) {
								path.extname = '.min.css'
							}))
					.pipe(gulp.dest(paths.css));
		});

gulp.task('intFiles', ['readme'], function() {
			return gulp.src(paths.int, {base: './'})
					.pipe(gulp.dest(paths.dist));
		});

gulp.task('albumFiles', [], function() {
			return gulp.src(paths.albumsSrc, {base: './'})
					.pipe(gulp.dest(paths.dist));
		});

gulp.task('html', [], function() {
		});

for (e in paths.ext) {
	gulp.task(e, [], (function(e) { return function() {
		var p;
		for (p in paths.ext[e]) {
			gulp.src(paths.ext[e][p], {base: './'})
					.pipe(rename({dirname: path.join('lib', p)}))
					.pipe(gulp.dest(paths.dist));
		}
	}})(e));
}

gulp.task('watch', function() {
	gulp.watch(paths.main, ['markupMainPhp']);
	gulp.watch(paths.allJsSrc, ['js']);
	gulp.watch(paths.cssSrc, ['css']);
	gulp.watch(paths.basicStylesScript, ['basicStyle']);
	gulp.watch(paths.int, ['intFiles']);
	gulp.watch(paths.albumsSrc, ['albumFiles', 'basicStyle']);
	for (e in paths.ext) {
		var f, files = [];
		for (f in paths.ext[e]) {
			files.push(paths.ext[e][f]);
		}
		gulp.watch(files, [e]);
	}
});

var defaultTasks = ['readme', 'markupMainPhp', 'css', 'js', 'intFiles', 'albumFiles', 'basicStyle'].concat(Object.keys(paths.ext));

gulp.task('package', defaultTasks, function() {
	// removed $lp lines
	var file = path.join(paths.dist, 'lib/GHierarchy.php');
	
	shell.task('sed -ie \'/static::$lp/d\' ' + file);

	//gulp.src(path.join(paths.dist, 'lib/GHierarchy.php'))
	//		.pipe(stripline([/static::\$lp/]))
	//		.pipe(gulp.dest(path.join(paths.dist, 'lib')));

	//gulp.src(paths.dist)
	//		.pipe(symlink('../gallery-hierarchy'));

	console.log('cd .. && tar -chzf ' + package.name + '-' + package.version
			+ '.tgz gallery-hierarchy');
	shell.task('tar -chzf ' + package.name + '-' + package.version
			+ '.tgz gallery-hierarchy', {cwd: '../'});
	shell.task('zip ' + package.name + '-' + package.version
			+ '.zip gallery-hierarchy', {cwd: '../'});
	shell.task('pwd');
	shell.task('cd .. && pwd');
});

gulp.task('one', defaultTasks);

gulp.task('default', defaultTasks.concat(['watch']));

gulp.task('pre_production', [], function() {
	delete paths.ext.lightbox;
});

gulp.task('production', ['pre_production', 'package'].concat(Object.keys(paths.ext)));
