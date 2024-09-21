const fs = require('fs');
const csv = require('csv-parse/sync');
const { stringify } = require('csv-stringify/sync');

function mergeCSVFiles(file1, file2) {
    const data1 = fs.readFileSync(file1, 'utf8');
    const data2 = fs.readFileSync(file2, 'utf8');

    const records1 = csv.parse(data1);
    const records2 = csv.parse(data2);

    const mergedRecords = records1.map((row, index) => {
        return [...row, ...(records2[index] || [])];
    });

    return stringify(mergedRecords);
}

function main() {
    if (process.argv.length !== 4) {
        console.error('Usage: node index.js <file1.csv> <file2.csv>');
        process.exit(1);
    }

    const [file1, file2] = process.argv.slice(2);
    const mergedCSV = mergeCSVFiles(file1, file2);

    const outputFile = 'merged.csv';
    fs.writeFileSync(outputFile, mergedCSV);
    console.log(`Merged CSV saved to ${outputFile}`);
}

main();
