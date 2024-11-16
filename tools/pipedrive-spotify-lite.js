import { Client } from "@notionhq/client";

// TODO: Add TRY_KEEPING_UNDER_30_SECONDS which, at the start of a loop, aborts
// if 27sec (or so) has already expired

export default defineComponent({
    props: {
        data: { type: "data_store" },
    },
    async run({ steps, $ }) {
        // TODO:
        // - Verify the skip played at logic works. Lots of conditional branches to be thorough. Scenarios:
        //   - itemOne fails, itemTwo succeeds. Hardcode the failure in by played_at value in the bottom function
        //     - what should happen: it aborts, skip_played_at in data store has the OTHER value as 'skip_played_at'
        //     - next run, remove the hardcoded failure, it should succeed, and the skip_played_at
        //       is CLEARED from data store AND duplicate not created
        //   - itemTwo fails, itemOne succeeds. As above but reversed.
        //   - itemOne and itemTwo fail. As above but with both.
        //   - itemOne fails when there is no itemTwo
        // - Save unix time to Notion instead for accuracy
        // - Verify that the before/after fetch logic works and no songs missed
        //   - CURRENTLY USING 'before' BELOW. FIX.
        // - Set crons
        // - Test and document steps when doing from scratch

        const initialFetchAfter = await this.data.get("spotify_fetch_after");
        console.log("initial spotify_fetch_after", initialFetchAfter);

        // If no items, skip
        if (steps.recently_played.$return_value.items.length === 0) {
            console.log("No items. Skipping.");
            return steps.trigger.event;
        }

        console.log(
            "Creating " +
                steps.recently_played.$return_value.items.length +
                " play histories"
        );

        // const fetchAfter = await this.data.get("spotify_fetch_after");
        // console.log('spotify_fetch_after = ' + fetchAfter);

        let skipPlayedAts =
            (await this.data.get("spotify_skip_played_ats")) ?? [];
        console.log("Found skipPlayedAts", skipPlayedAts);

        // TODO: Your internal integration needs to have access (not Pipedream!)
        const notion = new Client({
            auth: process.env.NOTION_MAKIWORLD_SPOTIFY_SECRET,
        });

        // Loop through all items from Spotify. These should always be songs
        // because Spotify currently doesn't provide podcast play history.
        const startBatch = new Date();

        // Loop through oldest to newest
        const items = steps.recently_played.$return_value.items.reverse();
        for (let i = 0; i < items.length; i += 2) {
            console.log(`Starting pair ${i} and ${i + 1}`);
            const startPair = new Date();

            // Process two at a time to keep under 30 seconds
            let itemOne = items[i];
            let itemTwo = items[i + 1]; // Might be undefined if an odd number of items

            // Remove any skipPlayedAts values that were already successfully skipped
            const updatedSkipPlayedAts = skipPlayedAts.filter(
                (skipPlayedAt) => {
                    // Keep skipPlayedAt if it's newer or equal to at least one item
                    const keepSkipPlayedAt = [itemOne, itemTwo].some(
                        (i) => skipPlayedAt >= i.played_at
                    );
                    console.log("keepSkipPlayedAt", keepSkipPlayedAt);
                    return keepSkipPlayedAt;
                }
            );
            console.log("updatedSkipPlayedAts", updatedSkipPlayedAts);
            if (updatedSkipPlayedAts.length !== skipPlayedAts.length) {
                console.log("updating skipPlayedAts", updatedSkipPlayedAts);
                await this.data.set(
                    "spotify_skip_played_ats",
                    updatedSkipPlayedAts
                );
                skipPlayedAts = updatedSkipPlayedAts;
            }

            // Skip items if they were part of a pair where the other failed
            console.log("checking if we should skip itemOne");
            console.log("skipPlayedAts", skipPlayedAts);
            console.log("itemOne.played_at", itemOne.played_at);
            let skipItemOne = false;
            if (
                skipPlayedAts.some(
                    (skipPlayedAt) =>
                        new Date(skipPlayedAt).getTime() ===
                        new Date(itemOne.played_at).getTime()
                )
            ) {
                console.log("Skipping itemOne");
                skipItemOne = true;
            }
            console.log("checking if we should skip itemTwo");
            console.log("itemTwo.played_at", itemTwo?.played_at, itemTwo);
            let skipItemTwo = false;
            if (
                !!itemTwo &&
                skipPlayedAts.some(
                    (skipPlayedAt) =>
                        new Date(skipPlayedAt).getTime() ===
                        new Date(itemTwo.played_at).getTime()
                )
            ) {
                console.log("Skipping itemTwo");
                skipItemTwo = true;
            }

            // Save both items to Notion. Use `allSettled` instead of `all` to wait for
            // all promises before continuing. This allows us to avoid creating duplicates
            // if one failed but the other succeeded.
            console.log("Starting request pair");
            const results = await Promise.allSettled([
                skipItemOne ? null : createNotionPage(notion, itemOne),
                skipItemTwo ? null : createNotionPage(notion, itemTwo),
            ]);
            console.log("results", results);

            // Save if one, or both, failed
            const atLeastOneRejected = results.some(
                (r) => r.status === "rejected"
            );
            let moreSkipPlayedAts = [];
            if (atLeastOneRejected) {
                console.log("some results have rejected status");

                moreSkipPlayedAts = results
                    // Exclude rejected and 'null' promises, which are fulfilled but have no value
                    .filter((r) => r.status === "fulfilled" && !!r.value)
                    .map((r) => r.value.played_at);
                console.log("moreSkipPlayedAts:", moreSkipPlayedAts);

                // This can be empty if itemOne failed and there was no itemTwo due to
                // fetching an odd number of tracks.
                skipPlayedAts = [...skipPlayedAts, ...moreSkipPlayedAts];
                await this.data.set("spotify_skip_played_ats", skipPlayedAts);
                console.log(
                    "spotify_skip_played_ats updated to",
                    skipPlayedAts
                );
            }

            const endPair = new Date();
            console.log("Pair duration: " + (endPair - startPair));

            // TODO: This should instead break if any have been REJECTED, not if any were successful while others were rejected
            // because the same one might fail again
            if (atLeastOneRejected) {
                console.error("Pair failed");
                break;
            }

            const newFetchAfter = new Date(
                itemTwo?.played_at ?? itemOne.played_at
            ).getTime();
            await this.data.set("spotify_fetch_after", newFetchAfter);
            console.log("set spotify_fetch_after", newFetchAfter);

            // Pause to avoid conflict_error. Even with this pause, there's still a speed advantage
            // to processing two at a time.
            console.log("Pair succeeded. Pausing briefly.");
            await new Promise((r) => setTimeout(r, 200));
        }
        const endBatch = new Date();
        console.log("Batch duration: " + (endBatch - startBatch));

        const beforeStr = steps.recently_played.$return_value.cursors.before;
        const before = parseInt(beforeStr);

        // TODO: Remove this when stopped testing. It's just to prevent running out of items to parse.
        // await this.data.set("spotify_fetch_after", (before - 10000) + '');
        // console.log('Set spotify_fetch_after');

        // Reference previous step data using the steps object and return data to use it in future steps
        return steps.trigger.event;
    },
});

async function createNotionPage(notion, item) {
    if (!item) {
        return null;
    }
    return new Promise(async (resolve, reject) => {
        try {
            // TODO: Remove
            // if (item.played_at === '2024-11-13T09:35:08.148Z') {
            //   throw new Error('test throw');
            // }
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
            resolve({ played_at: item.played_at });
        } catch (e) {
            console.error(e);
            reject();
            return;
        }
    });
}
