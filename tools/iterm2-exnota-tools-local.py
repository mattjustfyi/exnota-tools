#!/usr/bin/env python3.10

import asyncio
import iterm2
# This script was created with the "basic" environment which does not support adding dependencies
# with pip.

# -------------------
# 🚨 Edit in repo only: exnota-tools/tools/
#
# Then copy to /Users/matt/Library/Application Support/iTerm2/Scripts
#
# iTerm doesn't allow using scripts from other directories or symlinks.
# -------------------
async def wait_for_sail(session):
    await session.async_send_text('until docker ps | grep "exnota-tools-local" | grep -q "Up "; do echo "Waiting for sail to be ready..."; sleep 2; done\n')

async def main(connection):
    app = await iterm2.async_get_app(connection)
    window = app.current_terminal_window
    if window is not None:
        # Top left
        session = app.current_terminal_window.current_tab.current_session
        await session.async_send_text("cd ~/code/exnota-tools\n")
        await session.async_send_text("./sail.sh up\n")

        # Top center
        await session.async_split_pane(vertical=True)
        session = app.current_terminal_window.current_tab.current_session
        await session.async_send_text("cd ~/code/exnota-tools\n")
        await wait_for_sail(session)
        await session.async_send_text("npm run dev\n")

        # Top right
        await session.async_split_pane(vertical=True)
        session = app.current_terminal_window.current_tab.current_session
        await session.async_send_text("cd ~/code/exnota-tools\n")
        await wait_for_sail(session)
        await session.async_send_text("docker exec -it exnota-tools-local tail -f /var/www/html/storage/logs/laravel.log\n")

        # Middle left
        session = app.current_terminal_window.current_tab.sessions[0]
        new_session = await session.async_split_pane(vertical=False)
        await new_session.async_send_text("cd ~/code/exnota-tools\n")
        await wait_for_sail(new_session)
        await new_session.async_send_text("./vendor/bin/sail debug queue:listen -vvv --queue=default --timeout=180\n")

        # Middle center
        session = app.current_terminal_window.current_tab.sessions[2]
        new_session = await session.async_split_pane(vertical=False)
        await new_session.async_send_text("cd ~/code/exnota-tools\n")
        await wait_for_sail(new_session)
        await new_session.async_send_text("./vendor/bin/sail debug queue:listen -vvv --queue=delete-1 --timeout=180\n")

        # Middle right
        session = app.current_terminal_window.current_tab.sessions[4]
        new_session = await session.async_split_pane(vertical=False)
        await new_session.async_send_text("cd ~/code/exnota-tools\n")
        await wait_for_sail(new_session)
        await new_session.async_send_text("./vendor/bin/sail debug queue:listen -vvv --queue=delete-2 --timeout=180\n")

        # Bottom left
        session = app.current_terminal_window.current_tab.sessions[1]
        new_session = await session.async_split_pane(vertical=False)
        await new_session.async_send_text("cd ~/code/exnota-tools\n")
        await wait_for_sail(new_session)
        await new_session.async_send_text("./vendor/bin/sail debug queue:listen -vvv --queue=delete-1 --timeout=180\n")

        # Bottom center
        session = app.current_terminal_window.current_tab.sessions[3]
        new_session = await session.async_split_pane(vertical=False)
        await new_session.async_send_text("cd ~/code/exnota-tools\n")
        await wait_for_sail(new_session)
        await new_session.async_send_text("./vendor/bin/sail debug queue:listen -vvv --queue=delete-1 --timeout=180\n")

        # Bottom right
        session = app.current_terminal_window.current_tab.sessions[7]
        new_session = await session.async_split_pane(vertical=False)
        await new_session.async_send_text("cd ~/code/exnota-tools\n")
        await wait_for_sail(new_session)
        await new_session.async_send_text("./vendor/bin/sail debug queue:listen -vvv --queue=delete-1 --timeout=180\n")

    else:
        print("No current window")

# This instructs the script to run the "main" coroutine and to keep running even after it returns.
iterm2.run_forever(main)
