var gulp = require('gulp');

var basedir = '.';

var projectName = 'adminapp';

var serverDeployDir = '/var/www/html';

/**
 * deploy php server code to local web server
 */
gulp.task('deploy', function() {

  var srcPath = basedir + '/src/**';
  var destPath = serverDeployDir + '/' + projectName;

  console.log('Deploying server : ' + srcPath + ' --> ' + destPath);

  gulp.src(srcPath).pipe(gulp.dest(destPath));

});

/*
* Run tests
* Requirements : phpunit
* https://phpunit.de/getting-started.html
*/
gulp.task('test-utils', function() {

  console.log('phpunit tests :');



  var run = require('gulp-run');

  return run('phpunit --configuration phpunit-utils.xml').exec()
    .pipe(gulp.dest('output'))
  ;

});

gulp.task('test-api', function() {

  console.log('phpunit tests :');



  var run = require('gulp-run');

  return run('phpunit --configuration phpunit-api.xml').exec()
    .pipe(gulp.dest('output'))
  ;

});


gulp.task('default', function() {

  console.log('Server : check directories, and then \n gulp deploy');


});
