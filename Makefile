SHELL := /bin/bash

version = 0.1-beta-fancybox

# Building everything
all: release

release: gallery-hierarchy.$(version).zip gallery-hierarchy.$(version).tgz

clean: clean-minify clean-release

clean-release:
	rm -f gallery-hierarchy.zip
	rm gallery-hierarchy

coreFiles = readme.txt README.md LICENSE gallery-hierarchy.php lib/GHierarchy.php lib/GHAlbum.php lib/utils.php
core: $(coreFiles)

albumFiles = $(wildcard albums/*.php)
albums: $(albumFiles)

css/basicStyle.css: albums/thumbnails.php
	php createBasicStyle.php


# Submodules
submoduleFiles = $(WPSettings) $(JqueryUiMultiselect) $(JqueryUiTimepicker) $(Lightbox)
submodules: wp-settings jquery-ui-multiselect jquery-ui-timepicker lightbox

WPSettings = $(shell ls lib/wp-settings/{LICENSE,README.md,WPSettings.php})
wp-settings: $(WPSettings)

JqueryUiMultiselect = $(shell ls lib/jquery-ui-multiselect/{src/{jquery.multiselect.filter.min.js,jquery.multiselect.min.js},i18n/*,jquery.multiselect.css,jquery.multiselect.filter.css})
jquery-ui-multiselect: $(JqueryUiMultiselect)

JqueryUiTimepicker = $(shell ls lib/jquery-ui-timepicker/src/jquery-ui-timepicker-addon.{js,css})
jquery-ui-timepicker: $(JqueryUiTimepicker)


Lightbox = $(shell ls lib/fancybox/{*.gif,*.png,jquery.fancybox-1.3.4.pack.js,jquery.mousewheel-3.0.4.pack.js,jquery.fancybox-1.3.4.css})
lightbox : $(Lightbox)


jqueryUi = $(shell ls css/jquery-ui/{images/*,jquery-ui.min.css,jquery-ui.structure.min.css,jquery-ui.theme.min.css})
jquery-ui: $(jqueryUi)

CssFiles = $(CssMinFiles) css/basicStyle.css $(jqueryUi)
css: css/basicStyle.css minify jquery-ui

js: minifyjs

# Minifying Files
minify: minifycss minifyjs

clean-minify: clean-minifyjs clean-minifycss

# Javscript Files
JSFiles=js/ghierarchy.min.js
minifyjs: $(JSFiles)

clean-minifyjs:
	rm -f js/ghierarchy.min.js

js/ghierarchy.min.js: js/ghierarchy.js
	minify js/ghierarchy.js > js/ghierarchy.min.js

# CSS Files
CssMinFiles=css/basicStyle.min.css css/ghierarchy.min.css
minifycss: $(CssMinFiles)

clean-minifycss:
	rm -f $(CssMinFiles)

css/ghierarchy.min.css: css/ghierarchy.css
	minify css/ghierarchy.css > css/ghierarchy.min.css

css/basicStyle.min.css: css/basicStyle.css
	minify css/basicStyle.css > css/basicStyle.min.css

Files = $(JSFiles) $(CssFiles) $(coreFiles) $(albumFiles) $(submoduleFiles)
# Building the release file
gallery-hierarchy:
	ln -s . gallery-hierarchy

gallery-hierarchy.$(version).zip: gallery-hierarchy core albums css js submodules
	zip -X gallery-hierarchy.$(version).zip $(addprefix gallery-hierarchy/,$(Files))

gallery-hierarchy.$(version).tgz: gallery-hierarchy core albums css js submodules
	tar -czf gallery-hierarchy.$(version).tgz $(addprefix gallery-hierarchy/,$(Files))
