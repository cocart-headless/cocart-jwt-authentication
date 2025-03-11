const replace = require('replace-in-file');
const pkg = require('../package.json');

const files = {
    plugin: [{
        files: ['cocart-jwt-authentication.php', 'includes/class-cocart-jwt-authentication.php'],
        from: [
            /Description:.*$/m,
            /Requires at least:.*$/m,
            /Requires PHP:.*$/m,
            /WC requires at least:.*$/m,
            /WC tested up to:.*$/m,
            /CoCart requires at least:.*$/m,
            /CoCart tested up to:.*$/m,
            /Version:.*$/m,
            /public static \$version = \'.*.'/m,
            /public static \$required_wp = \'.*.'/m,
            /public static \$required_woo = \'.*.'/m,
            /public static \$required_php = \'.*.'/m
        ],
        to: [
            `Description: ${pkg.description}`,
            `Requires at least: ${pkg.requires}`,
            `Requires PHP: ${pkg.requires_php}`,
            `WC requires at least: ${pkg.wc_requires}`,
            `WC tested up to: ${pkg.wc_tested_up_to}`,
            `CoCart requires at least: ${pkg.cocart_requires}`,
            `CoCart tested up to: ${pkg.cocart_tested_up_to}`,
            `Version:     ${pkg.version}`,
            `public static $version = '${pkg.version}'`,
            `public static $required_wp = '${pkg.requires}'`,
            `public static $required_woo = '${pkg.wc_requires}'`,
            `public static $required_php = '${pkg.requires_php}'`
        ]
    }],
    readme: [{
        files: 'readme.txt',
        from: [
            /Requires at least:(\*\*|)(\s*?)[0-9.-]+(\s*?)$/mi,
            /Requires PHP:(\*\*|)(\s*?)[0-9.-]+(\s*?)$/mi,
            /Tested up to:(\*\*|)(\s*?)[0-9.-]+(\s*?)$/mi,
            /WC requires at least:(\*\*|)(\s*?)[0-9.-]+(\s*?)$/mi,
            /WC tested up to:(\*\*|)(\s*?)[a-zA-Z0-9.-]+(\s*?)$/mi
        ],
        to: [
            `Requires at least: ${pkg.requires}`,
            `Requires PHP: ${pkg.requires_php}`,
            `Tested up to: ${pkg.tested_up_to}`,
            `WC requires at least: ${pkg.wc_requires}`,
            `WC tested up to: ${pkg.wc_tested_up_to}`
        ]
    }]
};

// Execute replacements
Object.values(files).forEach(configs => {
    configs.forEach(config => {
        try {
            replace.sync(config);
            console.log('Replacement complete');
        } catch (error) {
            console.error('Error occurred:', error);
        }
    });
});
