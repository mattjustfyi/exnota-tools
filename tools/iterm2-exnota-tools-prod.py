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
async def main(connection):
    app = await iterm2.async_get_app(connection)
    window = app.current_terminal_window
    if window is not None:
        # Shortcuts
        # - To save/use password: Command: ⌘ ⌥ F

        # Top left
        session = app.current_terminal_window.current_tab.current_session
        await session.async_send_text("cd ~/code/exnota-tools\n")
        await session.async_send_text("ssh -t dokku@195.201.33.178 enter exnota-tools web tail -f --retry /var/www/html/storage/logs/laravel.log\n")

        # Top right
        await session.async_split_pane(vertical=True)
        session = app.current_terminal_window.current_tab.current_session
        await session.async_send_text("cd ~/code/exnota-tools\n")
        await session.async_send_text("ssh -t dokku@195.201.33.178 enter exnota-tools worker tail -f --retry /var/www/html/storage/logs/laravel.log\n")

        # Middle left
        session = app.current_terminal_window.current_tab.sessions[0]
        new_session = await session.async_split_pane(vertical=False)
        await new_session.async_send_text("cd ~/code/exnota-tools\n")
        await new_session.async_send_text("ssh -t dokku@195.201.33.178 enter exnota-tools worker tail -f --retry /var/log/supervisor/worker-default.log\n")

        # Middle right
        session = app.current_terminal_window.current_tab.sessions[2]
        new_session = await session.async_split_pane(vertical=False)
        await new_session.async_send_text("cd ~/code/exnota-tools\n")
        await new_session.async_send_text("ssh -t dokku@195.201.33.178 enter exnota-tools worker tail -f --retry /var/log/supervisor/worker-delete-1.log\n")

        # Bottom left
        session = app.current_terminal_window.current_tab.sessions[1]
        new_session = await session.async_split_pane(vertical=False)
        await new_session.async_send_text("cd ~/code/exnota-tools\n")
        await new_session.async_send_text("ssh -t dokku@195.201.33.178 enter exnota-tools worker tail -f --retry /var/log/supervisor/supervisor.log\n")

        # Bottom right
        session = app.current_terminal_window.current_tab.sessions[4]
        new_session = await session.async_split_pane(vertical=False)
        await new_session.async_send_text("cd ~/code/exnota-tools\n")
        await new_session.async_send_text("ssh root@195.201.33.178\n")

    else:
      print("No current window")   
    # This is an example of using an asyncio context manager to register a custom control
    # sequence. You can send a custom control sequence by issuing this command in a
    # terminal session in iTerm2 while this script is running:
    #
    # printf "\033]1337;Custom=id=%s:%s\a" "shared-secret" "create-window"
    # async with iterm2.CustomControlSequenceMonitor(connection, "shared-secret", r'^create-window$') as mon:
    #     while True:
    #         match = await mon.async_get()
    #         await iterm2.Window.async_create(connection)

# This instructs the script to run the "main" coroutine and to keep running even after it returns.
iterm2.run_forever(main)
