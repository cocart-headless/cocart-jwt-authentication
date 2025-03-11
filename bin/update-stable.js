const replace = require('replace-in-file');
const pkg = require('../package.json');

const options = {
    files: 'readme.txt',
    from: /Stable tag:(\*\*|)(\s*?)[0-9.-]+(\s*?)$/mi,
    to: `Stable tag: ${pkg.version}`
};

try {
    replace.sync(options);
    console.log('Stable tag updated');
} catch (error) {
    console.error('Error occurred:', error);
}
