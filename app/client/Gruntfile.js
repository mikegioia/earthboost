var _ = require( 'lodash' );

module.exports = function ( grunt ) {
    var TEMPLATE_CONFIG = {};

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
                    './src/js/app.js',
                    './src/js/pages/*.js',
                    './src/js/components/*.js',
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
                    './src/css/fonts.css'
                ],
                dest: './build/css/earthboost.css'
            }
        },
        copy: {
            fonts: {
                files: [{
                    expand: true,
                    flatten: true,
                    src: [
                        './src/fonts/**/*.woff2'
                    ],
                    dest: './build/fonts/'
                }]
            }
        }
    });

    grunt.loadNpmTasks( 'grunt-template' );
    grunt.loadNpmTasks( 'grunt-contrib-copy' );
    grunt.loadNpmTasks( 'grunt-contrib-watch' );
    grunt.loadNpmTasks( 'grunt-contrib-concat' );
    grunt.loadNpmTasks( 'grunt-contrib-uglify' );
    grunt.loadNpmTasks( 'grunt-contrib-cssmin' );

    grunt.registerTask( 'default', [ 'concat', 'copy', 'watch' ] );
    grunt.registerTask( 'printenv', function () {
        console.log( process.env );
    });

    // To generate the HTML file, we need to read configuration files
    // from the config directory.
    grunt.registerTask( 'html', 'Generate the HTML files.', function ( env ) {
        var assetVersion, config, options, template, html,
            serverEnvs = [
                'prod'
            ];

        if ( typeof env == 'undefined' || ! env.length ) {
            if ( process.env.ENVIRONMENT ) {
                env = process.env.ENVIRONMENT;
            }
            else {
                grunt.fail.warn( "Missing environment! Usage: grunt html:(<env>|dist)" );
            }
        }

        // Try to read the config files, and extend the options
        config = grunt.file.readJSON( './config/' + env + '.json' );
        options = _.extend( TEMPLATE_CONFIG, config );
        options.env = env;

        // Write out the index file
        template = grunt.file.read( './src/html/template.html' );
        html = grunt.template.process( template, { data: options } );

        // Write the file to the appropriate location
        if ( _.indexOf( serverEnvs, env ) !== -1 ) {
            grunt.file.write( './dist/' + env + '/index.html', html );
        }
        else {
            grunt.file.write( './build/index.html', html );
        }
    });
}