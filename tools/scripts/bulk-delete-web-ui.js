// Open a database in full screen, then paste this in dev tools console to
// delete all records.
//
// Notes:
// - Sometimes the timing is off and a click() will fail but it doesn't seem to
//   cause any issues.

const interval = setInterval(async () => {
    // I manually set this ID. Next time update the selector so this isn't needed.
    document.getElementById("matt-select-all").click();
    await new Promise((r) => setTimeout(r, 2000));
    document.querySelector('.pseudoSelection div[aria-label="Delete"]').click();
}, 5000);
