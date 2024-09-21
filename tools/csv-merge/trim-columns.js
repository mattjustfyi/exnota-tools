const fs = require('fs');
const csv = require('csv-parser');
const { stringify } = require('csv-stringify/sync');

const inputPath = process.argv[2];
const outputPath = process.argv[3];

if (!inputPath || !outputPath) {
  console.error('Usage: node trim-columns.js <input-csv-path> <output-csv-path>');
  process.exit(1);
}

const results = [];

fs.createReadStream(inputPath)
  .pipe(csv())
  .on('data', (data) => {
    const trimmedData = {};
    for (const [key, value] of Object.entries(data)) {
      trimmedData[key] = value.substring(0, 1900);
    }
    results.push(trimmedData);
  })
  .on('end', () => {
    const output = stringify(results, { header: true });
    fs.writeFileSync(outputPath, output);
    console.log(`Processed CSV saved to ${outputPath}`);
  });
