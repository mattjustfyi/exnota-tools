const fs = require("fs");
const csv = require("csv-parse/sync");
const { stringify } = require("csv-stringify/sync");

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
        let records = csv.parse(data, parseOptions);

        // Remove props
        const propsToRemove = [
            "Artists",
            "Length",
            "Link",
            "Played At",
            "Song Page",
            "Artist Pages",
            "Album Page",
            "In last 5",
        ];
        const propIndexToRemove = propsToRemove.map((prop) => {
            const index = records[0].indexOf(prop);
            if (index === -1) {
                throw new Error(`Property not found: ${prop}`);
            }
            return index;
        });
        records = records.map((row) => {
            const newRow = [];
            row.forEach((col, index) => {
                if (!propIndexToRemove.includes(index)) {
                    newRow.push(col);
                }
            });
            return newRow;
        });

        // Rename props
        const propsToRename = [["Artists Replaced Commas", "Artists"]];
        records[0] = records[0].map((prop) => {
            const propToRename = propsToRename.find(
                ([oldProp]) => oldProp === prop
            );
            return propToRename ? propToRename[1] : prop;
        });

        // Fix dates
        const dateProps = ["Played At Date", "Album Release Timestamp"];
        const datePropIndexes = dateProps.map((prop) => {
            const index = records[0].indexOf(prop);
            if (index === -1) {
                throw new Error(`Property not found: ${prop}`);
            }
            return index;
        });
        records = records.map((row, index) => {
            if (index === 0) {
                return row;
            }
            return row.map((col, index) => {
                if (datePropIndexes.includes(index)) {
                    return new Date(col).toISOString();
                }
                return col;
            });
        });

        // Notes:
        // - multi-selects columns: Values will only be imported if they match
        //   an existing value. Workaround: Change column to type 'text' then
        //   back to multi-select. It can even be done after import.
        // - select columns: I assume the same applies.

        //     const records2 = csv.parse(data2, parseOptions);
        //
        //     console.log(
        //         `File 1 has ${records1.length} rows, File 2 has ${records2.length} rows`
        //     );
        //
        //     console.log("Merging records");
        //     const mergedRecords = records1.map((row, index) => {
        //         const mergedRow = [...row, ...(records2[index] || [])];
        //         console.log(`Merged row ${index + 1}: ${mergedRow.join(", ")}`);
        //         return mergedRow;
        //     });
        //
        //     console.log("Stringifying merged records");
        //     const records2 = csv.parse(data2, parseOptions);
        //
        //     console.log(
        //         `File 1 has ${records1.length} rows, File 2 has ${records2.length} rows`
        //     );
        //
        //     console.log("Merging records");
        //     const mergedRecords = records1.map((row, index) => {
        //         const mergedRow = [...row, ...(records2[index] || [])];
        //         console.log(`Merged row ${index + 1}: ${mergedRow.join(", ")}`);
        //         return mergedRow;
        //     });
        //
        //     console.log("Stringifying merged records");
        return stringify(records);
    } catch (error) {
        console.error("Error parsing CSV:", error.message);
        throw error;
    }
}

function main() {
    console.log("Starting CSV merge process");
    if (process.argv.length !== 3) {
        console.error("Usage: node index.js <input.csv>");
        process.exit(1);
    }

    // const [file1, file2] = process.argv.slice(2);
    // console.log(`Input files: ${file1}, ${file2}`);
    const [file] = process.argv.slice(2);

    const results = fixProps(file);

    const outputFile = file.replace(".csv", "") + "-fixed.csv";
    console.log(`Writing merged CSV to ${outputFile}`);
    fs.writeFileSync(outputFile, results);
    console.log(`Merged CSV saved to ${outputFile}`);
}

console.log("CSV Merge Tool Starting");
main();
console.log("CSV Merge Tool Finished");
