var gulp = require('gulp')
var rename = require("gulp-rename")
var cssBase64 = require('gulp-css-base64')
var cleanCSS = require('gulp-clean-css')
var concat = require('gulp-concat')
var fontcustom = require('gulp-fontcustom')
var replace = require('gulp-replace')
var imagemin = require('gulp-imagemin')
var changed = require("gulp-changed")
var merge = require("merge2")
var addsrc = require('gulp-add-src')
var svgmin = require('gulp-svgmin')
var insert = require('gulp-insert')
var sourcemaps = require('gulp-sourcemaps')
var bourbon = require('bourbon')
var sass = require('gulp-sass')
var closureCompiler = require('gulp-closure-compiler')
var uglify = require('gulp-uglify')
var typedoc = require("gulp-typedoc")
var shell = require('gulp-shell')
var path = require('path')

var comment = `/*!	
 *	@author		HitkoDev http://hitko.eu/videobox
 *	@copyright	Copyright (C) 2016 HitkoDev All Rights Reserved.
 *	@license	http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 *
 *	This program is free software: you can redistribute it and/or modify
 *	it under the terms of the GNU General Public License as published by
 *	the Free Software Foundation, either version 3 of the License, or
 *	any later version.
 *
 *	This program is distributed in the hope that it will be useful,
 *	but WITHOUT ANY WARRANTY; without even the implied warranty of
 *	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 *	GNU General Public License for more details.
 *
 *	You should have received a copy of the GNU General Public License
 *	along with this program. If not, see <http://www.gnu.org/licenses/>
 */`

gulp.task('default', ['licence'], () => { })

gulp.task('licence', [
    'compress',
    'documentation'
], () => {
    return gulp.src(['./dist/videobox.min.js', './dist/videobox.min.css'])
        .pipe(insert.prepend(comment + "\n"))
        .pipe(gulp.dest('./dist'))
})

gulp.task('wrap', [
    'compress:nodep',
    'documentation',
    'images'
], () => {

})

gulp.task('documentation', () => {
    return gulp.src(['./build/**/*.d.ts', './typings/index.d.ts'])
        .pipe(typedoc({
            module: "es2015",
            target: "es6",
            includeDeclarations: true,
            out: "./docs",
            mode: "file",
            excludeExternals: true,
            theme: 'minimal',
            excludePrivate: true
        }))
})

gulp.task('images', () => {
    return gulp.src('./src/images/**')
        .pipe(changed('./build/images'))
        .pipe(imagemin({
            progressive: true,
            optimizationLevel: 7,
            multipass: true,
            interlaced: true
        }))
        .pipe(gulp.dest('./build/images'))
})

gulp.task('scripts:rollup', shell.task([
    'rollup -c'
]))

gulp.task('scripts', [
    'scripts:rollup'
], () => {
    return gulp.src('./build/videobox.js')
        .pipe(closureCompiler({
            compilerPath: 'closure.jar',
            compilerFlags: {
                language_out: 'ES5',
                create_source_map: 'dist/videobox.js.map',
                source_map_input: 'build/videobox.js|build/videobox.js.map'
            },
            fileName: 'videobox.min.js'
        }))
        .pipe(gulp.dest('./dist'))
})

gulp.task('sass', [
    'sass:convert'
], () => {
    return gulp.src('./src/sass/*.scss')
        .pipe(sourcemaps.init())
        .pipe(sass({
            includePaths: [
                bourbon.includePaths
            ],
            outputStyle: 'expanded'
        }))
        .pipe(replace(/(^|\})\s*[^\{\}]*\{\s*\}\s*/igm, ''))
        .pipe(replace(/(^|\})\s*[^\{\}]*\{\s*\}\s*/igm, ''))
        .pipe(replace(/(^|\})\s*[^\{\}]*\{\s*\}\s*/igm, ''))
        .pipe(cssBase64({
            baseDir: "./build"
        }))
        .pipe(sourcemaps.write('.'))
        .pipe(gulp.dest('./build'))
})

