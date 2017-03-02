global.SRC_FOLDER = 'Frontend';
global.RELEASE_FOLDER = 'Crowd/PttBundle/Resources/public';
global.RELEASE_FOLDER = 'Crowd/PttBundle/Resources/public';
global.FRONTEND_URL = '/bundles/ptt/';
global.TMP_FOLDER = 'Frontend/tmp';

global.config = {
    paths: {
        src: {
            index: 'Crowd/PttBundle/Resources/views/Default/layout.html.twig',
            assets: [
                SRC_FOLDER + '/assets/**/*',
                '!' + SRC_FOLDER + '/assets/images/**/*'
            ],
            images: SRC_FOLDER + '/assets/images/**/*',
            scripts: SRC_FOLDER + '/modules/**/*.js',
            styles: SRC_FOLDER + '/styles/base.less',
            stylesGlob: SRC_FOLDER + '/styles/**/*.less',
            modules: './' + SRC_FOLDER + '/modules/app.js',
            app: SRC_FOLDER + '/modules/app',
            controllers: SRC_FOLDER + '/modules/app/controllers',
            directives: SRC_FOLDER + '/modules/common/directives',
            filters: SRC_FOLDER + '/modules/common/filters',
            services: SRC_FOLDER + '/modules/common/services',
            macros: 'Crowd/PttBundle/Resources/views/Macros/*',
            templates: 'Crowd/PttBundle/Resources/views/**/*.html.twig'
        },
        dest: {
            styles: RELEASE_FOLDER,
            scripts: RELEASE_FOLDER,
            images: RELEASE_FOLDER + '/assets/images',
            assets: RELEASE_FOLDER + '/assets',
            index: 'Crowd/PttBundle/Resources/views/Default/',
            translate: './src/FrontendBundle/Resources/translations/',
            server: RELEASE_FOLDER
        }
    },
    filenames: {
        build: {
            styles: 'main.css',
            scripts: 'main.js'
        },
        release: {
            styles: 'main.min.css',
            scripts: 'main.min.js'
        },
        templates: {
            compiled: 'templates.js',
            angular: {
                moduleName: 'app.templates',
                prefix: '',
                stripPrefix: 'app/'
            }
        }
    },
    proxy: 'http://localhost',
    ports: {
        staticServer: 8080,
        livereloadServer: 35729,
        browserSyncPort: 8787
    }
};
