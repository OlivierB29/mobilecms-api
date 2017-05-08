var gulp = require('gulp');

/**
* Sample API endpoint for adminapp API :
* http://localhost/adminapp/api/v1/
*/
var projectName = 'adminapp';

/**
* Local web server directory
*/
var serverDeployDir = '/var/www/html';

/**
* Local private directory for users
*/
var privateDeployDir = '/var/www/private';


/**
* basedir for current gulp runtime
*/

var basedir = '.';
/**
 * deploy php server code to local web server
 */
gulp.task('deploy', function() {

  var srcPath = basedir + '/src/**';
  var destPath = serverDeployDir + '/' + projectName;

  console.log('Deploying server : ' + srcPath + ' --> ' + destPath);

  gulp.src(srcPath).pipe(gulp.dest(destPath));

});

/**
 * deploy sampe data
 */
gulp.task('samplepublic', function() {

  var srcPath = basedir + '/tests-data/public/**';
  var destPath = serverDeployDir + '/public';

  console.log('Deploying sampledata : ' + srcPath + ' --> ' + destPath);

  gulp.src(srcPath).pipe(gulp.dest(destPath));
});

/**
 * deploy sample private users
 */
gulp.task('sampleprivate', function() {

  var srcPath = basedir + '/tests-data/private/**';
  var destPath = privateDeployDir;

  console.log('Deploying sampleprivate : ' + srcPath + ' --> ' + destPath);

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
  console.log('Tests :\n gulp test-utils test-api');
  console.log('Sampledata :\n gulp sampledata sampleprivate');

});
