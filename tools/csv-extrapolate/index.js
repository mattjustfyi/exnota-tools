const fs = require("fs");
const csv = require("csv-parse/sync");
const { stringify } = require("csv-stringify/sync");

const extrapolateFactor = 23;

function fixProps(file) {
    console.log(`Reading files: ${file}`);
    const data = fs.readFileSync(file, "utf8");
    //     const data2 = fs.readFileSync(file2, "utf8");
    //
    console.log("Parsing CSV data");
    try {
        const parseOptions = {
            relax_column_count: true,
            skip_empty_lines: true,
        };
        const records = csv.parse(data, parseOptions);

        // Algorithm
        // - Accepts a CSV, list of properties and types (date or number), and
        //   an extrapolation factor (e.g. 4x on 50 rows give 200 rows).
        // - Orders by date
        const dateColumns = [
            {
                header: "Played At Timestamp",
                type: "timestamp-ms",
            },
            {
                header: "Played At Date",
                type: "date",
            },
        ];
        const dateColumnIndexes = dateColumns.map((column) => {
            const index = records[0].indexOf(column.header);
            if (index === -1) {
                throw new Error(`Column not found: ${column.header}`);
            }
            return index;
        });
        const dateSortIndex = dateColumnIndexes[0];
        const rowData = records.slice(1);
        // Sort newest to oldest
        rowData.sort((a, b) => {
            const aNum = parseInt(a[dateSortIndex]);
            const bNum = parseInt(b[dateSortIndex]);
            if (isNaN(aNum) || isNaN(bNum)) {
                throw new Error("Invalid date value");
            }
            return bNum - aNum;
        });

        // - Defines an offset by calculating the difference between most recent
        //   and oldest date and adding 1. E.g. if most recent was 10 Nov and
        //   oldest was 5 Nov, the offset is 6 days.
        const mostRecentDate = parseInt(rowData[0][dateSortIndex]);
        const oldestDate = parseInt(rowData[rowData.length - 1][dateSortIndex]);
        const offset = mostRecentDate - oldestDate + 1 * 24 * 60 * 60 * 1000;

        const extrapolatedRowData = [];
        for (let i = 1; i < extrapolateFactor; i++) {
            for (let j = 0; j < rowData.length; j++) {
                const row = rowData[j];
                const newRow = [...row];
                for (let k = 0; k < dateColumnIndexes.length; k++) {
                    const dateColumn = dateColumns[k];
                    const dateIndex = dateColumnIndexes[k];
                    if (dateColumn.type === "timestamp-ms") {
                        const currentDateInt = parseInt(newRow[dateIndex], 10);
                        if (isNaN(currentDateInt)) {
                            throw new Error("Invalid date value");
                        }
                        // Don't think this will handle daylight savings time
                        const newDate = new Date(currentDateInt - i * offset);

                        newRow[dateIndex] = newDate.getTime();
                    } else if (dateColumn.type === "date") {
                        const currentDate = new Date(newRow[dateIndex]);
                        // Don't think this will handle daylight savings time
                        const newDateInt = currentDate.getTime() - i * offset;
                        const newDate = new Date(newDateInt);
                        const newDateString = newDate.toISOString();
                        newRow[dateIndex] = newDateString;
                    } else {
                        throw new Error("Invalid date type");
                    }
                }
                extrapolatedRowData.push(newRow);
            }
        }

        // - Loops through all rows, duplicates all except the date rows, and
        //   for those subtracts the offset.
        // - Repeats for the number of extrapolations.

        const updatedRecords = [records[0], ...rowData, ...extrapolatedRowData];
        return stringify(updatedRecords);
    } catch (error) {
        console.error("Error parsing CSV:", error.message);
        throw error;
    }
}

function main() {
    console.log("Starting CSV extrapolate process");
    if (process.argv.length !== 3) {
        console.error("Usage: node index.js <input.csv>");
        process.exit(1);
    }

    // const [file1, file2] = process.argv.slice(2);
    // console.log(`Input files: ${file1}, ${file2}`);
    const [file] = process.argv.slice(2);

    const results = fixProps(file);

    const outputFile = file.replace(".csv", "") + "-extrapolated.csv";
    console.log(`Writing extrapolate CSV to ${outputFile}`);
    fs.writeFileSync(outputFile, results);
    console.log(`Extrapolate CSV saved to ${outputFile}`);
}

console.log("CSV Extrapolate Tool Starting");
main();
console.log("CSV Extrapolate Tool Finished");
