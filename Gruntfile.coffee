# global module:false

module.exports = (grunt) ->

  pkg = grunt.file.readJSON 'package.json'
  plugin = '<%= pkg.title || pkg.name %>'
  build = "build/#{plugin}"

  # Project configuration.
  grunt.initConfig
    # Metadata.
    pkg: pkg
    banner: '/*! <%= pkg.title || pkg.name %> - v<%= pkg.version %> - ' +
      '<%= grunt.template.today("yyyy-mm-dd") %>\n' +
      '<%= pkg.homepage ? "* " + pkg.homepage + "\\n" : "" %>' +
      '* Copyright (c) <%= grunt.template.today("yyyy") %> <%= pkg.author.name %>;' +
      ' Licensed <%= _.pluck(pkg.licenses, "type").join(", ") %> */\n'

    # Task configuration.
    clean:
      build: ["#{build}/", "#{build}.zip"]

    copy:
      build:
        files: [
          {
            expand: true
            src: [
              "*.php"
              "license"
            ]
            dest: "#{build}/"
          }
          {
            expand: true
            src: "admin/**"
            dest: "#{build}/"
          }
          {
            expand: false
            src: "data/"
            dest: "#{build}/"
          }
          {
            expand: true
            src: "includes/**"
            dest: "#{build}/"
          }
          {
            expand: true
            src: [
              "vendor/**/*.php"
              "vendor/**/src/**"
              "!vendor/**/test/**"
              "!vendor/**/doc/**"
              "!vendor/**/example/**"
              "!vendor/**/tests/**"
              "!vendor/**/docs/**"
              "!vendor/**/examples/**"
            ]
            dest: "#{build}/"
          }
        ]

    compress:
      build:
        options:
          archive: "#{build}.zip"
          mode: "zip"
        expand: true
        cwd: "#{build}"
        src: "**/*"

  # These plugins provide necessary tasks
  grunt.loadNpmTasks 'grunt-contrib-copy'
  grunt.loadNpmTasks 'grunt-contrib-clean'
  grunt.loadNpmTasks 'grunt-contrib-compress'
  grunt.loadNpmTasks 'grunt-contrib-coffee'

  # Default task.
  grunt.registerTask 'default', []
  grunt.registerTask 'build', ['clean:build', 'copy:build', 'compress:build']
