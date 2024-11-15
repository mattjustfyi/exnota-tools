import { Client } from "@notionhq/client";

// TODO: Add TRY_KEEPING_UNDER_30_SECONDS which, at the start of a loop, aborts
// if 27sec (or so) has already expired

export default defineComponent({
    props: {
        data: { type: "data_store" },
    },
    async run({ steps, $ }) {
        // TODO:
        // - Use unix time instead for accuracy
        // - Re-add 'after' to previous step
        // - Test speed of sequential requests to Notion. Possibly do 3 or so in parallel?
        //   - Doing 25 took 15842ms total with the for loop taking 15498ms
        // - Verify that the before/after fetch logic works and no songs missed
        //   - CURRENTLY USING 'before' BELOW. FIX.
        // - Set crons
        // - Test and document steps when doing from scratch

        // If no items, skip
        if (steps.recently_played.$return_value.items.length === 0) {
            return steps.trigger.event;
        }

        console.log(
            "Creating " +
                steps.recently_played.$return_value.items.length +
                " play histories"
        );

        const fetchAfter = await this.data.get("fetch_after");
        console.log("fetch_after = " + fetchAfter);

        // TODO: Your internal integration needs to have access (not Pipedream!)
        const notion = new Client({
            auth: process.env.NOTION_MAKIWORLD_SPOTIFY_SECRET,
        });

        // Loop through all items from Spotify. These should always be songs
        // because Spotify currently doesn't provide podcast play history.
        const startBatch = new Date();

        // TODO: Ensure going from oldest to newest
        const items = steps.recently_played.$return_value.items;
        for (let i = 0; i < items.length; i += 2) {
            const startPair = new Date();
            console.log(`Starting pair ${i} and ${i + 1}`);

            const itemOne = items[i];
            const itemTwo = items[i + 1]; // Might be undefined if an odd number of items

            // Save current song in loop to Notion
            const result = await Promise.allSettled([
                createNotionPage(notion, itemOne),
                itemTwo ? createNotionPage(notion, itemTwo) : null,
            ]);

            const failed = result
                .filter((r) => r.status === "rejected")
                .map((r) => r.reason.played_at);
            console.log("failed:", failed);

            // const result =  await Promise.allSettled([
            //   new Promise(async (resolve, reject) => {
            //     try {
            //       await createNotionPage(notion, item);
            //       resolve();
            //     } catch (e) {
            //       console.error(e);
            //       reject({played_at: item.played_at})
            //       return;
            //     }
            //   })
            // ]);

            // TODO:
            // Possibilities
            // - both succeed --> set fetch_after to oldest 'after', continue in loop
            // - both fail --> abort and don't update fetch_after
            // - oldest succeeds, newest fails: --> abort and set fetch_after to oldest 'after'
            // - oldest fails, newest succeeds: --> abort, don't update fetch_after, set 'skip_played_at = [...]'

            // on next run
            // - at start, fetch skip_played_at array
            // - at start of each pair, if array not empty... check if current pair are BOTH after any entries
            // - if yes -> clear those entries
            // - if no, do nothing
            // - for each INDIVIDUAL call, don't send the call if played_at matches one of skip_played_at

            const endPair = new Date();
            console.log("Pair duration: " + (endPair - startPair));

            if (failed.length > 0) {
                console.error("Pair failed");
                break;
            }

            // TODO: Handle error case (it just breaks above)
            console.log("Pair succeeded. Pausing briefly.");
            await new Promise((r) => setTimeout(r, 200));

            // TODO: update logic
            // const fetchAfter = Date.parse(item.played_at)
            // await this.data.set("fetch_after",  fetchAfter);
            // console.log('Set fetch_after to ' + fetchAfter);

            // const endItem = new Date();
            // console.log('Single request duration: ' + (endItem - startItem))
        }
        const endBatch = new Date();
        console.log("Batch duration: " + (endBatch - startBatch));

        // const listUsersResponse = await notion.users.list({})
        // console.log(listUsersResponse);

        const beforeStr = steps.recently_played.$return_value.cursors.before;
        const before = parseInt(beforeStr);

        // TODO: Remove this when stopped testing. It's just to prevent running out of items to parse.
        await this.data.set("fetch_after", before - 10000 + "");
        console.log("Set fetch_after");

        // Reference previous step data using the steps object and return data to use it in future steps
        return steps.trigger.event;
    },
});

async function createNotionPage(notion, item) {
    return new Promise(async (resolve, reject) => {
        try {
            const response = await notion.pages.create({
                cover: {
                    external: {
                        // "url": 5
                        url: item.track.album.images[0].url,
                    },
                },
                icon: {
                    external: {
                        url: "https://www.notion.so/icons/note-eighth_gray.svg",
                    },
                },
                parent: {
                    type: "database_id",
                    database_id: "138944fbb2bc8097ae9ed95d173eaeea",
                },
                properties: {
                    Name: {
                        title: [
                            {
                                text: {
                                    content: item.track.name,
                                },
                            },
                        ],
                    },
                    Artists: {
                        multi_select: item.track.artists.map((a) => ({
                            name: a.name.replaceAll(",", "-"),
                        })),
                    },
                    "Played At Timestamp": {
                        date: {
                            start: item.played_at,
                        },
                    },
                    "Length (milliseconds)": {
                        number: item.track.duration_ms,
                    },
                    Album: {
                        rich_text: [
                            {
                                text: {
                                    content: item.track.album.name,
                                },
                            },
                        ],
                    },
                    "Album Release Timestamp": {
                        date: {
                            start: item.track.album.release_date,
                        },
                    },
                    "Album Type": {
                        select: {
                            name: item.track.album.album_type,
                        },
                    },
                    "Album Artists": {
                        multi_select: item.track.album.artists.map((a) => ({
                            name: a.name.replaceAll(",", "-"),
                        })),
                    },
                    "Song ID": {
                        rich_text: [
                            {
                                text: {
                                    content: item.track.id,
                                },
                            },
                        ],
                    },
                    "Song URL": {
                        url: item.track.external_urls.spotify,
                    },
                    "Artist IDs": {
                        rich_text: [
                            {
                                text: {
                                    content: item.track.artists
                                        .map((a) => a.id)
                                        .join(","),
                                },
                            },
                        ],
                    },
                    "Main Artist URL": {
                        url: item.track.artists[0].external_urls.spotify,
                    },
                    "Album ID": {
                        rich_text: [
                            {
                                text: {
                                    content: item.track.album.id,
                                },
                            },
                        ],
                    },
                    "Album URL": {
                        url: item.track.album.external_urls.spotify,
                    },
                },
            });
            resolve();
        } catch (e) {
            console.error(e);
            reject({ played_at: item.played_at });
            return;
        }
    });
}
