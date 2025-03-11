const https = require('https');
const fs = require('fs');
const path = require('path');
const pkg = require('../package.json');

const baseUrl = 'https://translate.cocartapi.com';
const project = pkg.name;
const languagesDir = path.join(__dirname, '../languages');

// Ensure languages directory exists
if (!fs.existsSync(languagesDir)) {
    fs.mkdirSync(languagesDir, { recursive: true });
}

// First get the list of available translations
https.get(`${baseUrl}/api/projects/${project}/export-translations`, (resp) => {
    let data = '';

    resp.on('data', (chunk) => {
        data += chunk;
    });

    resp.on('end', () => {
        try {
            const translations = JSON.parse(data);
            
            // Download each translation except en_US
            translations.forEach(translation => {
                if (translation.locale !== 'en_US') {
                    const fileName = `${pkg.name}-${translation.locale}`;
                    
                    // Download .po file
                    downloadFile(
                        `${baseUrl}/projects/${project}/${translation.locale}/default/export-translations?format=po`,
                        path.join(languagesDir, `${fileName}.po`)
                    );
                    
                    // Download .mo file
                    downloadFile(
                        `${baseUrl}/projects/${project}/${translation.locale}/default/export-translations?format=mo`,
                        path.join(languagesDir, `${fileName}.mo`)
                    );
                }
            });
        } catch (e) {
            console.error('Error parsing translations list:', e.message);
        }
    });
}).on('error', (err) => {
    console.error('Error fetching translations list:', err.message);
});

function downloadFile(url, dest) {
    const file = fs.createWriteStream(dest);
    https.get(url, (response) => {
        response.pipe(file);
        file.on('finish', () => {
            file.close();
            console.log(`Downloaded: ${dest}`);
        });
    }).on('error', (err) => {
        fs.unlink(dest, () => {}); // Delete the file if there was an error
        console.error(`Error downloading ${dest}:`, err.message);
    });
}