gulp.task('sass:convert', [
    'overrides'
], () => {
    return gulp.src(['./src/sass/**/*.scss', '!./src/sass/_icons.scss'], { read: false })
        .pipe(changed('.', {
            hasChanged: (stream, cb, sourceFile, targetPath) => changed.compareLastModifiedTime(stream, cb, sourceFile, path.resolve(process.cwd(), './build/videobox.css'))
        }))
        .pipe(shell([
            'sass-convert -i --indent 4 <%= file.path %>'
        ]))

})

gulp.task('overrides', () => {
    return gulp.src(['./src/sass/**/!(videobox)*.scss', '!./src/sass/overrides.scss'])
        .pipe(concat('overrides.scss'))
        .pipe(replace(/\/\/[^]*?$/igm, ''))
        .pipe(replace(/\@[^\;\{]*?\;/ig, ''))
        .pipe(replace(/\/\*[^]*?\*\//ig, ''))
        .pipe(replace(/[\w\-]+\s*\:((?!(\$primary\-color|\$primary\-light|\:))[^])*?\;/igm, ''))
        .pipe(addsrc('./src/sass/videobox.scss'))
        .pipe(replace(/\/\*[^]*?\*\//ig, ''))
        .pipe(concat('overrides.scss'))
        .pipe(replace(/\@import [^\;\{]*?\;/ig, ''))
        .pipe(replace(/(\$\s*)*\$\s+/igm, ''))
        .pipe(insert.prepend(".vb-overrides-wrap {\n"))
        .pipe(insert.append("\n}"))
        .pipe(gulp.dest('./src/sass'))
})

gulp.task('compress', [
    'sass',
    'images',
    'scripts'
], () => {
    return compress()
})

gulp.task('compress:nodep', () => {
    return compress()
})

function compress() {
    return merge([
        gulp.src('./build/*.css')
            .pipe(sourcemaps.init({ loadMaps: true }))
            .pipe(cleanCSS())
            .pipe(rename({
                suffix: '.min'
            }))
            .pipe(sourcemaps.write('.', {
                mapFile: (mapFilePath) => {
                    return mapFilePath.replace('.min.js.map', '.js.map').replace('.min.css.map', '.css.map')
                }
            }))
            .pipe(gulp.dest('./dist')),

        gulp.src('./dist/videobox.min.js')
            .pipe(sourcemaps.init({ loadMaps: true }))
            .pipe(uglify())
            .pipe(sourcemaps.write('.', {
                mapFile: (mapFilePath) => {
                    return mapFilePath.replace('.min.js.map', '.js.map')
                }
            }))
            .pipe(gulp.dest('./dist'))
    ])
}

gulp.task('icons:font', () => {
    return gulp.src(['./src/icons/*.svg'])
        .pipe(fontcustom({
            font_name: 'Videobox',
            'css-selector': '.vb-icon-{{glyph}}',
            templates: ['_icons.scss'],
            preprocessor_path: '/font'
        }))
        .pipe(gulp.dest('./build/font'))
})

gulp.task('icons:sass', [
    'icons:font'
], () => {
    return gulp.src('./build/font/*.scss')
        .pipe(replace('-{{glyph}}', ', [class^="vb-icon-"], [class*=" vb-icon-"]'))
        .pipe(concat('_icons.scss'))
        .pipe(gulp.dest('./build/font'))
        .pipe(shell([
            'sass-convert -i --indent 4 <%= file.path %>'
        ]))
})

gulp.task('icons', [
    'icons:sass'
], () => {
    return merge([
        gulp.src('./build/font/*.svg')
            .pipe(svgmin({
                plugins: [{
                    removeUselessDefs: false
                }]
            }))
            .pipe(gulp.dest('./build/font')),

        gulp.src('./build/font/_icons.scss')
            .pipe(gulp.dest('./src/sass'))
    ])
})

gulp.task('watch', () => {
    gulp.watch('./build/**/*.d.ts', ['documentation'])
    gulp.watch('./build/**/*.js', ['scripts'])
    gulp.watch('./src/sass/**/*.scss', ['sass'])
})