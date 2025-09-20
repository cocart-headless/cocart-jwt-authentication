const wpTextdomain = require('wp-textdomain');
const glob = require('glob');

// Glob pattern to include all PHP files recursively
const files = glob.sync('**/*.php', {
    ignore: [
        '**/node_modules/**', // Exclude node_modules
        '**/vendor/**',       // Exclude vendor folder
        '**/tests/**',        // Exclude tests folder
    ]
});

// Process each matched file
files.forEach((file) => {
    wpTextdomain(
        file,
        {
            domain: 'cocart-jwt-authentication',
            fix: true,
            missingDomain: true,
            variableDomain: true,
            force: true,
            keywords: [
                '__:1,2d',
                '_e:1,2d',
                '_x:1,2c,3d',
                'esc_html__:1,2d',
                'esc_html_e:1,2d',
                'esc_html_x:1,2c,3d',
                'esc_attr__:1,2d',
                'esc_attr_e:1,2d',
                'esc_attr_x:1,2c,3d',
                '_ex:1,2c,3d',
                '_n:1,2,4d',
                '_nx:1,2,4c,5d',
                '_n_noop:1,2,3d',
                '_nx_noop:1,2,3c,4d',
                'wp_set_script_translations:1,2d,3'
            ],
        }
    );
});
