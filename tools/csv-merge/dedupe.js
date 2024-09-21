const fs = require('fs');
const csv = require('csv-parser');
const createCsvWriter = require('csv-writer').createObjectCsvWriter;

if (process.argv.length !== 4) {
  console.error('Usage: node dedupe.js <input_csv> <output_csv>');
  process.exit(1);
}

const inputFile = process.argv[2];
const outputFile = process.argv[3];

const results = [];
const seenValues = new Set();

fs.createReadStream(inputFile)
  .pipe(csv())
  .on('data', (data) => {
    const firstColumnValue = Object.values(data)[0];
    if (!seenValues.has(firstColumnValue)) {
      seenValues.add(firstColumnValue);
      results.push(data);
    }
  })
  .on('end', () => {
    const csvWriter = createCsvWriter({
      path: outputFile,
      header: Object.keys(results[0]).map(key => ({ id: key, title: key }))
    });

    csvWriter.writeRecords(results)
      .then(() => console.log('CSV file has been written successfully.'))
      .catch((err) => console.error('Error writing CSV:', err));
  });
