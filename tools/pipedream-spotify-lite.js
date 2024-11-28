import { Client } from "@notionhq/client";

const PLAY_HISTORY_DATABASE_ID = "147944fbb2bc811e908fd19bf212e1d1";
const HELPER_RELATION_PAGE_ID = "147944fbb2bc8167b42be42f74cb17fd"; // Page ID, not Database ID!
const KEEP_UNDER_30_SECONDS = true;

export default defineComponent({
    props: {
        data: { type: "data_store" },
    },
    async run({ steps, $ }) {
        // Start fetching from after the last successful pair
        const initialFetchAfter = await this.data.get("spotify_fetch_after");
        console.log("Initial spotify_fetch_after", initialFetchAfter);

        // If no items, skip
        if (steps.recently_played.$return_value.items.length === 0) {
            console.log("No items. Skipping.");
            return steps.trigger.event;
        }

        // Find any plays to skip
        let skipPlayedAts =
            (await this.data.get("spotify_skip_played_ats")) ?? [];
        console.log("Found skipPlayedAts", skipPlayedAts);

        // Make sure your internal integration has access (not Pipedream!)
        const notion = new Client({
            auth: process.env.NOTION_MAKIWORLD_SPOTIFY_SECRET,
        });

        const startBatch = new Date();
        console.log(
            "Creating " +
                steps.recently_played.$return_value.items.length +
                " play histories"
        );

        // Loop through all items from Spotify. Oldest to newest.
        //
        // These should always be songs because Spotify currently doesn't provide
        // podcast play history.
        const items = steps.recently_played.$return_value.items.reverse();
        for (let i = 0; i < items.length; i += 2) {
            console.log(`Starting pair ${i} and ${i + 1}`);
            const startPair = new Date();

            // Process two at a time to keep total workflow under 30 seconds
            let itemOne = items[i];
            let itemTwo = items[i + 1]; // Might be undefined if an odd number of items

            // Remove any items flagged for skipping if they were successfully skipped
            const updatedSkipPlayedAts = skipPlayedAts.filter(
                (skipPlayedAt) => {
                    // Keep skipPlayedAt if it's newer or equal to at least one item
                    return [itemOne, itemTwo].some(
                        (i) => skipPlayedAt >= i.played_at
                    );
                }
            );
            console.log("updatedSkipPlayedAts", updatedSkipPlayedAts);
            if (updatedSkipPlayedAts.length !== skipPlayedAts.length) {
                console.log("Updating skipPlayedAts", updatedSkipPlayedAts);
                await this.data.set(
                    "spotify_skip_played_ats",
                    updatedSkipPlayedAts
                );
                skipPlayedAts = updatedSkipPlayedAts;
            }

            // Skip items if they were part of a pair where the other failed
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

            // Save both items to Notion.
            //
            // Use `allSettled` instead of `all` to wait for all promises before continuing.
            // This allows us to avoid creating duplicates if one failed but the other succeeded.
            console.log("Starting request pair");
            const results = await Promise.allSettled([
                skipItemOne ? null : createNotionPage(notion, itemOne, false),
                skipItemTwo ? null : createNotionPage(notion, itemTwo, true),
            ]);
            console.log("results", results);

            // If one or more failed, flag the succsesful ones to be skipped next time
            const atLeastOneRejected = results.some(
                (r) => r.status === "rejected"
            );
            let moreSkipPlayedAts = [];
            if (atLeastOneRejected) {
                console.log("Some results have rejected status");

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

            // Abort workflow if one was rejected
            if (atLeastOneRejected) {
                console.error("Pair failed");
                break;
            }

            // Save where we're up to
            const newFetchAfter = new Date(
                itemTwo?.played_at ?? itemOne.played_at
            ).getTime();
            await this.data.set("spotify_fetch_after", newFetchAfter);
            console.log("set spotify_fetch_after", newFetchAfter);

            // Abort if about to exceed 30 seconds
            const now = new Date();
            const started = new Date($.context.ts);
            console.log("now and started", now, started);
            if (
                KEEP_UNDER_30_SECONDS &&
                now.getTime() - started.getTime() > 28000
            ) {
                console.log(
                    "Pair succeeded but 30 seconds nearly reached, so skipping remainder."
                );
                break;
            }

            // Pause to avoid conflict_error. Even with this pause, there's still a speed advantage
            // to processing two at a time.
            console.log("Pair succeeded. Pausing briefly.");
            await new Promise((r) => setTimeout(r, 100));
        }

        const endBatch = new Date();
        console.log("Batch duration: " + (endBatch - startBatch));

        // Reference previous step data using the steps object and return data to use it in future steps
        return steps.trigger.event;
    },
});

async function createNotionPage(notion, item, pause) {
    if (!item) {
        return null;
    }
    return new Promise(async (resolve, reject) => {
        console.log("Creating Notion page for item " + item.track.id, item);
        const start = new Date();
        if (pause) {
            console.log("Pausing briefly.");
            await new Promise((r) => setTimeout(r, 200));
        }
        try {
            const response = await notion.pages.create({
                cover: {
                    external: {
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
                    database_id: PLAY_HISTORY_DATABASE_ID,
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
                    "Played At Date": {
                        date: {
                            start: item.played_at,
                        },
                    },
                    "Played At Timestamp": {
                        number: new Date(item.played_at).getTime(),
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
                    "Music Stats Helper": {
                        relation: [
                            {
                                id: HELPER_RELATION_PAGE_ID,
                            },
                        ],
                    },
                },
            });
            console.log(
                "Finished creating Notion page for item " +
                    item.track.id +
                    " in " +
                    (new Date() - start)
            );
            resolve({ played_at: item.played_at });
        } catch (e) {
            console.error(e);
            reject();
            return;
        }
    });
}
