var project                 = 'fullworks-vulnrability-scanner'; // Project Name.
// @TODO remove non dist from vendor  see widget-for-eventbrite-api for how
// @TODO add james kemps freemius upload gulp

var gulp = require('gulp');
var zip = require('gulp-zip');
var del = require('del');
var rename = require('gulp-rename');
var gutil = require('gulp-util');
var dirSync = require( 'gulp-directory-sync' );
var removeLines = require('gulp-remove-lines');
var wpPot = require('gulp-wp-pot');
var sort = require('gulp-sort');
var notify = require("gulp-notify");

gulp.task('build', ['remove:secret'], function() {
    gulp.src('dist/**/*')
        .pipe(rename(function(file) {
            file.dirname = project+'/' + file.dirname;
        }))
        .pipe(zip(project+'.zip'))
        .pipe(gulp.dest('zipped'))
});

gulp.task('remove:secret',['clean:files','translate'], function () {
    gulp.src('src/includes/class-freemius-config.php')
        .pipe(removeLines({
            'filters': [
                /Set the SDK/,
                /IMPORTANT/,
                /\'secret_key+/
            ]
        }))
        .pipe(gulp.dest('dist/includes'));
});

gulp.task('clean:files',['sync'], function () {
    return del([
        'dist/**/sass/',
        'dist/**/*.css.map',
        'dist/composer.*',
        'dist/includes/vendor/bin/',
        'dist/includes/vendor/composer/ca-bundle/',
        'dist/includes/vendor/composer/installers/',
        'dist/includes/vendor/**/.git*',
        'dist/includes/vendor/**/.travis.yml',
        'dist/includes/vendor/**/.codeclimate.yml',
        'dist/includes/vendor/**/composer.json',
        'dist/includes/vendor/**/package.json',
        'dist/includes/vendor/**/gulpfile.js',
        'dist/includes/vendor/**/*.md',
        'dist/includes/vendor/maxmind/',
        'dist/includes/vendor/prospress/action-scheduler/docs/',
        'dist/includes/vendor/prospress/action-scheduler/tests/',
        'dist/includes/vendor/faisalman/ua-parser-js/src/',
        'dist/includes/vendor/faisalman/ua-parser-js/test/',
        'dist/includes/vendor/faisalman/ua-parser-js/.npmrc',
        'dist/includes/vendor/faisalman/ua-parser-js/bower.json',
        'dist/includes/vendor/faisalman/ua-parser-js/package.js',
        'dist/includes/vendor/running-coder/jquery-typeahead/src/',
        'dist/includes/vendor/running-coder/jquery-typeahead/example/',
        'dist/includes/vendor/running-coder/jquery-typeahead/src/',
        'dist/includes/vendor/running-coder/jquery-typeahead/test/',
        'dist/includes/vendor/running-coder/jquery-typeahead/.babelrc',
        'dist/includes/vendor/running-coder/jquery-typeahead/.editorconfig',
        'dist/includes/vendor/running-coder/jquery-typeahead/bower.json',
        'dist/includes/vendor/running-coder/jquery-typeahead/gulpfile.babel.js',
        'dist/includes/vendor/running-coder/jquery-typeahead/yarn.lock',
        'dist/includes/vendor/tomverran/robots-txt-checker/tests/',
        'dist/includes/vendor/tomverran/robots-txt-checker/phpunit.xml',
        'dist/includes/vendor/tomverran/robots-txt-checker/Vagrantfile',
        'dist/includes/vendor/Valve/fingerprintjs2/flash/',
        'dist/includes/vendor/Valve/fingerprintjs2/specs/',
        'dist/includes/vendor/Valve/fingerprintjs2/.eslintrc',
        'dist/includes/vendor/Valve/fingerprintjs2/bower.json',
        'dist/includes/vendor/Valve/fingerprintjs2/fingerprint2.js',
        'dist/includes/vendor/Valve/fingerprintjs2/index.html',
        'dist/includes/vendor/Valve/fingerprintjs2/yarn.lock'
    ]);
});


gulp.task( 'sync', function() {
    return gulp.src( '' )
        .pipe(dirSync( 'src', 'dist', { printSummary: true } ))
        .on('error', gutil.log);
} );

gulp.task( 'translate', function () {
    return gulp.src( ['src/**/*.php','!src/includes/{vendor,vendor/**}'])
        .pipe(sort())
        .pipe(wpPot( {
            domain        : project,
            package       : project
        } ))
        .on('error', gutil.log)
        .pipe(gulp.dest('src/languages/'+project+'.pot'))
        .pipe(gulp.dest('dist/languages/'+project+'.pot'))
        .pipe( notify( { message: 'TASK: "translate" Completed! 💯', onLast: true } ) );

});
var livereload = require('gulp-livereload');

gulp.task('watch', function() {
    livereload.listen();
    gulp.watch('src/**/*.*',['reload']);
});
gulp.task('reload', function() {
        livereload();
        console.log('changed !');
});
