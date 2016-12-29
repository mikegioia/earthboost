var _ = require( 'lodash' );

module.exports = function ( grunt ) {
    var SERVER_ENVS = [
        'prod'
    ];

    grunt.initConfig({
        pkg: grunt.file.readJSON( 'package.json' ),
        // File Watching
        watch: {
            js: {
                files: [ './src/js/**/*.js' ],
                tasks: [ 'concat:js' ]
            },
            css: {
                files: [ './src/css/**/*.css' ],
                tasks: [ 'concat:css' ]
            },
            html: {
                files: [ './src/html/**/*.html' ],
                tasks: [ 'html' ]
            },
            grunt: {
                files: [ 'Gruntfile.js' ]
            }
        },
        // JavaScript
        concat: {
            js: {
                options : {
                    sourceMap: true,
                    separator: ';\n'
                },
                src: [
                    // Vendor dependencies
                    './vendor/page/page.js',
                    './vendor/reqwest/reqwest.js',
                    './vendor/mustache.js/mustache.js',
                    // Environment config
                    './build/js/config.js',
                    // Bootstrap file
                    './src/js/app.js',
                    // Libraries
                    './src/js/lib/**/*.js',
                    // Controllers
                    './src/js/pages/**/*.js',
                    // Components
                    './src/js/components/**/*.js',
                    // Routes
                    './src/js/routes.js'
                ],
                dest: './build/js/earthboost.js'
            },
            css: {
                options : {
                    sourceMap: true
                },
                src: [
                    './vendor/skeleton/css/normalize.css',
                    './vendor/skeleton/css/skeleton.css',
                    './src/css/fonts.css',
                    './src/css/header.css',
                    './src/css/buttons.css',
                    './src/css/notifications.css',
                    './src/css/stage.css',
                    './src/css/dashboard.css',
                    './src/css/group.css',
                    './src/css/media.css'
                ],
                dest: './build/css/earthboost.css'
            }
        },
        // Copy to build or dist
        copy: {
            main: {
                files: [{
                    expand: true,
                    cwd: './src/images/',
                    src: '**',
                    dest: './build/images/'
                }, {
                    expand: true,
                    cwd: './src/fonts/',
                    src: '**',
                    dest: './build/fonts/'
                }]
            },
            dist: {
                files: [{
                    expand: true,
                    cwd: './build/',
                    src: [
                        'fonts/*.woff2',
                        'js/*.min.js',
                        'css/*.min.css'
                    ],
                    dest: './dist/'
                }]
            }
        },
        // Wrap our dist JS file
        wrap: {
            js: {
                src: [
                    'dist/js/earthboost.min.js'
                ],
                dest: '',
                options: {
                    wrapper: [
                        '(function () {\n',
                        '\n}); '
                    ]
                }
            }
        },
        // Minify the built CSS file
        cssmin: {
            build: {
                src: [
                    './build/css/earthboost.css'
                ],
                dest: './build/css/earthboost.min.css'
            }
        },
        // Minify the build JS file
        uglify: {
            js: {
                files: {
                    './build/js/earthboost.min.js': [ './build/js/earthboost.js' ]
                }
            }
        }
    });

    grunt.loadNpmTasks( 'grunt-wrap' );
    grunt.loadNpmTasks( 'grunt-template' );
    grunt.loadNpmTasks( 'grunt-contrib-copy' );
    grunt.loadNpmTasks( 'grunt-contrib-watch' );
    grunt.loadNpmTasks( 'grunt-contrib-concat' );
    grunt.loadNpmTasks( 'grunt-contrib-uglify' );
    grunt.loadNpmTasks( 'grunt-contrib-cssmin' );

    grunt.registerTask( 'build', [ 'concat', 'copy:main' ] );
    grunt.registerTask( 'default', [ 'build', 'watch' ] );
    grunt.registerTask( 'dist', [ 'config:prod', 'build', 'uglify', 'copy:dist', 'wrap', 'html:prod' ])
    grunt.registerTask( 'printenv', function () {
        console.log( process.env );
    });

    // Generates a config file for the application.
    grunt.registerTask( 'config', 'Generate the Config file.', function ( env ) {
        var config;

        env = getEnvironment( env );
        config = grunt.file.readJSON( './config/' + env + '.json' );
        grunt.file.write(
            './build/js/config.js',
            'var Config = ' + JSON.stringify( config, null, '    ' ));
    });

    // To generate the HTML file, we need to read configuration files
    // from the config directory. This also generates a config file
    // that is used by build compilation.
    grunt.registerTask( 'html', 'Generate the HTML files.', function ( env ) {
        var env;
        var html;
        var config;
        var template;
        var configText;

        // Fails if not found
        env = getEnvironment( env );
        // Try to read the config file
        config = grunt.file.readJSON( './config/' + env + '.json' );
        config.env = env;

        // Write out the index and config file
        template = grunt.file.read( './src/html/template.html' );
        html = grunt.template.process( template, { data: config } );

        // Write the file to the appropriate location
        if ( _.indexOf( SERVER_ENVS, env ) !== -1 ) {
            grunt.file.write( './dist/' + env + '/index.html', html );
        }
        else {
            grunt.file.write( './build/index.html', html );
        }
    });

    function getEnvironment ( env ) {
        if ( typeof env == 'undefined' || ! env.length ) {
            if ( process.env.ENVIRONMENT ) {
                env = process.env.ENVIRONMENT;
            }
            else {
                grunt.fail.warn( "Missing environment! Usage: grunt html:(<env>|dist)" );
            }
        }

        return env;
    }
}