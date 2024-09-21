const fs = require("fs");
const csv = require("csv-parse/sync");
const { stringify } = require("csv-stringify/sync");

function mergeCSVFiles(file1, file2) {
    console.log(`Reading files: ${file1} and ${file2}`);
    const data1 = fs.readFileSync(file1, "utf8");
    const data2 = fs.readFileSync(file2, "utf8");

    console.log("Parsing CSV data");
    try {
    const parseOptions = {
        relax_column_count: true,
        skip_empty_lines: true
    };
    const records1 = csv.parse(data1, parseOptions);
    const records2 = csv.parse(data2, parseOptions);

    console.log(
        `File 1 has ${records1.length} rows, File 2 has ${records2.length} rows`
    );

    console.log("Merging records");
    const mergedRecords = records1.map((row, index) => {
        const mergedRow = [...row, ...(records2[index] || [])];
        console.log(`Merged row ${index + 1}: ${mergedRow.join(", ")}`);
        return mergedRow;
    });

    console.log("Stringifying merged records");
    return stringify(mergedRecords);
    } catch (error) {
        console.error("Error parsing CSV:", error.message);
        throw error;
    }
}

function main() {
    console.log("Starting CSV merge process");
    if (process.argv.length !== 4) {
        console.error("Usage: node index.js <file1.csv> <file2.csv>");
        process.exit(1);
    }

    const [file1, file2] = process.argv.slice(2);
    console.log(`Input files: ${file1}, ${file2}`);

    const mergedCSV = mergeCSVFiles(file1, file2);

    const outputFile = "merged.csv";
    console.log(`Writing merged CSV to ${outputFile}`);
    fs.writeFileSync(outputFile, mergedCSV);
    console.log(`Merged CSV saved to ${outputFile}`);
}

console.log("CSV Merge Tool Starting");
main();
console.log("CSV Merge Tool Finished");
