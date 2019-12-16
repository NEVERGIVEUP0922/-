var gulp = require('gulp'),
    less = require('gulp-less'),
    notify = require('gulp-notify'),
    plumber = require('gulp-plumber');
var babel = require('gulp-babel');
var rename = require('gulp-rename');
/*var watch = require("gulp-watch");*/
var minifycss = require('gulp-minify-css');
var uglify = require('gulp-uglify');
var del = require('del');
var connect = require('gulp-connect');
var livereload = require('gulp-livereload');
var htmlmin = require('gulp-htmlmin');//Html压缩  
var less1 = {
    src:'Public/Home/**/*.less',
    dest:'Public/Home'
};
var less2 = {
    src:'Public/Common/toolkit/**/*.less',
    dest:'Public/Common/toolkit'
};
var es6={
    src:'src/js/**/*.js',//'Public/Home/js/**/*.js',
    dest:'Public/Home/seeJs/js'
};

// 注册任务
gulp.task('es6',function(){
    //   console.log(myGulp.src,myGulp.dest);
    return gulp.src(es6.src)
    //.pipe(plumber({errorHandler: notify.onError('Error: <%= error.message %>')}))
        .pipe(connect.reload())
        //将ES6代码转译为可执行的JS代码
        .pipe(babel({'presets': ['env']}))
        .pipe(rename({
            suffix: '_min'
        }))
        .pipe(uglify())
        .pipe(gulp.dest('dist/js'));
});
/*---------------------
    作者：杨ni
来源：CSDN
原文：https://blog.csdn.net/lyliyangzi/article/details/75208607
    版权声明：本文为博主原创文章，转载请附上博文链接！*/
gulp.task('less1', function () {
    gulp.src(less1.src)
        .pipe(plumber({errorHandler: notify.onError('Error: <%= error.message %>')}))
        .pipe(less())
        .pipe(gulp.dest(less1.dest));
});
gulp.task('less2', function () {
    gulp.src(less2.src)
        .pipe(plumber({errorHandler: notify.onError('Error: <%= error.message %>')}))
        .pipe(less())
        .pipe(gulp.dest(less2.dest));
});
gulp.task('watch', function () {
    gulp.watch(less1.src, ['less1']);
    gulp.watch(less2.src, ['less2']);
});
gulp.task('watchEs6', function () {
    gulp.watch(es6.src, ['es6']);
});