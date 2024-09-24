#!/bin/bash

SESH=ex-tools

# tmux commands
#
# ctrl+b <then> type any `:command` command
# ctrl+b <then> other shortcut combinations
#
# `:kill-session` - Close all.
# arrows - Move between panes.

tmux has-session -t $SESH 2>/dev/null

if [ $? != 0 ]; then
    tmux new-session -s $SESH -d -n "laravel"

    # Create the first row with 2 panes
    tmux split-window -h
    tmux split-window -h
    # tmux select-layout even-horizontal

    tmux split-window -v -f -t 0
    tmux split-window -h
    tmux split-window -h

    tmux split-window -v -f -t 0
    tmux split-window -h
    tmux split-window -h

    tmux select-layout tiled

    tmux set -g pane-border-status top
    tmux set -g pane-border-format " [ ###P #T ] "
    tmux set -g pane-border-style fg=#569CD6
    tmux set -g pane-active-border-style fg=#00FF00

    tmux select-pane -t 0
    tmux select-pane -T "Sail"
    tmux send-keys "cd ~/code/exnota-tools/" C-m
    tmux send-keys "./vendor/bin/sail up" C-m

    tmux select-pane -t 1
    tmux select-pane -T "Vite"
    tmux send-keys "cd ~/code/exnota-tools/" C-m
    tmux send-keys "npm run dev" C-m

    tmux select-pane -t 2
    tmux select-pane -T "Logs"
    tmux send-keys "cd ~/code/exnota-tools/" C-m
    tmux send-keys "docker exec -it exnota-tools-local tail -f /var/www/html/storage/logs/laravel.log" C-m

    tmux select-pane -t 3
    tmux select-pane -T "Queue: Default"
    tmux send-keys "cd ~/code/exnota-tools/" C-m
    tmux send-keys "./vendor/bin/sail debug queue:listen -vvv --queue=default --timeout=120" C-m

    tmux select-pane -t 4
    tmux select-pane -T "Queue: Delete-1"
    tmux send-keys "cd ~/code/exnota-tools/" C-m
    tmux send-keys "./vendor/bin/sail debug queue:listen -vvv --queue=delete-1 --timeout=120" C-m

    tmux select-pane -t 5
    tmux select-pane -T "Queue: Delete-1"
    tmux send-keys "cd ~/code/exnota-tools/" C-m
    tmux send-keys "./vendor/bin/sail debug queue:listen -vvv --queue=delete-1 --timeout=120" C-m

    tmux select-pane -t 6
    tmux select-pane -T "Queue: Delete-1"
    tmux send-keys "cd ~/code/exnota-tools/" C-m
    tmux send-keys "./vendor/bin/sail debug queue:listen -vvv --queue=delete-1 --timeout=120" C-m

    tmux select-pane -t 7
    tmux select-pane -T "Queue: Delete-1"
    tmux send-keys "cd ~/code/exnota-tools/" C-m
    tmux send-keys "./vendor/bin/sail debug queue:listen -vvv --queue=delete-1 --timeout=120" C-m

    tmux select-pane -t 0
fi

tmux attach-session -t $SESH