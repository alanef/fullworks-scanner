var project = 'fullworks-scanner'; // Project Name.

var gulp = require('gulp');
var zip = require('gulp-zip');
var del = require('del');
var rename = require('gulp-rename');
var gutil = require('gulp-util');
var dirSync = require('gulp-directory-sync');
var removeLines = require('gulp-remove-lines');
var wpPot = require('gulp-wp-pot');
var sort = require('gulp-sort');
var notify = require("gulp-notify");


gulp.task('zip', (done) => {
    gulp.src('dist/**/*')
        .pipe(rename(function (file) {
            file.dirname = project + '/' + file.dirname;
        }))
        .pipe(zip(project + '-free.zip'))
        .pipe(gulp.dest('zipped'))
    done()
});


gulp.task('clean', () => {
    return del([
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
    ]);
});


gulp.task('sync', () => {
    return gulp.src('.', {allowEmpty: true})
        .pipe(dirSync('src', 'dist', {printSummary: true}))
        .on('error', gutil.log);
});

gulp.task('translate', () => {
    return gulp.src(['src/**/*.php', '!src/includes/{vendor,vendor/**}'])
        .pipe(sort())
        .pipe(wpPot({
            domain: project,
            package: project
        }))
        .on('error', gutil.log)
        .pipe(gulp.dest('src/languages/' + project + '.pot'))
        .pipe(gulp.dest('dist/languages/' + project + '.pot'))
        .pipe(notify({message: 'TASK: "translate" Completed! 💯', onLast: true}));

});


gulp.task('build', gulp.series('sync',  'clean', 'translate', 'zip'));



